# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases) - التحقق من الهوية (KYC)

## نظرة عامة

التحقق من الهوية (KYC - Know Your Customer) هو عملية حساسة تتطلب توازناً بين سهولة الاستخدام والامتثال التنظيمي (AML/CFT). يجب التعامل مع آلاف المتغيرات بما في ذلك المستندات المزيفة، الصور غير الواضحة، والمستخدمين من دول محظورة.

## جدول حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة | كود الخطأ |
|---|--------|---------|---------------|-----------|
| 1 | رفع ملفات بصيغة خاطئة (.exe, .txt) | رفض 422 | Validation | INVALID_FILE_TYPE |
| 2 | حجم ملف كبير > 10MB | رفض | Validation | FILE_TOO_LARGE |
| 3 | صور منخفضة الدقة (< 800x600) | رفض تلقائي | Service | LOW_RESOLUTION |
| 4 | صور غير واضحة (مشوشة) | قد تمر تلقائياً لكن تُرفض يدوياً | Manual Review | BLURRY_IMAGE |
| 5 | رفع نفس الصورة لجميع الحقول | الفحص التلقائي لا يكتشف (مراجعة يدوية) | Manual Review | DUPLICATE_IMAGE |
| 6 | مستخدم يرفع KYC وهو محظور | مسموح (يمكن رفع وثائق في أي وقت) | Business | - |
| 7 | رفع KYC متزامن (مرتين) | الأول ينجح والثاني يرفض | ACID | CONCURRENT_SUBMISSION |
| 8 | نوع وثيقة غير مدعوم | رفض 422 | Validation | UNSUPPORTED_DOC_TYPE |
| 9 | Admin يحاول مراجعة مستخدم تمت مراجعته بالفعل | رفض (قفل) | DB | ALREADY_REVIEWED |
| 10 | إعادة رفض KYC مراراً | يُسمح بإعادة المحاولة | Business | - |
| 11 | وثيقة هوية منتهية الصلاحية | رفض + طلب وثيقة سارية | Validation | EXPIRED_DOCUMENT |
| 12 | عدم تطابق الاسم بين الوثائق | رفض + مراجعة يدوية | Manual Review | NAME_MISMATCH |
| 13 | صور غير قابلة للقراءة (blurred) | رفض تلقائي | OCR Service | UNREADABLE_DOCUMENT |
| 14 | مستخدم يعيد تصوير الصورة 10+ مرات | رفض مؤقت (Cooldown) | Service | TOO_MANY_RETAKES |
| 15 | KYC مقدم من دولة خاضعة للعقوبات | رفض + إبلاغ جهة الامتثال | Compliance | SANCTIONED_COUNTRY |
| 16 | قاصر (أقل من 18 سنة) يحاول التسجيل | رفض | Business | UNDERAGE_USER |
| 17 | مستخدم مكتمل KYC يحاول التقديم مجدداً | رفض + إعادة توجيه | Business | ALREADY_VERIFIED |

## تحليل الحالات بالتفصيل مع أكواد PHP

### 1. صيغة ملف خاطئة
```php
public function rules(): array
{
    return [
        'id_front' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,pdf',
            'max:10240', // 10MB
        ],
        'id_back' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        'selfie' => 'required|file|mimes:jpg,jpeg,png|max:5120', // 5MB
    ];
}

public function messages(): array
{
    return [
        'id_front.mimes' => 'صيغة الملف غير مدعومة. الصيغ المسموحة: JPG, PNG, PDF',
        'id_front.max' => 'حجم الملف يتجاوز 10 ميجابايت',
    ];
}
```

### 2. حجم ملف كبير
```php
// معالجة متقدمة في خدمة الرفع
public function validateFileSize(UploadedFile $file): void
{
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($file->getSize() > $maxSize) {
        throw new KYCException(
            'حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت.',
            'FILE_TOO_LARGE'
        );
    }

    // تحقق إضافي من أبعاد الصورة
    if (str_starts_with($file->getMimeType(), 'image/')) {
        [$width, $height] = getimagesize($file->path());
        if ($width < 800 || $height < 600) {
            throw new KYCException(
                'دقة الصورة منخفضة جداً. الحد الأدنى 800x600 بكسل.',
                'LOW_RESOLUTION'
            );
        }
    }
}
```

