<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

final class GenerateOpenApi extends Command
{
    protected $signature = 'openapi:generate {--output=docs/specs/openapi.yaml}';
    protected $description = 'Generate unified OpenAPI specification from all module routes';

    public function handle(): int
    {
        $spec = $this->buildSpec();
        $yaml = Yaml::dump($spec, 8, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        $outputPath = base_path($this->option('output'));
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $yaml);
        $this->info("OpenAPI spec generated: {$outputPath}");

        return self::SUCCESS;
    }

    private function buildSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Beza Platform API',
                'description' => "مَنصة بَiza — API موحّد لفرق Flutter و USSD\nالمنصة المالية السورية رقم 1 للخدمات المالية الرقمية",
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Beza Platform',
                    'email' => 'api@beza-platform.com',
                ],
            ],
            'servers' => [
                ['url' => 'http://localhost:8000/api', 'description' => 'Local Development'],
            ],
            'paths' => $this->buildPaths(),
            'components' => [
                'securitySchemes' => $this->buildSecuritySchemes(),
                'schemas' => $this->buildSchemas(),
                'parameters' => $this->buildParameters(),
                'responses' => $this->buildResponses(),
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'tags' => [
                ['name' => 'Agent', 'description' => 'Agent management — تسجيل وإدارة الوكلاء'],
                ['name' => 'Financial Core', 'description' => 'Core financial engine — التحويلات والإيداع والسحب'],
                ['name' => 'Fraud', 'description' => 'Fraud detection and prevention — كشف الاحتيال'],
                ['name' => 'FX', 'description' => 'Foreign exchange — تحويل العملات وأسعار الصرف'],
                ['name' => 'Ledger', 'description' => 'General ledger — دفتر الأستاذ العام والتسوية'],
                ['name' => 'Event Bus', 'description' => 'Async event bus health and management — حالة ناقل الأحداث'],
            ],
        ];
    }

    private function buildSecuritySchemes(): array
    {
        return [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'JWT token issued at authentication',
            ],
            'IdempotencyKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-Idempotency-Key',
                'description' => 'Idempotency key for safe retries (ULID format recommended)',
            ],
        ];
    }

    private function buildParameters(): array
    {
        return [
            'IdempotencyKeyHeader' => [
                'in' => 'header',
                'name' => 'X-Idempotency-Key',
                'schema' => ['type' => 'string', 'example' => '01JQZABC123...'],
                'required' => true,
                'description' => 'Unique idempotency key (ULID)',
            ],
            'AcceptLanguageHeader' => [
                'in' => 'header',
                'name' => 'Accept-Language',
                'schema' => ['type' => 'string', 'enum' => ['ar', 'en'], 'default' => 'ar'],
                'required' => false,
                'description' => 'Response language',
            ],
            'WalletIdQuery' => [
                'in' => 'query',
                'name' => 'wallet_id',
                'schema' => ['type' => 'string'],
                'description' => 'Wallet ULID filter',
            ],
            'PerPageQuery' => [
                'in' => 'query',
                'name' => 'per_page',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 15],
                'description' => 'Items per page',
            ],
        ];
    }

    private function buildResponses(): array
    {
        return [
            'UnauthorizedError' => [
                'description' => 'Missing or invalid authentication',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                        'example' => ['message' => 'Unauthenticated', 'code' => 401],
                    ],
                ],
            ],
            'UnprocessableEntity' => [
                'description' => 'Validation error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ValidationErrorResponse'],
                        'example' => [
                            'message' => 'Validation failed',
                            'errors' => ['amount' => ['The amount field is required.']],
                        ],
                    ],
                ],
            ],
            'NotFoundError' => [
                'description' => 'Resource not found',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                        'example' => ['message' => 'Resource not found', 'code' => 404],
                    ],
                ],
            ],
        ];
    }

    private function buildPaths(): array
    {
        return array_merge(
            $this->agentPaths(),
            $this->financialCorePaths(),
            $this->fraudPaths(),
            $this->fxPaths(),
            $this->ledgerPaths(),
            $this->eventBusPaths(),
        );
    }

    private function agentPaths(): array
    {
        return [
            'v1/agents/register' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Register a new agent',
                    'description' => 'تسجيل وكيل جديد — requires admin authorization',
                    'operationId' => 'registerAgent',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/AgentRegisterRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Agent registered',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Agent'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/agents/{id}' => [
                'get' => [
                    'tags' => ['Agent'],
                    'summary' => 'Get agent details',
                    'description' => 'عرض بيانات الوكيل',
                    'operationId' => 'getAgent',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Agent ULID'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Agent details',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Agent'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/agents/{id}/verify' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Verify an agent (KYC)',
                    'description' => 'توثيق الوكيل بعد استكمال مستندات KYC',
                    'operationId' => 'verifyAgent',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Agent ULID'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Agent verified',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => ['type' => 'string', 'example' => 'Agent verified'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/agents/cash-in' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Agent cash-in to customer',
                    'description' => 'إيداع نقدي — يقوم الوكيل بإيداع أموال في محفظة العميل',
                    'operationId' => 'agentCashIn',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CashInOutRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Cash-in transaction created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/AgentTransaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/agents/cash-out' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Agent cash-out from customer',
                    'description' => 'سحب نقدي — يقوم الوكيل بسحب أموال من محفظة العميل',
                    'operationId' => 'agentCashOut',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CashInOutRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Cash-out transaction created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/AgentTransaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/agents/transactions' => [
                'get' => [
                    'tags' => ['Agent'],
                    'summary' => 'List agent transactions',
                    'description' => 'سجل معاملات الوكيل',
                    'operationId' => 'listAgentTransactions',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'agent_id', 'schema' => ['type' => 'string'], 'description' => 'Agent ULID filter'],
                        ['$ref' => '#/components/parameters/PerPageQuery'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated agent transactions',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/AgentTransaction'],
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/agents/settlements' => [
                'get' => [
                    'tags' => ['Agent'],
                    'summary' => 'List agent settlements',
                    'description' => 'سجل تسويات الوكيل',
                    'operationId' => 'listAgentSettlements',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'agent_id', 'schema' => ['type' => 'string'], 'description' => 'Agent ULID filter'],
                        ['$ref' => '#/components/parameters/PerPageQuery'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated settlements',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Settlement'],
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function financialCorePaths(): array
    {
        return [
            'v1/financial/transfer' => [
                'post' => [
                    'tags' => ['Financial Core'],
                    'summary' => 'Transfer between wallets',
                    'description' => 'تحويل أموال بين محفظتين — يستخدم X-Idempotency-Key لمنع التكرار',
                    'operationId' => 'transfer',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/TransferRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Transfer initiated',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Transaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/financial/deposit' => [
                'post' => [
                    'tags' => ['Financial Core'],
                    'summary' => 'Deposit to wallet',
                    'description' => 'إيداع في محفظة',
                    'operationId' => 'deposit',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/DepositRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Deposit created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Transaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/financial/withdraw' => [
                'post' => [
                    'tags' => ['Financial Core'],
                    'summary' => 'Withdraw from wallet',
                    'description' => 'سحب من محفظة',
                    'operationId' => 'withdraw',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/WithdrawRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Withdrawal created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Transaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/financial/{id}/reverse' => [
                'post' => [
                    'tags' => ['Financial Core'],
                    'summary' => 'Reverse a transaction',
                    'description' => 'عكس معاملة — ينشئ معاملة تعويضية',
                    'operationId' => 'reverseTransaction',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Transaction ULID to reverse'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ReverseRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Transaction reversed',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Transaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/financial/transactions' => [
                'get' => [
                    'tags' => ['Financial Core'],
                    'summary' => 'List financial transactions',
                    'description' => 'سجل المعاملات المالية',
                    'operationId' => 'listTransactions',
                    'parameters' => [
                        ['$ref' => '#/components/parameters/WalletIdQuery'],
                        ['$ref' => '#/components/parameters/PerPageQuery'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated transactions',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Transaction'],
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/financial/transactions/{id}' => [
                'get' => [
                    'tags' => ['Financial Core'],
                    'summary' => 'Get transaction details',
                    'description' => 'عرض تفاصيل معاملة',
                    'operationId' => 'getTransaction',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Transaction ULID'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Transaction details',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/Transaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
        ];
    }

    private function fraudPaths(): array
    {
        return [
            'v1/fraud/check' => [
                'post' => [
                    'tags' => ['Fraud'],
                    'summary' => 'Pre-check transaction for fraud',
                    'description' => 'فحص المعاملة قبل التنفيذ — يحلل السرعة والجهاز والتسجيل',
                    'operationId' => 'fraudPreCheck',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/FraudCheckRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Fraud check complete',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/FraudDecision'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '403' => [
                            'description' => 'Transaction blocked by fraud rules',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/fraud/monitor' => [
                'post' => [
                    'tags' => ['Fraud'],
                    'summary' => 'Post-transaction fraud monitoring',
                    'description' => 'مراقبة ما بعد المعاملة — تحديث درجة الثقة بعد التنفيذ الناجح',
                    'operationId' => 'fraudPostMonitor',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/FraudMonitorRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Monitoring updated',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => ['type' => 'string', 'example' => 'ok'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/fraud/decisions' => [
                'get' => [
                    'tags' => ['Fraud'],
                    'summary' => 'List fraud decisions',
                    'description' => 'سجل قرارات مكافحة الاحتيال',
                    'operationId' => 'listFraudDecisions',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'wallet_id', 'schema' => ['type' => 'string'], 'description' => 'Wallet ULID filter'],
                        ['$ref' => '#/components/parameters/PerPageQuery'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated fraud decisions',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/FraudDecision'],
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/fraud/rules' => [
                'get' => [
                    'tags' => ['Fraud'],
                    'summary' => 'List active fraud rules',
                    'description' => 'قواعد مكافحة الاحتيال النشطة',
                    'operationId' => 'listFraudRules',
                    'responses' => [
                        '200' => [
                            'description' => 'Active fraud rules',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/FraudRule'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/fraud/decisions/{id}/resolve' => [
                'post' => [
                    'tags' => ['Fraud'],
                    'summary' => 'Resolve a fraud decision',
                    'description' => 'حل قرار احتيال — تصنيف كاحتيال مؤكد أو إيجابي كاذب',
                    'operationId' => 'resolveFraudDecision',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Fraud decision ULID'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ResolveFraudRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Decision resolved',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/FraudDecision'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
        ];
    }

    private function fxPaths(): array
    {
        return [
            'v1/fx/convert' => [
                'post' => [
                    'tags' => ['FX'],
                    'summary' => 'Convert currency',
                    'description' => 'تحويل عملة — يقفل السعر، يطبق الفارق، ينفذ عبر CFE',
                    'operationId' => 'convertCurrency',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/FxConvertRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Conversion completed',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/FxTransaction'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/fx/rate' => [
                'get' => [
                    'tags' => ['FX'],
                    'summary' => 'Get current exchange rate',
                    'description' => 'سعر الصرف الحالي',
                    'operationId' => 'getExchangeRate',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'from_currency', 'required' => true, 'schema' => ['type' => 'string', 'enum' => ['SYP', 'USD']], 'description' => 'Base currency'],
                        ['in' => 'query', 'name' => 'to_currency', 'required' => true, 'schema' => ['type' => 'string', 'enum' => ['SYP', 'USD']], 'description' => 'Quote currency'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Current exchange rate',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/ExchangeRate'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
                'post' => [
                    'tags' => ['FX'],
                    'summary' => 'Update exchange rate (manual)',
                    'description' => 'تحديث سعر الصرف يدويًا',
                    'operationId' => 'updateExchangeRate',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/UpdateRateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Rate updated',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/ExchangeRate'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/fx/spread' => [
                'get' => [
                    'tags' => ['FX'],
                    'summary' => 'Calculate spread for amount',
                    'description' => 'حساب الفارق (السبريد) لمبلغ معين حسب الشريحة',
                    'operationId' => 'calculateSpread',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'amount', 'required' => true, 'schema' => ['type' => 'integer', 'minimum' => 1], 'description' => 'Amount in minor units'],
                        ['in' => 'query', 'name' => 'kyc_tier', 'required' => true, 'schema' => ['type' => 'string', 'enum' => ['t0', 't1', 't2', 't3']], 'description' => 'KYC tier'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Spread calculation',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'spread_bps' => ['type' => 'integer', 'description' => 'Spread in basis points'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/fx/history' => [
                'get' => [
                    'tags' => ['FX'],
                    'summary' => 'List FX conversion history',
                    'description' => 'سجل تحويلات العملات',
                    'operationId' => 'listFxHistory',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'wallet_id', 'schema' => ['type' => 'string'], 'description' => 'Wallet ULID filter'],
                        ['$ref' => '#/components/parameters/PerPageQuery'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated FX history',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/FxTransaction'],
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function ledgerPaths(): array
    {
        return [
            'v1/ledger/accounts' => [
                'get' => [
                    'tags' => ['Ledger'],
                    'summary' => 'List ledger accounts',
                    'description' => 'قائمة حسابات دفتر الأستاذ',
                    'operationId' => 'listLedgerAccounts',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'type', 'schema' => ['type' => 'string', 'enum' => ['asset', 'liability', 'equity', 'revenue', 'expense']], 'description' => 'Filter by account type'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'List of accounts',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/LedgerAccount'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/ledger/accounts/{id}' => [
                'get' => [
                    'tags' => ['Ledger'],
                    'summary' => 'Get ledger account details',
                    'description' => 'تفاصيل حساب دفتر الأستاذ',
                    'operationId' => 'getLedgerAccount',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Account ULID or code'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Account details',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/LedgerAccount'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/ledger/journal' => [
                'get' => [
                    'tags' => ['Ledger'],
                    'summary' => 'List journal entries',
                    'description' => 'سجل القيود اليومية مع سلسلة التجزئة',
                    'operationId' => 'listJournalEntries',
                    'parameters' => [
                        ['$ref' => '#/components/parameters/PerPageQuery'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated journal entries',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/JournalEntry'],
                                            ],
                                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/ledger/journal/{id}' => [
                'get' => [
                    'tags' => ['Ledger'],
                    'summary' => 'Get journal entry details',
                    'description' => 'تفاصيل قيد يومي مع السطور',
                    'operationId' => 'getJournalEntry',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Journal entry ULID'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Journal entry with lines',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/JournalEntry'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/ledger/trial-balance' => [
                'get' => [
                    'tags' => ['Ledger'],
                    'summary' => 'Get trial balance',
                    'description' => 'ميزان المراجعة — ملخص أرصدة الحسابات',
                    'operationId' => 'getTrialBalance',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'currency', 'schema' => ['type' => 'string', 'enum' => ['SYP', 'USD']], 'description' => 'Filter by currency'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Trial balance',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/TrialBalanceReport'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'v1/ledger/verify-chain' => [
                'get' => [
                    'tags' => ['Ledger'],
                    'summary' => 'Verify hash chain integrity',
                    'description' => 'التحقق من سلامة سلسلة التجزئة',
                    'operationId' => 'verifyHashChain',
                    'responses' => [
                        '200' => [
                            'description' => 'Chain verification result',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'valid' => ['type' => 'boolean'],
                                                    'entries_checked' => ['type' => 'integer'],
                                                    'first_entry_id' => ['type' => 'string', 'nullable' => true],
                                                    'last_entry_id' => ['type' => 'string', 'nullable' => true],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function eventBusPaths(): array
    {
        return [
            'api/event-bus/health' => [
                'get' => [
                    'tags' => ['Event Bus'],
                    'summary' => 'Event bus health check',
                    'description' => 'فحص صحة ناقل الأحداث',
                    'operationId' => 'eventBusHealth',
                    'responses' => [
                        '200' => [
                            'description' => 'Health status',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'string', 'example' => 'healthy'],
                                            'consumers' => ['type' => 'integer'],
                                            'poison_pills' => ['type' => 'integer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'api/event-bus/dead-letters' => [
                'get' => [
                    'tags' => ['Event Bus'],
                    'summary' => 'List dead letter events',
                    'description' => 'الرسائل الميتة (فشلت بعد أقصى عدد محاولات)',
                    'operationId' => 'listDeadLetters',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'status', 'schema' => ['type' => 'string', 'enum' => ['pending', 'retrying', 'resolved']], 'description' => 'Filter by status'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Dead letters with stats',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/DeadLetterEvent'],
                                            ],
                                            'stats' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'total' => ['type' => 'integer'],
                                                    'pending' => ['type' => 'integer'],
                                                    'retrying' => ['type' => 'integer'],
                                                    'resolved' => ['type' => 'integer'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'api/event-bus/dead-letters/{id}/retry' => [
                'post' => [
                    'tags' => ['Event Bus'],
                    'summary' => 'Retry a dead letter event',
                    'description' => 'إعادة محاولة معالجة رسالة ميتة',
                    'operationId' => 'retryDeadLetter',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Dead letter event ULID'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Retry queued',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => ['type' => 'string', 'example' => 'Dead letter event queued for retry'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
        ];
    }

    private function buildSchemas(): array
    {
        return array_merge(
            $this->commonSchemas(),
            $this->agentSchemas(),
            $this->financialCoreSchemas(),
            $this->fraudSchemas(),
            $this->fxSchemas(),
            $this->ledgerSchemas(),
            $this->eventBusSchemas(),
        );
    }

    private function commonSchemas(): array
    {
        return [
            'ErrorResponse' => [
                'type' => 'object',
                'required' => ['message'],
                'properties' => [
                    'message' => ['type' => 'string'],
                    'code' => ['type' => 'integer', 'nullable' => true],
                ],
            ],
            'ValidationErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'errors' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                ],
            ],
        ];
    }

    private function agentSchemas(): array
    {
        return [
            'Agent' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'user_id' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'name_ar' => ['type' => 'string'],
                    'kyc_tier' => ['type' => 'string', 'enum' => ['t0', 't1', 't2', 't3']],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'active', 'suspended', 'terminated']],
                    'id_type' => ['type' => 'string', 'nullable' => true],
                    'id_number' => ['type' => 'string', 'nullable' => true],
                    'is_verified' => ['type' => 'boolean'],
                    'verified_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'gps_lat' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                    'gps_lng' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                    'address' => ['type' => 'string', 'nullable' => true],
                    'address_ar' => ['type' => 'string', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                    'deleted_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
            'AgentRegisterRequest' => [
                'type' => 'object',
                'required' => ['user_id', 'phone', 'name', 'name_ar'],
                'properties' => [
                    'user_id' => ['type' => 'string', 'description' => 'User ULID'],
                    'phone' => ['type' => 'string', 'example' => '9639XXXXXXXX'],
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'name_ar' => ['type' => 'string', 'maxLength' => 255],
                    'id_type' => ['type' => 'string', 'nullable' => true],
                    'id_number' => ['type' => 'string', 'nullable' => true],
                    'gps_lat' => ['type' => 'number', 'format' => 'float', 'minimum' => -90, 'maximum' => 90, 'nullable' => true],
                    'gps_lng' => ['type' => 'number', 'format' => 'float', 'minimum' => -180, 'maximum' => 180, 'nullable' => true],
                    'address' => ['type' => 'string', 'nullable' => true],
                    'address_ar' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'CashInOutRequest' => [
                'type' => 'object',
                'required' => ['agent_id', 'customer_wallet_id', 'amount', 'currency', 'idempotency_key', 'customer_phone', 'customer_name'],
                'properties' => [
                    'agent_id' => ['type' => 'string', 'description' => 'Agent ULID'],
                    'customer_wallet_id' => ['type' => 'string', 'description' => 'Customer wallet ULID'],
                    'amount' => ['type' => 'integer', 'minimum' => 100, 'description' => 'Amount in minor units (e.g., 100000 = 1000 SYP)'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'idempotency_key' => ['type' => 'string', 'description' => 'Unique idempotency key'],
                    'customer_phone' => ['type' => 'string'],
                    'customer_name' => ['type' => 'string'],
                    'location_lat' => ['type' => 'number', 'format' => 'float', 'minimum' => -90, 'maximum' => 90, 'nullable' => true],
                    'location_lng' => ['type' => 'number', 'format' => 'float', 'minimum' => -180, 'maximum' => 180, 'nullable' => true],
                ],
            ],
            'AgentTransaction' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'agent_id' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['cash_in', 'cash_out']],
                    'status' => ['type' => 'string', 'enum' => ['initiated', 'held', 'posted', 'reversed', 'failed']],
                    'customer_wallet_id' => ['type' => 'string', 'nullable' => true],
                    'customer_phone' => ['type' => 'string'],
                    'customer_name' => ['type' => 'string'],
                    'amount' => ['type' => 'integer'],
                    'currency' => ['type' => 'string'],
                    'fee' => ['type' => 'integer'],
                    'commission_amount' => ['type' => 'integer'],
                    'commission_rate_bps' => ['type' => 'integer'],
                    'settlement_date' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'idempotency_key' => ['type' => 'string'],
                    'transaction_id' => ['type' => 'string', 'nullable' => true],
                    'location_lat' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                    'location_lng' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Settlement' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'agent_id' => ['type' => 'string'],
                    'settlement_date' => ['type' => 'string', 'format' => 'date'],
                    'expected_amount' => ['type' => 'integer'],
                    'actual_amount' => ['type' => 'integer', 'nullable' => true],
                    'difference' => ['type' => 'integer'],
                    'commission_amount' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'balanced', 'discrepancy', 'resolved']],
                    'notes' => ['type' => 'string', 'nullable' => true],
                    'resolved_by' => ['type' => 'string', 'nullable' => true],
                    'settled_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ];
    }

    private function financialCoreSchemas(): array
    {
        return [
            'Transaction' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'type' => ['type' => 'string', 'enum' => ['transfer', 'deposit', 'withdraw', 'reversal', 'settlement', 'fee']],
                    'status' => ['type' => 'string', 'enum' => ['initiated', 'held', 'posted', 'settled', 'reversed', 'failed']],
                    'wallet_id' => ['type' => 'string', 'nullable' => true],
                    'from_account_id' => ['type' => 'string', 'nullable' => true],
                    'to_account_id' => ['type' => 'string', 'nullable' => true],
                    'amount' => ['type' => 'integer'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'fee_amount' => ['type' => 'integer'],
                    'fee_account_id' => ['type' => 'string', 'nullable' => true],
                    'fee_basis_points' => ['type' => 'integer'],
                    'idempotency_key' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'description_ar' => ['type' => 'string', 'nullable' => true],
                    'metadata' => ['type' => 'object', 'nullable' => true],
                    'reversal_of' => ['type' => 'string', 'nullable' => true],
                    'journal_entry_id' => ['type' => 'string', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'TransferRequest' => [
                'type' => 'object',
                'required' => ['from_wallet_id', 'to_wallet_id', 'amount', 'currency', 'idempotency_key'],
                'properties' => [
                    'from_wallet_id' => ['type' => 'string', 'description' => 'Source wallet ULID'],
                    'to_wallet_id' => ['type' => 'string', 'description' => 'Destination wallet ULID'],
                    'amount' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Amount in minor units'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'idempotency_key' => ['type' => 'string'],
                    'fee' => [
                        'type' => 'object',
                        'properties' => [
                            'rule_id' => ['type' => 'string', 'description' => 'Fee rule ULID (required_without)'],
                            'description' => ['type' => 'string', 'nullable' => true],
                            'description_ar' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                ],
            ],
            'DepositRequest' => [
                'type' => 'object',
                'required' => ['wallet_id', 'amount', 'currency', 'idempotency_key'],
                'properties' => [
                    'wallet_id' => ['type' => 'string', 'description' => 'Wallet ULID'],
                    'amount' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Amount in minor units'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'idempotency_key' => ['type' => 'string'],
                ],
            ],
            'WithdrawRequest' => [
                'type' => 'object',
                'required' => ['wallet_id', 'amount', 'currency', 'idempotency_key'],
                'properties' => [
                    'wallet_id' => ['type' => 'string', 'description' => 'Wallet ULID'],
                    'amount' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Amount in minor units'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'idempotency_key' => ['type' => 'string'],
                ],
            ],
            'ReverseRequest' => [
                'type' => 'object',
                'required' => ['idempotency_key'],
                'properties' => [
                    'reason' => ['type' => 'string', 'maxLength' => 500, 'nullable' => true],
                    'reason_ar' => ['type' => 'string', 'maxLength' => 500, 'nullable' => true],
                    'idempotency_key' => ['type' => 'string'],
                ],
            ],
        ];
    }

    private function fraudSchemas(): array
    {
        return [
            'FraudCheckRequest' => [
                'type' => 'object',
                'required' => ['wallet_id', 'amount', 'kyc_tier'],
                'properties' => [
                    'wallet_id' => ['type' => 'string'],
                    'amount' => ['type' => 'integer', 'minimum' => 1],
                    'device_data' => [
                        'type' => 'object',
                        'properties' => [
                            'user_agent' => ['type' => 'string', 'nullable' => true],
                            'ip_address' => ['type' => 'string', 'nullable' => true],
                            'device_type' => ['type' => 'string', 'nullable' => true],
                            'app_version' => ['type' => 'string', 'nullable' => true],
                            'os' => ['type' => 'string', 'nullable' => true],
                            'screen_resolution' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'kyc_tier' => ['type' => 'string', 'enum' => ['t0', 't1', 't2', 't3']],
                ],
            ],
            'FraudMonitorRequest' => [
                'type' => 'object',
                'required' => ['wallet_id', 'transaction_id', 'amount', 'kyc_tier'],
                'properties' => [
                    'wallet_id' => ['type' => 'string'],
                    'transaction_id' => ['type' => 'string'],
                    'amount' => ['type' => 'integer', 'minimum' => 1],
                    'device_data' => [
                        'type' => 'object',
                        'properties' => [
                            'user_agent' => ['type' => 'string', 'nullable' => true],
                            'ip_address' => ['type' => 'string', 'nullable' => true],
                            'device_type' => ['type' => 'string', 'nullable' => true],
                            'app_version' => ['type' => 'string', 'nullable' => true],
                            'os' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'kyc_tier' => ['type' => 'string', 'enum' => ['t0', 't1', 't2', 't3']],
                ],
            ],
            'FraudDecision' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'wallet_id' => ['type' => 'string'],
                    'rule_id' => ['type' => 'string', 'nullable' => true],
                    'device_fingerprint_id' => ['type' => 'string', 'nullable' => true],
                    'action' => ['type' => 'string', 'enum' => ['allow', 'flag', 'hold', 'block']],
                    'score_before' => ['type' => 'integer'],
                    'score_after' => ['type' => 'integer'],
                    'score_impact' => ['type' => 'integer'],
                    'reason' => ['type' => 'string', 'nullable' => true],
                    'reason_ar' => ['type' => 'string', 'nullable' => true],
                    'context_type' => ['type' => 'string', 'nullable' => true],
                    'context_id' => ['type' => 'string', 'nullable' => true],
                    'reference_id' => ['type' => 'string', 'nullable' => true],
                    'resolved_by' => ['type' => 'string', 'nullable' => true],
                    'resolution' => ['type' => 'string', 'enum' => ['confirmed_fraud', 'false_positive', 'overridden'], 'nullable' => true],
                    'resolved_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'FraudRule' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'name' => ['type' => 'string'],
                    'name_ar' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['velocity', 'device', 'scoring', 'geography']],
                    'category' => ['type' => 'string'],
                    'action' => ['type' => 'string', 'enum' => ['allow', 'flag', 'hold', 'block']],
                    'scope' => ['type' => 'string'],
                    'metric' => ['type' => 'string'],
                    'threshold' => ['type' => 'integer'],
                    'score_impact' => ['type' => 'integer'],
                    'kyc_tier_min' => ['type' => 'string', 'enum' => ['t0', 't1', 't2', 't3']],
                    'priority' => ['type' => 'integer'],
                    'time_window_minutes' => ['type' => 'integer', 'nullable' => true],
                    'is_active' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'ResolveFraudRequest' => [
                'type' => 'object',
                'required' => ['resolution', 'resolved_by'],
                'properties' => [
                    'resolution' => ['type' => 'string', 'enum' => ['confirmed_fraud', 'false_positive', 'overridden']],
                    'resolved_by' => ['type' => 'string'],
                ],
            ],
        ];
    }

    private function fxSchemas(): array
    {
        return [
            'FxConvertRequest' => [
                'type' => 'object',
                'required' => ['wallet_id', 'amount', 'from_currency', 'to_currency', 'kyc_tier', 'idempotency_key'],
                'properties' => [
                    'wallet_id' => ['type' => 'string'],
                    'amount' => ['type' => 'integer', 'minimum' => 100],
                    'from_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'to_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'kyc_tier' => ['type' => 'string', 'enum' => ['t0', 't1', 't2', 't3']],
                    'idempotency_key' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'description_ar' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'FxTransaction' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'wallet_id' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['conversion', 'reversal']],
                    'status' => ['type' => 'string', 'enum' => ['initiated', 'held', 'completed', 'failed']],
                    'base_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'quote_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'debit_amount' => ['type' => 'integer'],
                    'credit_amount' => ['type' => 'integer'],
                    'rate_used' => ['type' => 'integer', 'description' => 'Rate in basis points (e.g., 25000 = 250.00)'],
                    'spread_bps_applied' => ['type' => 'integer'],
                    'rate_source_id' => ['type' => 'string', 'nullable' => true],
                    'fx_hold_id' => ['type' => 'string', 'nullable' => true],
                    'cfe_transaction_id' => ['type' => 'string', 'nullable' => true],
                    'reversal_of' => ['type' => 'string', 'nullable' => true],
                    'idempotency_key' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'description_ar' => ['type' => 'string', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'ExchangeRate' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'rate_source_id' => ['type' => 'string'],
                    'base_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'quote_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'buy_rate' => ['type' => 'integer'],
                    'sell_rate' => ['type' => 'integer'],
                    'spread_bps' => ['type' => 'integer'],
                    'valid_from' => ['type' => 'string', 'format' => 'date-time'],
                    'valid_until' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'UpdateRateRequest' => [
                'type' => 'object',
                'required' => ['from_currency', 'to_currency', 'buy_rate', 'sell_rate'],
                'properties' => [
                    'from_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'to_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'buy_rate' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Buy rate in basis points'],
                    'sell_rate' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Sell rate in basis points'],
                    'spread_bps' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Spread in basis points'],
                    'ttl_minutes' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 1440, 'description' => 'Rate validity duration'],
                ],
            ],
        ];
    }

    private function ledgerSchemas(): array
    {
        return [
            'LedgerAccount' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'code' => ['type' => 'string', 'description' => 'Account code (e.g., 1100)'],
                    'name' => ['type' => 'string'],
                    'name_ar' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['asset', 'liability', 'equity', 'revenue', 'expense']],
                    'balance' => ['type' => 'integer', 'description' => 'Current balance in minor units'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'is_system' => ['type' => 'boolean'],
                    'metadata' => ['type' => 'object', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'JournalEntry' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'transaction_id' => ['type' => 'string', 'nullable' => true],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'description_ar' => ['type' => 'string', 'nullable' => true],
                    'previous_hash' => ['type' => 'string', 'description' => 'SHA-256 of previous entry'],
                    'hash' => ['type' => 'string', 'description' => 'SHA-256 of this entry'],
                    'metadata' => ['type' => 'object', 'nullable' => true],
                    'lines' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/JournalLine'],
                    ],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'JournalLine' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'journal_entry_id' => ['type' => 'string'],
                    'account_id' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['debit', 'credit']],
                    'amount' => ['type' => 'integer'],
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD']],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'account' => ['$ref' => '#/components/schemas/LedgerAccount'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'TrialBalanceReport' => [
                'type' => 'object',
                'properties' => [
                    'accounts' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'code' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'name_ar' => ['type' => 'string'],
                                'type' => ['type' => 'string'],
                                'debit' => ['type' => 'integer'],
                                'credit' => ['type' => 'integer'],
                                'balance' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                    'totals' => [
                        'type' => 'object',
                        'properties' => [
                            'total_debit' => ['type' => 'integer'],
                            'total_credit' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function eventBusSchemas(): array
    {
        return [
            'DeadLetterEvent' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string', 'description' => 'ULID'],
                    'event_id' => ['type' => 'string'],
                    'event_type' => ['type' => 'string'],
                    'consumer_name' => ['type' => 'string'],
                    'payload' => ['type' => 'object'],
                    'headers' => ['type' => 'object'],
                    'error_message' => ['type' => 'string', 'nullable' => true],
                    'error_trace' => ['type' => 'string', 'nullable' => true],
                    'attempts' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'retrying', 'resolved']],
                    'failed_at' => ['type' => 'string', 'format' => 'date-time'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ];
    }
}
