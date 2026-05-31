# Settlement AI Integration

## AI Use Cases

### 1. Intelligent Exception Classification

**Goal**: Automatically classify settlement exceptions by root cause, reducing manual investigation time.

```php
class AISettlementClassifier
{
    public function classify(SettlementException $exception): string
    {
        // Send exception details to AI service
        $prompt = "
            Classify this settlement exception:
            - Type: {$exception->type->value}
            - Internal amount: {$exception->internal_amount} SYP
            - External amount: {$exception->external_amount} SYP
            - Difference: {$exception->difference} SYP
            - Entity: {$exception->entity_type}/{$exception->entity_id}
            - Previous exceptions for this entity: {$previousCount}

            Possible root causes:
            1. BANK_FEE — Bank deducted transfer fee
            2. TIMING — Confirmation from previous cycle
            3. EXCHANGE_RATE — Currency conversion difference
            4. DUPLICATE — Transaction settled in previous batch
            5. SYSTEM_ERROR — Internal system discrepancy
            6. UNKNOWN — Cannot determine

            Respond with ONLY the root cause key.
        ";

        $classification = $this->aiService->classify($prompt);
        return $classification;
    }
}
```

### 2. Predictive Exception Detection

**Goal**: Flag potentially problematic batches before processing, based on historical patterns.

```php
class PredictiveExceptionDetector
{
    public function analyze(SettlementBatch $batch): PredictionResult
    {
        $features = [
            'transaction_count' => $batch->transaction_count,
            'total_amount' => $batch->total_amount,
            'entity_count' => $batch->items->count(),
            'hour_of_day' => now()->hour,
            'day_of_week' => now()->dayOfWeek,
            'entity_mix' => $batch->items->pluck('entity_type')->unique()->count(),
            'historical_match_rate' => $this->getHistoricalMatchRate($batch->items),
            'recent_exception_rate' => $this->getRecentExceptionRate(),
        ];

        $prediction = $this->mlService->predict('exception_probability', $features);

        if ($prediction['probability'] > 0.3) {
            return new PredictionResult(
                highRisk: true,
                probability: $prediction['probability'],
                suggestion: "High exception probability ({$prediction['probability']}). Recommend manual review before processing."
            );
        }

        return new PredictionResult(highRisk: false, probability: $prediction['probability']);
    }
}
```

### 3. Automated Exception Resolution Suggestions

**Goal**: Suggest resolution actions based on similar past exceptions.

```php
class AIExceptionResolver
{
    public function suggestResolution(SettlementException $exception): ResolutionSuggestion
    {
        // Find similar resolved exceptions
        $similar = $this->findSimilar($exception);

        if ($similar->isNotEmpty()) {
            $mostCommon = $similar->groupBy('resolution_type')
                ->sortByDesc->count()
                ->first()
                ->first();

            return new ResolutionSuggestion(
                resolutionType: $mostCommon->resolution_type,
                confidence: $similar->count() / $this->totalExceptions,
                similarCases: $similar->take(3)->toArray(),
                suggestedNotes: $this->generateNotes($exception, $mostCommon),
                autoResolve: $similar->count() > 10, // Auto-resolve if 10+ similar cases
            );
        }

        return new ResolutionSuggestion(
            resolutionType: null,
            confidence: 0,
            similarCases: [],
            suggestedNotes: 'No similar cases found. Manual investigation required.',
        );
    }

    private function findSimilar(SettlementException $exception): Collection
    {
        return SettlementException::where('type', $exception->type)
            ->where('entity_type', $exception->entity_type)
            ->where('status', 'resolved')
            ->where('difference', '>=', $exception->difference * 0.8)
            ->where('difference', '<=', $exception->difference * 1.2)
            ->where('resolved_at', '>', now()->subMonths(3))
            ->get();
    }
}
```

### 4. Natural Language Query for Settlement Reports

**Goal**: Allow operations team to query settlement data using Arabic natural language.

```
User: "كم دفعة تسوية كانت معلقة الأسبوع الماضي؟"
AI: "كانت 3 دفعات معلقة في الأسبوع الماضي (22-28 مايو 2026):
     - STL-20260522-001: سبب استثناء مبلغ
     - STL-20260524-003: سبب تأكيد مفقود
     - STL-20260526-001: سبب رفض بنكي
     جميعها تمت تسويتها خلال 4 ساعات في المتوسط"

User: "أظهر أعلى 5 استثناءات من حيث القيمة هذا الشهر"
AI: "أعلى 5 استثناءات لشهر مايو 2026:
     1. EXC-20260515-003: 250,000 ل.س — Bank of Syria
     2. EXC-20260522-001: 150,000 ل.س — Syriatel
     ...
     الإجمالي: 550,000 ل.س عبر 12 استثناء"
```

### 5. Anomaly Detection

```php
class SettlementAnomalyDetector
{
    public function check(SettlementBatch $batch): AnomalyReport
    {
        // Check 1: Amount deviation from historical average
        $avgAmount = $this->getAverageDailyAmount(30); // 30-day avg
        $deviation = abs(($batch->total_amount - $avgAmount) / $avgAmount) * 100;

        if ($deviation > 50) {
            return new AnomalyReport(
                type: 'amount_deviation',
                severity: 'high',
                message: "Batch amount {$batch->total_amount} deviates {$deviation}% from 30-day average {$avgAmount}",
            );
        }

        // Check 2: Unusual entity mix
        // Check 3: Rapid repeat transactions
        // Check 4: Unusual time patterns

        return new AnomalyReport(type: 'none', severity: 'none');
    }
}
```

## AI Configuration

```php
// config/ai-settlement.php
return [
    'classifier' => [
        'enabled' => true,
        'model' => 'settlement-exception-classifier-v2',
        'min_confidence' => 0.7,
    ],
    'predictor' => [
        'enabled' => true,
        'model' => 'settlement-risk-predictor-v1',
        'threshold' => 0.3,
    ],
    'resolver' => [
        'enabled' => true,
        'auto_resolve_min_cases' => 10,
        'auto_resolve_max_amount' => 10000, // SYP
    ],
    'nlp' => [
        'enabled' => true,
        'language' => 'ar',
        'max_results' => 10,
    ],
    'anomaly' => [
        'enabled' => true,
        'deviation_threshold_pct' => 50,
        'lookback_days' => 30,
    ],
];
```
