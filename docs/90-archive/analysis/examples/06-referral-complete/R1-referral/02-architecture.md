# 02 - مكان العملية في الأرشيتيكشر (Architecture Position)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Flutter App / React SPA                        │
│  [ReferralScreen] → [ReferralRepository] → [HTTP Request]        │
└────────────────────────────────┬─────────────────────────────────┘
                                  │ POST /api/v1/referral/code
                                  │ POST /api/v1/referral/claim
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                        │
│  Route::post('/referral/code', [ReferralController::class, 'generateCode']) │
│  Route::post('/referral/claim', [ReferralController::class, 'claim'])      │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Middleware Stack                               │
│  ┌──────────┐  ┌──────────┐  ┌───────────────────────────────┐  │
│  │ auth:api │  │ throttle │  │ verified (for claim)         │  │
│  └──────────┘  └──────────┘  └───────────────────────────────┘  │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    ReferralController                              │
│  1. Generate referral code for user                               │
│  2. Claim referral when friend registers                          │
│  3. Call ReferralService/ RewardService                           │
└────────────────────────────────┬─────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────┐
│                    ReferralService / RewardService                  │
│  GenerateCode:                                                    │
│  1. Check if user already has code → return existing              │
│  2. Generate unique 8-char code                                   │
│  3. Store in referral_codes table                                 │
│                                                                   │
│  Claim:                                                           │
│  1. Validate referral code exists                                 │
│  2. Check friend is new user                                      │
│  3. Link friend to referrer                                       │
│  4. On first transaction ≥ 10 USD → trigger reward                │
└────────────────────────────────┬─────────────────────────────────┘
                                  │
                     ┌────────────┼────────────┐
                     ▼            ▼            ▼
           ┌────────────┐ ┌────────────┐ ┌────────────┐
           │ MySQL       │ │ Redis      │ │ Queue      │
           │ referral_   │ │ Cache      │ │ (Rewards)  │
           │ codes       │ │            │ │            │
           │ rewards     │ │            │ │            │
           └────────────┘ └────────────┘ └──────┬─────┘
                                                │
                                       ┌────────┴────────┐
                                       ▼                 ▼
                                ┌────────────┐   ┌────────────┐
                                │ Listener    │   │ Notification│
                                │ SendReward  │   │ FCM        │
                                └────────────┘   └────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/ReferralController.php
├── app/Http/Requests/ReferralClaimRequest.php
├── app/Services/ReferralService.php
├── app/Services/RewardService.php
├── app/Models/ReferralCode.php
├── app/Models/ReferralReward.php
├── app/Events/ReferralClaimed.php
├── app/Listeners/SendReferralReward.php
├── database/migrations/2024_01_01_000020_create_referral_codes_table.php
├── database/migrations/2024_01_01_000021_create_referral_rewards_table.php
└── tests/Feature/ReferralTest.php
```
