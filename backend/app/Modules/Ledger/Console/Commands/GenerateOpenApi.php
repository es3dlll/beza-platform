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
                ['name' => 'Wallet', 'description' => 'Digital wallet management — المحفظة الرقمية والحدود'],
                ['name' => 'Remittance', 'description' => 'International remittances — التحويلات الدولية والامتثال العابر للحدود'],
                ['name' => 'Merchant', 'description' => 'Merchant & POS — نقاط البيع والفواتير والتسوية التجارية'],
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
            $this->walletPaths(),
            $this->remittancePaths(),
            $this->merchantPaths(),
            $this->compliancePaths(),
        );
    }

    private function agentPaths(): array
    {
        return [
            'v1/agents/onboard' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Onboard a new agent',
                    'description' => 'تسجيل وكيل جديد مع تفعيل المحفظة وتحديد مستوى العمولة',
                    'operationId' => 'onboardAgent',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/AgentRegisterRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Agent onboarded'],
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
                        '200' => ['description' => 'Agent details'],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/agents/{id}/float' => [
                'get' => [
                    'tags' => ['Agent'],
                    'summary' => 'Get agent float balance',
                    'description' => 'عرض السيولة الحالية والمعلقة والحد الأدنى',
                    'operationId' => 'getAgentFloat',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Agent ULID'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Float balance details'],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/agents/float/adjust' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Adjust agent float balance',
                    'description' => 'طلب تعديل حد السيولة مع سبب إجباري وسجل تدقيق',
                    'operationId' => 'adjustAgentFloat',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/AdjustFloatRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Float adjusted'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/agents/commissions' => [
                'get' => [
                    'tags' => ['Agent'],
                    'summary' => 'List agent commissions',
                    'description' => 'عرض تاريخ العمولات المحققة والنسبة المطبقة',
                    'operationId' => 'listAgentCommissions',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'agent_id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Agent ULID'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Commission details'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                    ],
                ],
            ],
            'v1/agents/settle' => [
                'post' => [
                    'tags' => ['Agent'],
                    'summary' => 'Trigger manual settlement',
                    'description' => 'طلب تسوية يدوية عاجلة للوكلاء المعتمدين',
                    'operationId' => 'settleAgent',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/SettleRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Settlements processed'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
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
                        '200' => ['description' => 'Agent verified'],
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
                        '201' => ['description' => 'Cash-in transaction created'],
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
                        '201' => ['description' => 'Cash-out transaction created'],
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
                        '200' => ['description' => 'Paginated agent transactions'],
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
                        '200' => ['description' => 'Paginated settlements'],
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
            $this->walletSchemas(),
            $this->remittanceSchemas(),
            $this->merchantSchemas(),
            $this->complianceSchemas(),
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
            'AdjustFloatRequest' => [
                'type' => 'object',
                'required' => ['agent_id', 'adjustment', 'reason'],
                'properties' => [
                    'agent_id' => ['type' => 'string', 'description' => 'Agent ULID'],
                    'adjustment' => ['type' => 'integer', 'description' => 'قيمة التعديل (سالب/موجب)'],
                    'reason' => ['type' => 'string', 'maxLength' => 500, 'description' => 'سبب التعديل (إجباري)'],
                ],
            ],
            'SettleRequest' => [
                'type' => 'object',
                'required' => ['agent_id'],
                'properties' => [
                    'agent_id' => ['type' => 'string', 'description' => 'Agent ULID'],
                    'settlement_date' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ التسوية (اختياري، الافتراضي اليوم)'],
                ],
            ],
            'FloatBalance' => [
                'type' => 'object',
                'properties' => [
                    'available' => ['type' => 'integer', 'description' => 'السيولة المتاحة'],
                    'pending' => ['type' => 'integer', 'description' => 'السيولة المعلقة'],
                    'minimum_required' => ['type' => 'integer', 'description' => 'الحد الأدنى الإلزامي'],
                    'daily_limit' => ['type' => 'integer'],
                    'daily_used' => ['type' => 'integer'],
                    'below_minimum' => ['type' => 'boolean'],
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

    private function walletPaths(): array
    {
        return [
            'v1/wallet/limits' => [
                'get' => [
                    'tags' => ['Wallet'],
                    'summary' => 'Get wallet limits',
                    'description' => 'الحدود الحالية للمحفظة — اليومية والشهرية والفردية',
                    'operationId' => 'getWalletLimits',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'tier', 'schema' => ['type' => 'string', 'enum' => ['T1', 'T2', 'T3'], 'default' => 'T1'], 'description' => 'KYC tier'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Wallet limits',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/WalletLimit'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                    ],
                ],
            ],
            'v1/wallet/limits/request-increase' => [
                'post' => [
                    'tags' => ['Wallet'],
                    'summary' => 'Request limit increase',
                    'description' => 'طلب زيادة حد المحفظة — يتطلب مراجعة إدارية',
                    'operationId' => 'requestLimitIncrease',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/LimitIncreaseRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '202' => [
                            'description' => 'Request submitted for review',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => ['type' => 'string', 'example' => 'تم استلام طلب زيادة الحد وهو قيد المراجعة'],
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
            'v1/wallet/balance' => [
                'get' => [
                    'tags' => ['Wallet'],
                    'summary' => 'Get wallet balance',
                    'description' => 'رصيد المحفظة — المستقر والمعلق والمتاح',
                    'operationId' => 'getWalletBalance',
                    'responses' => [
                        '200' => [
                            'description' => 'Wallet balance snapshot',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/BalanceSnapshot'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                    ],
                ],
            ],
        ];
    }

    private function remittancePaths(): array
    {
        return [
            'v1/remittance/quote' => [
                'post' => [
                    'tags' => ['Remittance'],
                    'summary' => 'Get remittance quote',
                    'description' => 'حساب سعر الصرف والرسوم مقدماً دون حجز رصيد',
                    'operationId' => 'getRemittanceQuote',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/RemittanceQuoteRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Quote calculated',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/RemittanceQuote'],
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
            'v1/remittance/initiate' => [
                'post' => [
                    'tags' => ['Remittance'],
                    'summary' => 'Initiate remittance transfer',
                    'description' => 'بدء تحويل دولي مع مفتاح التفرد Idempotency-Key',
                    'operationId' => 'initiateRemittance',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/InitiateRemittanceRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Remittance initiated',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/RemittanceResponse'],
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
            'v1/remittance/{id}/status' => [
                'get' => [
                    'tags' => ['Remittance'],
                    'summary' => 'Get remittance status',
                    'description' => 'تتبع لحظي لحالة التحويل والمراحل المنجزة',
                    'operationId' => 'getRemittanceStatus',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Remittance ID (REM-...)'],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Remittance status',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'data' => ['$ref' => '#/components/schemas/RemittanceStatusResponse'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/remittance/{id}/cancel' => [
                'post' => [
                    'tags' => ['Remittance'],
                    'summary' => 'Cancel remittance transfer',
                    'description' => 'إلغاء التحويل قبل بدء المعالجة — يعيد الأموال تلقائياً',
                    'operationId' => 'cancelRemittance',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Remittance ID (REM-...)'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CancelRemittanceRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Transfer cancelled',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => ['type' => 'string'],
                                            'data' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'remittance_id' => ['type' => 'string'],
                                                    'status' => ['type' => 'string', 'example' => 'CANCELLED'],
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
        ];
    }

    private function walletSchemas(): array
    {
        return [
            'WalletLimit' => [
                'type' => 'object',
                'properties' => [
                    'daily_max_syp' => ['type' => 'number', 'format' => 'float', 'description' => 'الحد اليومي بالليرة السورية'],
                    'monthly_max_syp' => ['type' => 'number', 'format' => 'float', 'description' => 'الحد الشهري بالليرة السورية'],
                    'single_max_syp' => ['type' => 'number', 'format' => 'float', 'description' => 'الحد الأقصى للمعاملة الواحدة'],
                    'daily_remaining' => ['type' => 'integer', 'description' => 'المتبقي من الحد اليومي'],
                    'monthly_remaining' => ['type' => 'integer', 'description' => 'المتبقي من الحد الشهري'],
                    'daily_used' => ['type' => 'integer', 'description' => 'المستخدم من الحد اليومي'],
                    'monthly_used' => ['type' => 'integer', 'description' => 'المستخدم من الحد الشهري'],
                ],
            ],
            'BalanceSnapshot' => [
                'type' => 'object',
                'properties' => [
                    'currency' => ['type' => 'string', 'enum' => ['SYP', 'USD'], 'description' => 'العملة'],
                    'settled' => ['type' => 'number', 'format' => 'float', 'description' => 'الرصيد المستقر'],
                    'pending' => ['type' => 'number', 'format' => 'float', 'description' => 'الرصيد المعلق'],
                    'available' => ['type' => 'number', 'format' => 'float', 'description' => 'الرصيد المتاح'],
                    'has_sufficient_funds' => ['type' => 'boolean'],
                ],
            ],
            'LimitIncreaseRequest' => [
                'type' => 'object',
                'required' => ['user_id', 'new_daily_limit', 'reason'],
                'properties' => [
                    'user_id' => ['type' => 'string', 'description' => 'ULID المستخدم'],
                    'new_daily_limit' => ['type' => 'integer', 'minimum' => 1000000, 'description' => 'الحد اليومي الجديد بوحدات صغرى'],
                    'reason' => ['type' => 'string', 'maxLength' => 500, 'description' => 'سبب طلب الزيادة'],
                ],
            ],
        ];
    }

    private function remittanceSchemas(): array
    {
        return [
            'RemittanceQuoteRequest' => [
                'type' => 'object',
                'required' => ['from_currency', 'to_currency', 'amount'],
                'properties' => [
                    'from_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD', 'EUR', 'SAR', 'AED'], 'description' => 'عملة المصدر'],
                    'to_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD', 'EUR', 'SAR', 'AED'], 'description' => 'عملة الوجهة'],
                    'amount' => ['type' => 'integer', 'minimum' => 100000, 'description' => 'المبلغ بوحدات صغرى'],
                ],
            ],
            'RemittanceQuote' => [
                'type' => 'object',
                'properties' => [
                    'from_currency' => ['type' => 'string'],
                    'to_currency' => ['type' => 'string'],
                    'source_amount' => ['type' => 'integer'],
                    'destination_amount' => ['type' => 'integer'],
                    'buy_rate' => ['type' => 'integer'],
                    'spread_bps' => ['type' => 'integer'],
                    'fee_amount' => ['type' => 'integer'],
                    'total_charge' => ['type' => 'integer'],
                    'quote_expires_in' => ['type' => 'integer'],
                ],
            ],
            'InitiateRemittanceRequest' => [
                'type' => 'object',
                'required' => ['idempotency_key', 'sender_id', 'recipient_name', 'recipient_phone', 'recipient_country', 'from_currency', 'to_currency', 'source_amount'],
                'properties' => [
                    'idempotency_key' => ['type' => 'string', 'maxLength' => 64, 'description' => 'مفتاح التفرد لمنع التكرار'],
                    'sender_id' => ['type' => 'string', 'description' => 'ULID المرسل'],
                    'recipient_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'اسم المستلم'],
                    'recipient_phone' => ['type' => 'string', 'description' => 'هاتف المستلم'],
                    'recipient_country' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 2, 'description' => 'دولة المستلم (ISO 3166-1 alpha-2)'],
                    'from_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD', 'EUR', 'SAR', 'AED']],
                    'to_currency' => ['type' => 'string', 'enum' => ['SYP', 'USD', 'EUR', 'SAR', 'AED']],
                    'source_amount' => ['type' => 'integer', 'minimum' => 100000],
                ],
            ],
            'RemittanceResponse' => [
                'type' => 'object',
                'properties' => [
                    'remittance_id' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['PENDING', 'FX_LOCKED', 'COMPLIANCE_CHECK', 'PROCESSING', 'SETTLED', 'REJECTED', 'CANCELLED', 'EXPIRED']],
                    'destination_amount' => ['type' => 'integer'],
                    'fee_amount' => ['type' => 'integer'],
                    'total_charge' => ['type' => 'integer'],
                    'expires_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
            'RemittanceStatusResponse' => [
                'type' => 'object',
                'properties' => [
                    'remittance_id' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'audit_trail' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'status' => ['type' => 'string'],
                                'at' => ['type' => 'string', 'format' => 'date-time'],
                            ],
                        ],
                    ],
                    'cancellation_reason' => ['type' => 'string', 'nullable' => true],
                    'completed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
            'CancelRemittanceRequest' => [
                'type' => 'object',
                'required' => ['reason'],
                'properties' => [
                    'reason' => ['type' => 'string', 'maxLength' => 500, 'description' => 'سبب الإلغاء'],
                ],
            ],
        ];
    }

    private function merchantPaths(): array
    {
        return [
            'v1/merchants/onboard' => [
                'post' => [
                    'tags' => ['Merchant'],
                    'summary' => 'Onboard a new merchant',
                    'description' => 'تسجيل تاجر جديد مع رفع المستندات الأساسية',
                    'operationId' => 'onboardMerchant',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/OnboardMerchantRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Merchant registered'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/merchants/invoices' => [
                'post' => [
                    'tags' => ['Merchant'],
                    'summary' => 'Create invoice',
                    'description' => 'إنشاء فاتورة جديدة مع رمز QR ديناميكي',
                    'operationId' => 'createInvoice',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CreateInvoiceRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Invoice created with QR token'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/merchants/invoices/{id}/qr' => [
                'get' => [
                    'tags' => ['Merchant'],
                    'summary' => 'Get invoice QR code',
                    'description' => 'استرجاع رمز QR صالح أو توليد جديد إذا منتهي',
                    'operationId' => 'getInvoiceQR',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Invoice ID (INV-...)'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'QR token'],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/merchants/invoices/{id}/pay' => [
                'post' => [
                    'tags' => ['Merchant'],
                    'summary' => 'Pay invoice',
                    'description' => 'تأكيد دفع فاتورة بواسطة رمز QR',
                    'operationId' => 'payInvoice',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Invoice ID (INV-...)'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/PayInvoiceRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Payment initiated'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/merchants/settlements' => [
                'get' => [
                    'tags' => ['Merchant'],
                    'summary' => 'List merchant settlements',
                    'description' => 'عرض تاريخ التسويات والعمولات والصافي',
                    'operationId' => 'listMerchantSettlements',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'merchant_id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Merchant ID (MER-...)'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Settlement history'],
                    ],
                ],
            ],
            'v1/merchants/invoices/{id}/refund' => [
                'post' => [
                    'tags' => ['Merchant'],
                    'summary' => 'Refund invoice',
                    'description' => 'طلب استرداد مع سبب إجباري',
                    'operationId' => 'refundInvoice',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Invoice ID (INV-...)'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/RefundRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Refund processed'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
        ];
    }

    private function compliancePaths(): array
    {
        return [
            'v1/compliance/alerts' => [
                'get' => [
                    'tags' => ['Compliance'],
                    'summary' => 'List active alerts',
                    'description' => 'عرض التنبيهات النشطة مع فلترة حسب الخطورة والتاريخ ونوع القاعدة',
                    'operationId' => 'listComplianceAlerts',
                    'parameters' => [
                        ['in' => 'query', 'name' => 'severity', 'schema' => ['type' => 'string', 'enum' => ['INFO', 'WARNING', 'HIGH', 'CRITICAL']], 'description' => 'فلترة حسب الخطورة'],
                        ['in' => 'query', 'name' => 'rule_id', 'schema' => ['type' => 'string'], 'description' => 'فلترة حسب القاعدة'],
                        ['in' => 'query', 'name' => 'status', 'schema' => ['type' => 'string'], 'description' => 'فلترة حسب الحالة'],
                        ['in' => 'query', 'name' => 'from', 'schema' => ['type' => 'string', 'format' => 'date-time'], 'description' => 'بداية التاريخ'],
                        ['in' => 'query', 'name' => 'to', 'schema' => ['type' => 'string', 'format' => 'date-time'], 'description' => 'نهاية التاريخ'],
                        ['in' => 'query', 'name' => 'per_page', 'schema' => ['type' => 'integer', 'default' => 15], 'description' => 'عدد النتائج لكل صفحة'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Alerts list (paginated)'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                    ],
                ],
            ],
            'v1/compliance/cases/{id}' => [
                'get' => [
                    'tags' => ['Compliance'],
                    'summary' => 'Get compliance case details',
                    'description' => 'عرض تفاصيل حالة رقابية مع سجل التقييمات والإجراءات',
                    'operationId' => 'getComplianceCase',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Case ID (CASE-...)'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Case details with audit trail and alerts'],
                        '404' => ['$ref' => '#/components/responses/NotFoundError'],
                    ],
                ],
            ],
            'v1/compliance/cases/{id}/review' => [
                'post' => [
                    'tags' => ['Compliance'],
                    'summary' => 'Review compliance case',
                    'description' => 'تسجيل قرار المراجعة مع سبب إجباري وتغيير حالة الحالة',
                    'operationId' => 'reviewComplianceCase',
                    'parameters' => [
                        ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Case ID (CASE-...)'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ReviewCaseRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Case reviewed'],
                        '401' => ['$ref' => '#/components/responses/UnauthorizedError'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/compliance/rules/evaluate' => [
                'post' => [
                    'tags' => ['Compliance'],
                    'summary' => 'Evaluate rule (manual test)',
                    'description' => 'محاكاة تقييم قاعدة على معاملة وهمية قبل تفعيلها في الإنتاج',
                    'operationId' => 'evaluateRule',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/EvaluateRuleRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Rule evaluation result'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
            'v1/compliance/sanctions/check' => [
                'post' => [
                    'tags' => ['Compliance'],
                    'summary' => 'Check sanctions list',
                    'description' => 'فحص سريع لاسم أو رقم هاتف أو جهاز ضد القائمة المحظورة',
                    'operationId' => 'checkSanctions',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/SanctionsCheckRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Sanctions check result'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                    ],
                ],
            ],
        ];
    }

    private function complianceSchemas(): array
    {
        return [
            'ReviewCaseRequest' => [
                'type' => 'object',
                'required' => ['resolution', 'reason'],
                'properties' => [
                    'resolution' => ['type' => 'string', 'enum' => ['RESOLVED_FALSE_POSITIVE', 'RESOLVED_TRUE_POSITIVE', 'ESCALATED', 'CLOSED'], 'description' => 'قرار المراجعة'],
                    'reason' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 2000, 'description' => 'سبب القرار (إجباري)'],
                ],
            ],
            'EvaluateRuleRequest' => [
                'type' => 'object',
                'required' => ['transaction_id', 'account_id', 'recipient_id', 'amount', 'currency'],
                'properties' => [
                    'transaction_id' => ['type' => 'string', 'description' => 'ULID المعاملة'],
                    'account_id' => ['type' => 'string', 'description' => 'ULID الحساب'],
                    'recipient_id' => ['type' => 'string', 'description' => 'ULID المستلم'],
                    'amount' => ['type' => 'integer', 'description' => 'المبلغ بوحدات صغرى'],
                    'currency' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 3, 'description' => 'رمز العملة ISO 4217'],
                    'device_fingerprint' => ['type' => 'string', 'description' => 'بصمة الجهاز'],
                    'country' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 2, 'description' => 'رمز الدولة ISO 3166-1 alpha-2'],
                    'daily_transaction_count' => ['type' => 'integer', 'description' => 'عدد المعاملات اليومية'],
                ],
            ],
            'SanctionsCheckRequest' => [
                'type' => 'object',
                'required' => ['name'],
                'properties' => [
                    'name' => ['type' => 'string', 'description' => 'الاسم المراد فحصه'],
                    'phone' => ['type' => 'string', 'description' => 'رقم الهاتف'],
                    'device_fingerprint' => ['type' => 'string', 'description' => 'بصمة الجهاز'],
                ],
            ],
            'RiskAssessment' => [
                'type' => 'object',
                'properties' => [
                    'risk_score' => ['type' => 'object', 'properties' => [
                        'score' => ['type' => 'integer', 'description' => 'درجة المخاطرة 0-100'],
                        'level' => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']],
                    ]],
                    'rule_details' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                        'rule_id' => ['type' => 'string'],
                        'score' => ['type' => 'integer'],
                        'action' => ['type' => 'string'],
                    ]]],
                    'requires_action' => ['type' => 'boolean'],
                    'requires_block' => ['type' => 'boolean'],
                ],
            ],
            'CaseLifecycle' => [
                'type' => 'object',
                'properties' => [
                    'case_id' => ['type' => 'string'],
                    'transaction_id' => ['type' => 'string', 'nullable' => true],
                    'account_id' => ['type' => 'string', 'nullable' => true],
                    'risk_score' => ['type' => 'integer'],
                    'status' => ['type' => 'string', 'enum' => ['OPEN', 'UNDER_REVIEW', 'ESCALATED', 'RESOLVED_FALSE_POSITIVE', 'RESOLVED_TRUE_POSITIVE', 'CLOSED']],
                    'severity' => ['type' => 'string'],
                    'triggered_rules' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'context' => ['type' => 'object'],
                    'reviewer_id' => ['type' => 'string', 'nullable' => true],
                    'reviewed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'resolution' => ['type' => 'string', 'nullable' => true],
                    'resolution_reason' => ['type' => 'string', 'nullable' => true],
                    'escalated_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'closed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ];
    }

    private function merchantSchemas(): array
    {
        return [
            'OnboardMerchantRequest' => [
                'type' => 'object',
                'required' => ['business_name', 'owner_id', 'phone'],
                'properties' => [
                    'business_name' => ['type' => 'string', 'maxLength' => 255, 'description' => 'الاسم التجاري'],
                    'owner_id' => ['type' => 'string', 'description' => 'ULID مالك التاجر'],
                    'phone' => ['type' => 'string', 'description' => 'هاتف التاجر'],
                    'category' => ['type' => 'string', 'enum' => ['goods_food', 'goods_general', 'goods_luxury', 'services_general', 'services_digital', 'services_financial']],
                    'settlement_cycle' => ['type' => 'string', 'enum' => ['DAILY', 'WEEKLY', 'INSTANT']],
                ],
            ],
            'CreateInvoiceRequest' => [
                'type' => 'object',
                'required' => ['merchant_id', 'amount', 'description'],
                'properties' => [
                    'merchant_id' => ['type' => 'string', 'description' => 'MER-...'],
                    'amount' => ['type' => 'integer', 'minimum' => 1000, 'description' => 'المبلغ بوحدات صغرى'],
                    'description' => ['type' => 'string', 'maxLength' => 500, 'description' => 'وصف الفاتورة'],
                    'category' => ['type' => 'string', 'description' => 'فئة السلعة أو الخدمة'],
                ],
            ],
            'PayInvoiceRequest' => [
                'type' => 'object',
                'required' => ['qr_token', 'payee_id'],
                'properties' => [
                    'qr_token' => ['type' => 'string', 'description' => 'رمز QR من الفاتورة'],
                    'payee_id' => ['type' => 'string', 'description' => 'ULID الدافع'],
                ],
            ],
            'RefundRequest' => [
                'type' => 'object',
                'required' => ['reason'],
                'properties' => [
                    'reason' => ['type' => 'string', 'maxLength' => 500, 'description' => 'سبب الاسترداد'],
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
