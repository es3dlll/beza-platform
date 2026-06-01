# 06 - موديلات Eloquent

## Contact Model

```php
<?php
// app/Models/Contact.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
```

## Subscriber Model

```php
<?php
// app/Models/Subscriber.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
        'source',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'subscribed_at'   => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function unsubscribe(): void
    {
        $this->update([
            'is_active'       => false,
            'unsubscribed_at' => now(),
        ]);
    }
}
```

## MerchantInquiry Model

```php
<?php
// app/Models/MerchantInquiry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantInquiry extends Model
{
    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'business_type',
        'monthly_volume',
        'notes',
        'status',
    ];

    protected $casts = [
        'monthly_volume' => 'decimal:2',
    ];

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function markAsContacted(): void
    {
        $this->update(['status' => 'contacted']);
    }
}
```

## AgentInquiry Model

```php
<?php
// app/Models/AgentInquiry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentInquiry extends Model
{
    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'city',
        'has_office',
        'notes',
        'status',
    ];

    protected $casts = [
        'has_office' => 'boolean',
    ];
}
```