### 3-4. صور منخفضة الدقة أو مشوشة
```php
// استخدام خدمة OCR + تحليل جودة الصورة
public function analyzeImageQuality(string $imagePath): array
{
    $image = Image::make($imagePath);

    // تحليل التباين (Laplacian variance)
    $laplacian = $image->filter(new Laplacian());
    $variance = $laplacian->getCore()->variance();

    $isBlurry = $variance < 100; // عتبة ال blur

    // تحليل الدقة
    [$width, $height] = [$image->width(), $image->height()];
    $isLowRes = $width < 800 || $height < 600;

    return [
        'is_blurry' => $isBlurry,
        'is_low_resolution' => $isLowRes,
        'width' => $width,
        'height' => $height,
        'sharpness_score' => $variance,
    ];
}
```

### 5. رفع نفس الصورة لجميع الحقول
```php
public function detectDuplicateImages(KYCSubmission $submission): bool
{
    $images = [
        $submission->id_front_path,
        $submission->id_back_path,
        $submission->selfie_path,
    ];

    // حساب بصمة (hash) لكل صورة
    $hashes = array_map(function ($path) {
        return md5_file(storage_path('app/' . $path));
    }, $images);

    // إذا تطابق الهاش لصور مختلفة
    if (count(array_unique($hashes)) < count($images)) {
        $submission->update([
            'status' => 'flagged',
            'notes' => 'تم رفع نفس الصورة لأكثر من حقل - مراجعة يدوية مطلوبة',
        ]);
        return true;
    }

    return false;
}
```

### 6. مستخدم محظور يرفع KYC
```php
// مسموح - يمكن للمستخدم المحظور رفع وثائق لإثبات هويته
// الرفع لا يغير حالة الحظر تلقائياً
public function submitKYC(User $user, array $data): KYCSubmission
{
    // لا نتحقق من حالة الحظر هنا
    $submission = KYCSubmission::create([
        'user_id' => $user->id,
        'id_front_path' => $data['id_front']->store('kyc/' . $user->id),
        'id_back_path' => $data['id_back']->store('kyc/' . $user->id),
        'selfie_path' => $data['selfie']->store('kyc/' . $user->id),
        'status' => 'pending',
    ]);

    // إشعار Admin بوجود طلب KYC جديد
    NotificationService::notifyAdmins('طلب KYC جديد من مستخدم: ' . $user->name);

    return $submission;
}
```

### 7. رفع KYC متزامن
```php
// منع الرفع المتزامن عبر قفل Pessimistic
public function submitKYCSafe(User $user, array $data): KYCSubmission
{
    return DB::transaction(function () use ($user, $data) {
        // قفل المستخدم لمنع التقديم المزدوج
        $lockedUser = User::lockForUpdate()->find($user->id);

        $existingPending = KYCSubmission::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->exists();

        if ($existingPending) {
            throw new KYCException(
                'لديك طلب KYC قيد المراجعة بالفعل. الرجاء الانتظار.',
                'CONCURRENT_SUBMISSION'
            );
        }

        return KYCSubmission::create([...]);
    });
}
```

### 8. نوع وثيقة غير مدعوم
```php
const SUPPORTED_DOCUMENT_TYPES = [
    'passport',
    'national_id',
    'driver_license',
    'residence_permit',
];

public function validateDocumentType(string $type): void
{
    if (!in_array($type, self::SUPPORTED_DOCUMENT_TYPES)) {
        throw new KYCException(
            'نوع الوثيقة غير مدعوم. الأنواع المسموحة: ' . implode('، ', self::SUPPORTED_DOCUMENT_TYPES),
            'UNSUPPORTED_DOC_TYPE'
        );
    }
}
```

### 9. مراجعة مكررة من Admin
```php
public function reviewKYC(KYCSubmission $submission, Admin $admin, string $decision): void
{
    // قفل السجل لمنع المراجعة المتزامنة
    $locked = KYCSubmission::where('id', $submission->id)
        ->whereIn('status', ['pending', 'reviewing'])
        ->lockForUpdate()
        ->first();

    if (!$locked) {
        throw new KYCException(
            'تمت مراجعة طلب KYC هذا مسبقاً من قبل مسؤول آخر',
            'ALREADY_REVIEWED'
        );
    }

    // تعيين المسؤول الذي يقوم بالمراجعة
    $submission->update([
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
        'status' => $decision, // 'approved' or 'rejected'
    ]);
}
```

