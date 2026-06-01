# 02 - بنية الاختبارات (Architecture)

## هيكل مجلدات الاختبارات

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── RegisterTest.php
│   │   ├── LoginTest.php
│   │   └── OtpTest.php
│   ├── Wallet/
│   │   ├── WalletBalanceTest.php
│   │   └── WalletExchangeTest.php
│   ├── Transfer/
│   │   └── TransferTest.php
│   ├── Merchant/
│   │   ├── MerchantRegistrationTest.php
│   │   ├── ProductTest.php
│   │   └── PaymentTest.php
│   ├── Agent/
│   │   ├── CashInTest.php
│   │   └── CashOutTest.php
│   ├── Deal/
│   │   ├── DealCreationTest.php
│   │   └── DealInvestmentTest.php
│   ├── Card/
│   │   └── CardTransactionTest.php
│   ├── Kyc/
│   │   └── KycVerificationTest.php
│   └── Admin/
│       ├── AuditLogTest.php
│       └── FraudDetectionTest.php
├── Unit/
│   ├── Services/
│   │   ├── WalletServiceTest.php
│   │   ├── TransferServiceTest.php
│   │   ├── FraudDetectionServiceTest.php
│   │   └── TwoFactorServiceTest.php
│   ├── Models/
│   │   ├── UserTest.php
│   │   ├── WalletTest.php
│   │   └── TransactionTest.php
│   └── Helpers/
│       └── ReferenceNumberTest.php
├── Pest/
│   ├── AuthPestTest.php
│   └── TransferPestTest.php
├── TestCase.php
└── CreatesApplication.php
```

## إعداد TestCase

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // تشغيل Seeders
        $this->seed();
    }
}
```
