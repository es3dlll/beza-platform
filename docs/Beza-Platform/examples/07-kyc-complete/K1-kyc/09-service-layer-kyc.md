# 09 - KycService كامل

```php
<?php
// app/Services/KycService.php

namespace App\Services;

use App\Events\KycUpdated;
use App\Exceptions\KycAlreadySubmittedException;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KycService
{
    public function __construct(
        private readonly VerificationService $verificationService
    ) {}

    /**
     * رفع وثائق KYC
     */
    public function submit(User $user, array $files, string $docType): array
    {
        // ─── التحقق من أن المستخدم لم يسبق له التقديم ───
        if (in_array($user->kyc_status, ['pending', 'verified'])) {
            throw new KycAlreadySubmittedException($user->kyc_status);
        }

        $autoRejected = false;
        $rejectionReason = null;

        DB::transaction(function () use (
            $user, $files, $docType, &$autoRejected, &$rejectionReason
        ) {
            foreach ($files as $category => $file) {
                /* @var UploadedFile $file */

                // ─── رفع الملف ───
                $path = $file->store("kyc/{$user->id}", 'public');

                // ─── حساب hash الملف ───
                $hash = hash_file('sha256', $file->path());

                // ─── فحص تلقائي ───
                $autoResult = $this->verificationService->autoVerify($file);

                if (!$autoResult['passed']) {
                    $autoRejected = true;
                    $rejectionReason = $autoResult['reason'];
                }

                // ─── تسجيل الوثيقة ───
                KycDocument::create([
                    'user_id'              => $user->id,
                    'doc_type'             => $docType,
                    'doc_category'         => $category,
                    'file_path'            => $path,
                    'file_hash'            => $hash,
                    'mime_type'            => $file->getMimeType(),
                    'auto_verified'        => $autoResult['passed'],
                    'auto_rejection_reason'=> $autoResult['reason'] ?? null,
                ]);
            }

            // ─── تحديث حالة المستخدم ───
            if ($autoRejected) {
                $user->update(['kyc_status' => 'rejected']);
            } else {
                $user->update(['kyc_status' => 'pending']);
            }
        });

        // ─── إشعار للمشرف (إذا لم يتم الرفض التلقائي) ───
        if (!$autoRejected) {
            try {
                KycUpdated::dispatch($user, 'pending');
            } catch (\Throwable $e) {
                Log::warning('فشل إشعار KYC', ['user_id' => $user->id]);
            }
        }

        return [
            'auto_rejected' => $autoRejected,
            'reason'        => $rejectionReason,
        ];
    }
}
```
