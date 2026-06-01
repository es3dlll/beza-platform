# 06 - نماذج Eloquent (Eloquent Models)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $fillable = [
        'type', 'notifiable_type', 'notifiable_id', 'channel',
        'title', 'body', 'data', 'status',
        'sent_at', 'read_at', 'failed_at', 'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read', 'read_at' => now()]);
    }

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
        ]);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'type', 'channels', 'title_ar', 'title_en',
        'body_ar', 'body_en', 'variables', 'priority',
    ];

    protected $casts = [
        'channels' => 'array',
        'variables' => 'array',
    ];

    public function compile(array $data = []): array
    {
        $locale = app()->getLocale();
        $title = $locale === 'ar' ? $this->title_ar : $this->title_en;
        $body = $locale === 'ar' ? $this->body_ar : $this->body_en;

        foreach ($data as $key => $value) {
            $title = str_replace("{{$key}}", $value, $title);
            $body = str_replace("{{$key}}", $value, $body);
        }

        return ['title' => $title, 'body' => $body];
    }
}
```
