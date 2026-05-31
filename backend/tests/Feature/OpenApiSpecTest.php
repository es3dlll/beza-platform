<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

final class OpenApiSpecTest extends TestCase
{
    private array $spec;

    protected function setUp(): void
    {
        parent::setUp();
        $path = dirname(__DIR__, 2) . '/docs/specs/openapi.yaml';
        $this->assertFileExists($path);
        $yaml = file_get_contents($path);
        $this->spec = Yaml::parse($yaml);
    }

    public function test_parses_as_valid_yaml(): void
    {
        $this->assertIsArray($this->spec);
    }

    public function test_has_required_top_level_keys(): void
    {
        $this->assertArrayHasKey('openapi', $this->spec);
        $this->assertArrayHasKey('info', $this->spec);
        $this->assertArrayHasKey('servers', $this->spec);
        $this->assertArrayHasKey('paths', $this->spec);
        $this->assertArrayHasKey('components', $this->spec);
        $this->assertArrayHasKey('tags', $this->spec);
    }

    public function test_openapi_version_is_3_0_3(): void
    {
        $this->assertSame('3.0.3', $this->spec['openapi']);
    }

    public function test_covers_all_54_endpoints_across_10_tags(): void
    {
        $endpointCount = 0;
        $tags = [];
        foreach ($this->spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $def) {
                $endpointCount++;
                $tag = $def['tags'][0];
                $tags[$tag] = ($tags[$tag] ?? 0) + 1;
            }
        }

        $this->assertSame(54, $endpointCount);
        $this->assertArrayHasKey('Agent', $tags);
        $this->assertArrayHasKey('Financial Core', $tags);
        $this->assertArrayHasKey('Fraud', $tags);
        $this->assertArrayHasKey('FX', $tags);
        $this->assertArrayHasKey('Ledger', $tags);
        $this->assertArrayHasKey('Event Bus', $tags);
        $this->assertArrayHasKey('Wallet', $tags);
        $this->assertArrayHasKey('Remittance', $tags);
        $this->assertArrayHasKey('Merchant', $tags);
        $this->assertArrayHasKey('Compliance', $tags);

        $this->assertSame(11, $tags['Agent']);
        $this->assertSame(6, $tags['Financial Core']);
        $this->assertSame(5, $tags['Fraud']);
        $this->assertSame(5, $tags['FX']);
        $this->assertSame(6, $tags['Ledger']);
        $this->assertSame(3, $tags['Event Bus']);
        $this->assertSame(3, $tags['Wallet']);
        $this->assertSame(4, $tags['Remittance']);
        $this->assertSame(6, $tags['Merchant']);
        $this->assertSame(5, $tags['Compliance']);
    }

    public function test_has_security_schemes(): void
    {
        $schemes = $this->spec['components']['securitySchemes'];
        $this->assertArrayHasKey('bearerAuth', $schemes);
        $this->assertArrayHasKey('IdempotencyKey', $schemes);
    }

    public function test_has_all_request_schemas(): void
    {
        $schemas = $this->spec['components']['schemas'];
        $required = [
            'AgentRegisterRequest',
            'CashInOutRequest',
            'TransferRequest',
            'DepositRequest',
            'WithdrawRequest',
            'ReverseRequest',
            'FraudCheckRequest',
            'FraudMonitorRequest',
            'ResolveFraudRequest',
            'FxConvertRequest',
            'UpdateRateRequest',
            'ReviewCaseRequest',
            'EvaluateRuleRequest',
            'SanctionsCheckRequest',
            'AdjustFloatRequest',
            'SettleRequest',
        ];
        foreach ($required as $name) {
            $this->assertArrayHasKey($name, $schemas, "Missing schema: {$name}");
        }
    }

    public function test_has_all_response_schemas(): void
    {
        $schemas = $this->spec['components']['schemas'];
        $required = [
            'Agent',
            'AgentTransaction',
            'Settlement',
            'Transaction',
            'FraudDecision',
            'FraudRule',
            'FxTransaction',
            'ExchangeRate',
            'LedgerAccount',
            'JournalEntry',
            'JournalLine',
            'TrialBalanceReport',
            'DeadLetterEvent',
            'ErrorResponse',
            'ValidationErrorResponse',
            'PaginationMeta',
            'RiskAssessment',
            'CaseLifecycle',
            'FloatBalance',
        ];
        foreach ($required as $name) {
            $this->assertArrayHasKey($name, $schemas, "Missing schema: {$name}");
        }
    }

    public function test_has_standard_error_responses(): void
    {
        $responses = $this->spec['components']['responses'];
        $this->assertArrayHasKey('UnauthorizedError', $responses);
        $this->assertArrayHasKey('UnprocessableEntity', $responses);
        $this->assertArrayHasKey('NotFoundError', $responses);
    }

    public function test_every_path_has_operationId(): void
    {
        foreach ($this->spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $def) {
                $this->assertArrayHasKey(
                    'operationId',
                    $def,
                    "Missing operationId on {$method} {$path}"
                );
            }
        }
    }

    public function test_every_path_has_at_least_one_response(): void
    {
        foreach ($this->spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $def) {
                $this->assertNotEmpty(
                    $def['responses'],
                    "No responses on {$method} {$path}"
                );
            }
        }
    }
}
