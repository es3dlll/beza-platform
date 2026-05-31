<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Feedback extends Model
{
    protected $fillable = [
        'user_id', 'module', 'category', 'severity', 'description',
        'screenshot_url', 'context', 'status', 'internal_notes',
    ];

    protected $casts = [
        'context' => 'array',
        'internal_notes' => 'array',
    ];

    public const CATEGORIES = ['ui_issue', 'feature_request', 'bug_report', 'compliance_question'];
    public const SEVERITIES = ['low', 'medium', 'high'];
    public const STATUSES = ['new', 'reviewing', 'resolved'];
}
