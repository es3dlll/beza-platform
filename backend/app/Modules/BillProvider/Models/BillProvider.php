<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Models;

use App\Modules\BillProvider\Database\Factories\BillProviderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BillProvider extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'bill_providers';

    protected $fillable = [
        'name',
        'category',
        'external_id',
        'is_active',
        'logo_url',
        'support_phone',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
