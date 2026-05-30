<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OpenFinance\Services\OpenFinanceService;
use Modules\OpenFinance\Exceptions\AppNotFoundException;
use Modules\OpenFinance\Exceptions\ConsentExpiredException;
use Modules\OpenFinance\Exceptions\InvalidScopeException;

class OpenFinanceController extends Controller
{
    public function __construct(private readonly OpenFinanceService $service) {}

    public function registerApp(Request $request): JsonResponse
    {
        $request->validate(['name'=>'required|string|max:100','redirect_uris'=>'required|string|max:500','scopes'=>'required|array','scopes.*'=>'string']);
        try {
            return response()->json(['data' => $this->service->registerApp($request->user()->id, $request->input('name'), $request->input('redirect_uris'), $request->input('scopes'))], 201);
        } catch (InvalidScopeException $e) {
            return response()->json(['error' => 'INVALID_SCOPE', 'reason' => $e->getMessage()], 422);
        }
    }

    public function createConsent(Request $request): JsonResponse
    {
        $request->validate(['app_id'=>'required|string|size:26','scopes'=>'required|array','scopes.*'=>'string','ttl_days'=>'nullable|integer|min:1|max:365']);
        return response()->json(['data' => $this->service->createConsent($request->user()->id, $request->input('app_id'), $request->input('scopes'), $request->integer('ttl_days', 90))], 201);
    }

    public function generateToken(Request $request): JsonResponse
    {
        $request->validate(['consent_id'=>'required|string|size:26']);
        try {
            return response()->json(['data' => $this->service->generateToken($request->input('consent_id'))]);
        } catch (ConsentExpiredException $e) {
            return response()->json(['error' => 'CONSENT_EXPIRED'], 422);
        }
    }

    public function revokeConsent(string $id): JsonResponse
    {
        $this->service->revokeConsent($id);
        return response()->json(['message' => 'Consent revoked']);
    }

    public function listApps(Request $request): JsonResponse { return response()->json(['data' => $this->service->listApps($request->user()->id)]); }

    public function listConsents(Request $request): JsonResponse { return response()->json(['data' => $this->service->listConsents($request->user()->id)]); }

    // ─── V3: Payment Initiation ───

    public function initiatePayment(Request $request): JsonResponse
    {
        $request->validate([
            'consent_id' => 'required|string|size:26',
            'payment_type' => 'required|in:p2p,bulk,merchant',
            'recipient_id' => 'required|string|max:50',
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $payment = $this->service->initiatePayment(
            $request->input('consent_id'),
            $request->user()->id,
            $request->input('payment_type'),
            $request->input('recipient_id'),
            $request->integer('amount'),
            $request->input('description'),
            $request->input('idempotency_key'),
        );

        return response()->json(['data' => $payment], 201);
    }

    // ─── V3: Account Information ───

    public function listAccounts(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listAccounts($request->user()->id)]);
    }

    public function accountTransactions(Request $request, string $accountId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        return response()->json(['data' => $this->service->accountTransactions($accountId, $perPage)]);
    }

    // ─── V3: Wallet API ───

    public function createWallet(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100', 'currency' => 'nullable|string|size:3']);
        $wallet = $this->service->createWallet(
            $request->user()->id,
            $request->input('name'),
            $request->input('currency', 'SYP'),
        );
        return response()->json(['data' => $wallet], 201);
    }

    // ─── V3: Webhooks ───

    public function registerWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'app_id' => 'required|string|size:26',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);
        return response()->json(['data' => $this->service->registerWebhook($request->input('app_id'), $request->input('url'), $request->input('events'))], 201);
    }

    public function listWebhooks(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listWebhooks($request->input('app_id'))]);
    }

    public function webhookDeliveries(Request $request, string $webhookId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);
        return response()->json(['data' => $this->service->listWebhookDeliveries($webhookId, $perPage)]);
    }

    // ─── V3: Developer Tier ───

    public function myTier(Request $request): JsonResponse
    {
        $tier = $this->service->getDeveloperTier($request->user()->id);
        $rateLimit = $this->service->getRateLimit($tier);
        return response()->json(['data' => ['tier' => $tier, 'rate_limit_rps' => $rateLimit]]);
    }

    public function myPayments(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listPayments($request->user()->id)]);
    }

    // ─── V3: Sandbox helpers ───

    public function sandboxMode(Request $request): JsonResponse
    {
        $isSandbox = $request->header('X-Sandbox', 'false') === 'true';
        return response()->json(['data' => ['sandbox' => $isSandbox, 'message' => $isSandbox ? 'Using sandbox environment — no real funds' : 'Production environment']]);
    }
}