### 10. إعادة رفض KYC مراراً
```php
// مسموح مع تسجيل عدد المحاولات
public function canResubmit(User $user): bool
{
    $attempts = KYCSubmission::where('user_id', $user->id)
        ->where('status', 'rejected')
        ->count();

    // بعد 5 محاولات فاشلة، يتطلب مراجعة يدوية
    if ($attempts >= 5) {
        throw new KYCException(
            'لقد تجاوزت عدد المحاولات المسموح بها. الرجاء التواصل مع الدعم الفني.',
            'MAX_ATTEMPTS_EXCEEDED'
        );
    }

    // فترة تبريد بين المحاولات (ساعة واحدة)
    $lastAttempt = KYCSubmission::where('user_id', $user->id)
        ->latest()
        ->first();

    if ($lastAttempt && $lastAttempt->created_at->diffInHours(now()) < 1) {
        throw new KYCException(
            'الرجاء الانتظار ساعة واحدة قبل إعادة المحاولة.',
            'COOLDOWN_ACTIVE'
        );
    }

    return true;
}
```

### 11. وثيقة هوية منتهية الصلاحية
```php
public function validateDocumentExpiry(string $expiryDate): void
{
    $expiry = Carbon::parse($expiryDate);

    if ($expiry->isPast()) {
        throw new KYCException(
            'الوثيقة منتهية الصلاحية منذ ' . $expiry->diffForHumans()
            . '. الرجاء تقديم وثيقة سارية المفعول.',
            'EXPIRED_DOCUMENT'
        );
    }
}

// استخراج تاريخ الانتهاء من OCR
public function extractExpiryDate(string $imagePath): ?string
{
    $text = OCRService::extractText($imagePath);
    $pattern = '/(?:تاريخ الانتهاء|تاريخ انتهاء|expiry|expires|valid until)[:\s]*(\d{2}[\/\-]\d{2}[\/\-]\d{4})/i';

    if (preg_match($pattern, $text, $matches)) {
        return $matches[1];
    }
    return null; // لم يتم العثور على تاريخ
}
```

### 12. عدم تطابق الاسم بين الوثائق
```php
public function checkNameConsistency(KYCSubmission $submission): array
{
    $idFrontText = OCRService::extractText($submission->id_front_path);
    $idBackText = OCRService::extractText($submission->id_back_path);

    // استخراج الأسماء
    $nameFromFront = $this->extractFullName($idFrontText);
    $nameFromBack = $this->extractFullName($idBackText);

    // مقارنة الأسماء
    $similarity = $this->calculateTextSimilarity($nameFromFront, $nameFromBack);

    $issues = [];
    if ($similarity < 80) {
        $issues[] = 'عدم تطابق كبير بين الاسم في وجهي البطاقة';
    }

    // المقارنة مع اسم المستخدم في النظام
    $userNameSimilarity = $this->calculateTextSimilarity(
        $nameFromFront,
        $submission->user->name
    );

    if ($userNameSimilarity < 70) {
        $issues[] = 'الاسم في الوثيقة لا يتطابق مع اسم المستخدم المسجل';
    }

    return $issues;
}
```

### 13. صور غير قابلة للقراءة
```php
public function checkDocumentReadability(string $imagePath): bool
{
    try {
        $confidence = OCRService::extractTextWithConfidence($imagePath);
        return $confidence > 0.7; // 70% حد أدنى للثقة
    } catch (OCRException $e) {
        Log::error('فشل OCR: ' . $e->getMessage());
        return false;
    }
}
```

### 14. إعادة تصوير 10+ مرات
```php
public function checkRetakeLimit(User $user): void
{
    $recentRetakes = KYCSubmission::where('user_id', $user->id)
        ->where('created_at', '>=', now()->subHour())
        ->count();

    if ($recentRetakes >= 10) {
        // منع مؤقت لمدة 24 ساعة
        TemporaryBlock::create([
            'user_id' => $user->id,
            'type' => 'kyc_retake_limit',
            'expires_at' => now()->addDay(),
        ]);

        throw new KYCException(
            'لقد تجاوزت الحد المسموح من محاولات التصوير. يرجى المحاولة بعد 24 ساعة.',
            'TOO_MANY_RETAKES'
        );
    }
}
```

