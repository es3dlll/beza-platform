# 09 - ReferralService كامل

```php
<?php
// app/Services/ReferralService.php

namespace App\Services;

use App\Exceptions\AlreadyReferredException;
use App\Exceptions\DuplicateReferralException;
use App\Exceptions\SelfReferralException;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * إنشاء كود إحالة للمستخدم
     */
    public function generateCode(User $user): ReferralCode
    {
        // إذا كان لديه كود بالفعل → أعده
        $existing = $user->referralCode;
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user) {
            return ReferralCode::create([
                'user_id' => $user->id,
                'code'    => ReferralCode::generateUniqueCode(),
            ]);
        });
    }

    /**
     * تسجيل دعوة — ربط المستخدم المدعو بالداعي
     */
    public function claim(User $user, string $codeStr): void
    {
        $code = ReferralCode::where('code', $codeStr)
            ->where('is_active', true)
            ->firstOrFail();

        // ─── التحقق من الصلاحية ───
        if ($code->user_id === $user->id) {
            throw new SelfReferralException();
        }

        if ($user->referred_by !== null) {
            throw new AlreadyReferredException();
        }

        $existingReward = ReferralReward::where('referrer_id', $code->user_id)
            ->where('referred_id', $user->id)
            ->exists();

        if ($existingReward) {
            throw new DuplicateReferralException();
        }

        // ─── ربط المستخدم بالداعي ───
        DB::transaction(function () use ($user, $code) {
            $user->update(['referred_by' => $code->user_id]);

            ReferralReward::create([
                'referrer_id'      => $code->user_id,
                'referred_id'      => $user->id,
                'referral_code_id' => $code->id,
                'reward_type'      => 'signup',
                'referrer_amount'  => 2.00,
                'referred_amount'  => 2.00,
                'status'           => 'pending',
            ]);

            $code->incrementUsage();
        });

        Log::info('تم تسجيل دعوة جديدة', [
            'referrer_id' => $code->user_id,
            'referred_id' => $user->id,
            'code'        => $codeStr,
        ]);
    }
}
```
