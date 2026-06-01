# 05 - الميغريشن (Migrations)

## Create wallet_enrollments Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('wallet_type', ['apple_pay', 'google_pay']);
            $table->string('device_id', 255);
            $table->text('dan_token'); // encrypted DAN token
            $table->string('dan_suffix', 4); // last 4 digits for display
            $table->enum('status', ['active', 'suspended', 'expired', 'revoked'])->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('card_id');
            $table->index('user_id');
            $table->index('wallet_type');
            $table->index('device_id');
            $table->index('status');
            $table->unique(['card_id', 'wallet_type', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_enrollments');
    }
};
```

## Add DAN Fields to Cards Table (Alternative)

```php
Schema::table('cards', function (Blueprint $table) {
    $table->json('wallet_tokens')->nullable()->after('status');
    $table->timestamp('wallet_enrolled_at')->nullable()->after('wallet_tokens');
});
```

## Migration Notes

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| card_id | BIGINT UNSIGNED | FK to cards table |
| user_id | BIGINT UNSIGNED | FK to users table |
| wallet_type | ENUM | apple_pay or google_pay |
| device_id | VARCHAR(255) | Device identifier from SDK |
| dan_token | TEXT | Encrypted DAN token |
| dan_suffix | VARCHAR(4) | Last 4 digits for UI display |
| status | ENUM | Current enrollment state |
| enrolled_at | TIMESTAMP | When enrolled |
| expires_at | TIMESTAMP | Token expiration (2 years) |

## Indexes

```sql
CREATE UNIQUE INDEX idx_unique_enrollment ON wallet_enrollments(card_id, wallet_type, device_id);
CREATE INDEX idx_device_id ON wallet_enrollments(device_id);
CREATE INDEX idx_status_expires ON wallet_enrollments(status, expires_at);
```
