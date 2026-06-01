# 05 - الترحيلات (Migrations)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100);
            $table->morphs('notifiable');
            $table->enum('channel', ['fcm', 'sms', 'email', 'database']);
            $table->string('title', 255)->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('created_at');
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->unique();
            $table->json('channels');
            $table->string('title_ar', 255);
            $table->string('title_en', 255);
            $table->text('body_ar');
            $table->text('body_en');
            $table->json('variables')->nullable();
            $table->tinyInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['fcm', 'sms', 'email']);
            $table->json('provider_response')->nullable();
            $table->enum('status', ['sent', 'failed']);
            $table->timestamp('sent_at')->useCurrent();
        });
    }
};
```
