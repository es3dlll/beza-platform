<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class BetaFeedback extends Model
{
    protected $table = 'beta_feedback';

    protected $fillable = [
        'feedback_id',
        'user_id',
        'category',
        'description',
        'screenshot_url',
        'rating',
        'allow_followup',
        'status',
        'analysis_metadata',
        'internal_notes',
    ];

    protected $casts = [
        'allow_followup' => 'boolean',
        'rating' => 'integer',
        'analysis_metadata' => 'array',
        'internal_notes' => 'array',
    ];

    public const CATEGORIES = [
        'technical_issue',
        'feature_request',
        'general_question',
        'security_report',
    ];

    public const STATUSES = [
        'new',
        'in_review',
        'resolved',
        'rejected',
    ];
}
