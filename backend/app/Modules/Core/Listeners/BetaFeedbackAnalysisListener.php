<?php

declare(strict_types=1);

namespace App\Modules\Core\Listeners;

use App\Modules\Core\Events\BetaFeedbackReceived;
use App\Modules\Core\Events\SecurityAlert;
use App\Modules\Core\Models\BetaFeedback;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class BetaFeedbackAnalysisListener
{
    private const SECURITY_KEYWORDS = [
        'اختراق', 'تهكير', 'تسريب', 'ثغرة', 'access', 'hack', 'breach',
        'exploit', 'vulnerability', 'malicious', 'unauthorized',
    ];

    private const BUG_KEYWORDS = [
        'فشل', 'خطأ', 'لا يعمل', 'بطيء', 'عطل', 'bug', 'error', 'crash',
        'broken', 'slow', 'failed', 'exception',
    ];

    private const FEATURE_KEYWORDS = [
        'أتمنى', 'لو كان', 'إضافة', 'تحسين', 'نقص', 'feature', 'wish',
        'improve', 'suggest', 'missing', 'would be nice',
    ];

    private const POSITIVE_KEYWORDS = [
        'ممتاز', 'رائع', 'جيد', 'شكراً', 'great', 'excellent', 'awesome',
        'amazing', 'love', 'perfect', 'easy', 'fast',
    ];

    public function handle(BetaFeedbackReceived $event): void
    {
        $analysis = $this->analyze($event->description);
        $tags = [];

        if ($this->containsKeyword($event->description, self::BUG_KEYWORDS)) {
            $tags[] = 'bug_report';
        }
        if ($this->containsKeyword($event->description, self::FEATURE_KEYWORDS)) {
            $tags[] = 'feature_request';
        }
        if ($this->containsKeyword($event->description, self::POSITIVE_KEYWORDS)) {
            $tags[] = 'positive_feedback';
        }

        if ($analysis['sentiment'] === 'negative' && !in_array('bug_report', $tags)) {
            $tags[] = 'negative_feedback';
        }

        $metadata = [
            'tags' => $tags,
            'sentiment' => $analysis['sentiment'],
            'keyword_matches' => $analysis['matches'],
        ];

        BetaFeedback::where('feedback_id', $event->feedbackId)->update([
            'analysis_metadata' => $metadata,
        ]);

        if ($this->containsKeyword($event->description, self::SECURITY_KEYWORDS)) {
            Event::dispatch(new SecurityAlert(
                feedbackId: $event->feedbackId,
                userId: $event->userId,
                description: $event->description,
                timestamp: $event->timestamp,
            ));

            Log::channel('audit')->critical('SECURITY_ALERT_FROM_BETA_FEEDBACK', [
                'feedback_id' => $event->feedbackId,
                'user_id' => $event->userId,
            ]);
        }
    }

    private function analyze(string $text): array
    {
        $positive = $this->countKeywordMatches($text, self::POSITIVE_KEYWORDS);
        $negative = $this->countKeywordMatches($text, self::BUG_KEYWORDS)
            + $this->countKeywordMatches($text, self::SECURITY_KEYWORDS);

        return [
            'sentiment' => $positive > $negative ? 'positive' : ($negative > $positive ? 'negative' : 'neutral'),
            'matches' => [
                'positive' => $positive,
                'negative' => $negative,
            ],
        ];
    }

    private function containsKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function countKeywordMatches(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                $count++;
            }
        }
        return $count;
    }
}
