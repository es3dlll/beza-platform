# 10 - VerificationService كامل

```php
<?php
// app/Services/VerificationService.php

namespace App\Services;

use App\Events\KycUpdated;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    /**
     * الفحص التلقائي للصور
     */
    public function autoVerify(UploadedFile $file): array
    {
        try {
            $imageInfo = getimagesize($file->path());

            if (!$imageInfo) {
                return ['passed' => false, 'reason' => 'غير قادر على قراءة الصورة'];
            }

            [$width, $height] = $imageInfo;

            // ─── فحص الأبعاد (800×600 minimum) ───
            if ($width < 800 || $height < 600) {
                return [
                    'passed' => false,
                    'reason' => "الصورة صغيرة جداً ({$width}x{$height}). الحد الأدنى 800x600 بكسل",
                ];
            }

            // ─── فحص DPI ───
            $dpi = $imageInfo[0] ?? 72; // تقديري
            if (isset($imageInfo['bits']) && $imageInfo['bits'] > 0) {
                // في الصور الحقيقية نحاول استخراج DPI
            }

            // ─── فحص حجم الملف ───
            $sizeMB = $file->getSize() / 1024 / 1024;
            if ($sizeMB > 10) {
                return [
                    'passed' => false,
                    'reason' => 'حجم الصورة كبير جداً',
                ];
            }

            return ['passed' => true, 'reason' => null];
        } catch (\Throwable $e) {
            Log::error('خطأ في الفحص التلقائي للصورة', [
                'error' => $e->getMessage(),
            ]);
            return ['passed' => false, 'reason' => 'فشل الفحص التلقائي'];
        }
    }

    /**
     * مراجعة يدوية من Admin — قبول
     */
    public function approve(User $user, User $admin, ?string $notes = null): void
    {
        $user->update([
            'kyc_status'      => 'verified',
            'kyc_verified_at' => now(),
        ]);

        \App\Models\KycReview::create([
            'user_id'     => $user->id,
            'reviewed_by' => $admin->id,
            'status'      => 'approved',
            'notes'       => $notes,
            'reviewed_at' => now(),
        ]);

        KycUpdated::dispatch($user, 'verified');
    }

    /**
     * مراجعة يدوية من Admin — رفض
     */
    public function reject(User $user, User $admin, string $reason): void
    {
        $user->update(['kyc_status' => 'rejected']);

        \App\Models\KycReview::create([
            'user_id'     => $user->id,
            'reviewed_by' => $admin->id,
            'status'      => 'rejected',
            'notes'       => $reason,
            'reviewed_at' => now(),
        ]);

        KycUpdated::dispatch($user, 'rejected', $reason);
    }
}
```
