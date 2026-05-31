<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use App\Models\User;
use App\Modules\Marketplace\Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Seller extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'marketplace_sellers';

    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'category',
        'location',
        'contact_phone',
        'status',
        'rating',
        'total_sales',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'float',
        'total_sales' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