### 15. دولة خاضعة للعقوبات
```php
// قائمة الدول الخاضعة للعقوبات (SANCTIONED_COUNTRIES)
const SANCTIONED_COUNTRIES = [
    'IR' => 'إيران',
    'KP' => 'كوريا الشمالية',
    'SY' => 'سوريا',
    'CU' => 'كوبا',
    'SD' => 'السودان',
    // ... إلخ
];

public function checkSanctionedCountry(string $countryCode): void
{
    if (array_key_exists($countryCode, self::SANCTIONED_COUNTRIES)) {
        // تسجيل وإبلاغ جهة الامتثال
        ComplianceAlert::create([
            'type' => 'SANCTIONED_COUNTRY_KYC',
            'country' => $countryCode,
            'user_id' => auth()->id(),
        ]);

        // إبلاغ AML officer
        NotificationService::notifyCompliance(
            'محاولة KYC من دولة خاضعة للعقوبات: ' . self::SANCTIONED_COUNTRIES[$countryCode]
        );

        throw new KYCException(
            'عذراً، لا يمكننا قبول طلب KYC من دول معينة حالياً.',
            'SANCTIONED_COUNTRY'
        );
    }
}
```

### 16. قاصر (أقل من 18 سنة)
```php
public function validateAge(string $dateOfBirth): void
{
    $dob = Carbon::parse($dateOfBirth);
    $age = $dob->age;

    if ($age < 18) {
        // لا نرفض بشكل مفاجئ، نطلب ولي أمر
        throw new KYCException(
            'يجب أن يكون عمرك 18 سنة على الأقل للتسجيل. '
            . 'إذا كنت ولي أمر، يمكنك التواصل مع الدعم الفني.',
            'UNDERAGE_USER'
        );
    }

    if ($age > 120) {
        // تاريخ ميلاد غير منطقي
        throw new KYCException(
            'تاريخ الميلاد المدخل غير منطقي. يرجى التحقق من صحة البيانات.',
            'UNREALISTIC_AGE'
        );
    }
}
```

### 17. مستخدم مكتمل KYC يحاول التقديم مجدداً
```php
public function checkAlreadyVerified(User $user): void
{
    $verifiedKyc = KYCSubmission::where('user_id', $user->id)
        ->where('status', 'approved')
        ->exists();

    if ($verifiedKyc) {
        throw new KYCException(
            'تم التحقق من هويتك مسبقاً. يمكنك تحديث بياناتك من صفحة الإعدادات.',
            'ALREADY_VERIFIED'
        );
    }
}
```

## مصفوفة القرار لـ KYC

| الحالة | القرار | رسالة للمستخدم | تصعيد |
|--------|--------|----------------|-------|
| صيغة ملف خاطئة | رفض فوري | "الصيغ المسموحة: JPG, PNG, PDF" | لا |
| حجم كبير | رفض فوري | "الحد الأقصى 10MB" | لا |
| دقة منخفضة | رفض فوري | "الحد الأدنى 800×600 بكسل" | لا |
| صورة مشوشة | مراجعة يدوية | "سيتم المراجعة خلال 24 ساعة" | نعم |
| وثيقة منتهية | رفض فوري | "قدّم وثيقة سارية المفعول" | لا |
| اسم غير متطابق | مراجعة يدوية | "سيتم المراجعة خلال 48 ساعة" | نعم |
| 10+ محاولات | حظر 24 ساعة | "حاول مجدداً بعد 24 ساعة" | نعم |
| دولة عقوبات | رفض + إبلاغ | "لا يمكن القبول حالياً" | نعم (امتثال) |
| قاصر | رفض | "يجب أن تكون 18+" | لا |
| مكتمل مسبقاً | رفض | "هويتك موثقة بالفعل" | لا |
| مستخدم محظور | مسموح | "سيتم المراجعة" | لا |

## توصيات امتثال وأمان

1. **الاحتفاظ بالبيانات**: يجب الاحتفاظ بنسخ KYC لمدة 5 سنوات وفقاً للوائح AML
2. **التشفير**: تشفير جميع صور KYC باستخدام AES-256 في التخزين
3. **الخصوصية**: عدم عرض صور KYC كاملة في واجهة Admin (إظهار حقول الاسم فقط)
4. **GDPR**: إتاحة خيار حذف بيانات KYC بعد انتهاء المدة القانونية
5. **مراجعة دورية**: مراجعة عشوائية بنسبة 10% من طلبات KYC المقبولة تلقائياً
6. **Audit Log**: تسجيل كل عملية رفع ومراجعة وتعديل على KYC
7. **مكافحة الاحتيال**: استخدام Face Matching API للمقارنة بين السيلفي وصورة الهوية
