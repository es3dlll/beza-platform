<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Models;

use App\Modules\Marketplace\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Product extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'marketplace_products';

    protected $fillable = [
        'seller_id',
        'name',
        'description',
        'price_fils',
        'category',
        'location',
        'images',
        'status',
        'rating',
        'metadata',
    ];

    protected $casts = [
        'price_fils' => 'integer',
        'rating' => 'float',
        'images' => 'array',
        'metadata' => 'array',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
