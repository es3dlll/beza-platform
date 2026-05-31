# 06 - الموديلز (Eloquent Models)

## Dispute Model

```php
<?php
// app/Models/Dispute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $fillable = [
        'transaction_id', 'complainant_id', 'respondent_id',
        'reason', 'description', 'status', 'resolution',
        'partial_amount', 'admin_notes', 'resolved_by', 'resolved_at',
        'auto_closed_at',
    ];

    protected $casts = [
        'partial_amount' => 'decimal:2',
        'resolved_at'    => 'datetime',
        'auto_closed_at' => 'datetime',
    ];

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function complainant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'complainant_id');
    }

    public function respondent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'respondent_id');
    }

    public function evidence(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    public function resolver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'investigating']);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'rejected']);
    }

    public function isExpired(): bool
    {
        return $this->created_at->diffInHours(now()) >= 48;
    }
}
```

## DisputeEvidence Model

```php
<?php
// app/Models/DisputeEvidence.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeEvidence extends Model
{
    protected $table = 'dispute_evidence';

    protected $fillable = [
        'dispute_id', 'file_path', 'original_name', 'type',
    ];

    public function dispute(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }
}
```
