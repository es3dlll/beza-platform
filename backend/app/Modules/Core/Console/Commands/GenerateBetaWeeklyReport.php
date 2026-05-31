<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Models\BetaFeedback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class GenerateBetaWeeklyReport extends Command
{
    protected $signature = 'beta:weekly-report {--week= : ISO week number}';
    protected $description = 'Generate weekly beta feedback report';

    public function handle(): int
    {
        $week = $this->option('week') ?? now()->isoWeek;
        $year = now()->isoWeekYear;
        $start = now()->setISODate($year, (int) $week)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $feedbacks = BetaFeedback::whereBetween('created_at', [$start, $end])->get();
        $total = $feedbacks->count();
        $categories = $feedbacks->groupBy('category')->map->count()->toArray();
        $avgRating = $total > 0 ? round($feedbacks->avg('rating'), 2) : 0;
        $statuses = $feedbacks->groupBy('status')->map->count()->toArray();

        $previousStart = $start->copy()->subWeek();
        $previousEnd = $end->copy()->subWeek();
        $previousCount = BetaFeedback::whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $trend = $previousCount > 0
            ? round((($total - $previousCount) / $previousCount) * 100, 1)
            : 0;

        $topIssues = $feedbacks->where('category', 'technical_issue')
            ->sortByDesc('rating')
            ->take(3)
            ->pluck('description')
            ->toArray();

        $topRequests = $feedbacks->where('category', 'feature_request')
            ->sortByDesc('rating')
            ->take(3)
            ->pluck('description')
            ->toArray();

        $report = [
            'week' => "{$year}-W{$week}",
            'period' => "{$start->toDateString()} → {$end->toDateString()}",
            'total_feedback' => $total,
            'previous_week_count' => $previousCount,
            'trend_percent' => $trend,
            'average_rating' => $avgRating,
            'categories' => $categories,
            'statuses' => $statuses,
            'top_3_issues' => $topIssues,
            'top_3_requests' => $topRequests,
        ];

        $summary = "📊 Beta Weekly Report {$year}-W{$week}\n"
            . "Period: {$report['period']}\n"
            . "Total: {$total} feedback items (trend: {$trend}%)\n"
            . "Avg Rating: {$avgRating}/5\n"
            . "Categories: " . json_encode($categories) . "\n"
            . "Top Issues:\n" . implode("\n", array_map(fn($i) => "  • {$i}", $topIssues))
            . "\nTop Requests:\n" . implode("\n", array_map(fn($r) => "  • {$r}", $topRequests));

        $filePath = storage_path("beta-reports/weekly-{$year}-W{$week}.json");
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::channel('audit')->info('BETA_WEEKLY_REPORT', $report);

        $this->info($summary);
        $this->info("Report archived: {$filePath}");

        return self::SUCCESS;
    }
}
