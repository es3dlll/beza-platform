<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Models;

use App\Modules\Fraud\Database\Factories\RiskScoreFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RiskScore extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'risk_scores';

    protected $fillable = [
        'score',
        'status',
        'reasons',
        'request_type',
        'request_id',
        'user_id',
        'amount_fils',
        'currency',
        'region',
        'metadata',
    ];

    protected $casts = [
        'score' => 'integer',
        'amount_fils' => 'integer',
        'reasons' => 'array',
        'metadata' => 'array',
    ];

    const UPDATED_AT = null;

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
