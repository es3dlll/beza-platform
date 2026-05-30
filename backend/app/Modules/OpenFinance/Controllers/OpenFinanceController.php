<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OpenFinance\Services\OpenFinanceService;
use Modules\OpenFinance\Exceptions\AppNotFoundException;
use Modules\OpenFinance\Exceptions\ConsentExpiredException;
use Modules\OpenFinance\Exceptions\InvalidScopeException;

final class OpenFinanceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OpenFinanceService $service) {}

    public function registerApp(Request $request): JsonResponse
    {
        $request->validate(['name'=>'required|string|max:100','redirect_uris'=>'required|string|max:500','scopes'=>'required|array','scopes.*'=>'string']);
        try {
            return $this->respondCreated($this->service->registerApp($request->user()->id, $request->input('name'), $request->input('redirect_uris'), $request->input('scopes')));
        } catch (InvalidScopeException $e) {
            return $this->respondError('INVALID_SCOPE', $e->getMessage(), null, 422);
        }
    }

    public function createConsent(Request $request): JsonResponse
    {
        $request->validate(['app_id'=>'required|string|size:26','scopes'=>'required|array','scopes.*'=>'string','ttl_days'=>'nullable|integer|min:1|max:365']);
        return $this->respondCreated($this->service->createConsent($request->user()->id, $request->input('app_id'), $request->input('scopes'), $request->integer('ttl_days', 90)));
    }

    public function generateToken(Request $request): JsonResponse
    {
        $request->validate(['consent_id'=>'required|string|size:26']);
        try {
            return $this->respond($this->service->generateToken($request->input('consent_id')));
        } catch (ConsentExpiredException $e) {
            return $this->respondError('CONSENT_EXPIRED', null, null, 422);
        }
    }

    public function revokeConsent(string $id): JsonResponse
    {
        $this->service->revokeConsent($id);
        return $this->respond(null, 'Consent revoked');
    }

    public function listApps(Request $request): JsonResponse { return $this->respond($this->service->listApps($request->user()->id)); }

    public function listConsents(Request $request): JsonResponse { return $this->respond($this->service->listConsents($request->user()->id)); }

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

        return $this->respondCreated($payment);
    }

    // ─── V3: Account Information ───

    public function listAccounts(Request $request): JsonResponse
    {
        return $this->respond($this->service->listAccounts($request->user()->id));
    }

    public function accountTransactions(Request $request, string $accountId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        return $this->respond($this->service->accountTransactions($accountId, $perPage));
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
        return $this->respondCreated($wallet);
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
        return $this->respondCreated($this->service->registerWebhook($request->input('app_id'), $request->input('url'), $request->input('events')));
    }

    public function listWebhooks(Request $request): JsonResponse
    {
        return $this->respond($this->service->listWebhooks($request->input('app_id')));
    }

    public function webhookDeliveries(Request $request, string $webhookId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);
        return $this->respond($this->service->listWebhookDeliveries($webhookId, $perPage));
    }

    // ─── V3: Developer Tier ───

    public function myTier(Request $request): JsonResponse
    {
        $tier = $this->service->getDeveloperTier($request->user()->id);
        $rateLimit = $this->service->getRateLimit($tier);
        return $this->respond(['tier' => $tier, 'rate_limit_rps' => $rateLimit]);
    }

    public function myPayments(Request $request): JsonResponse
    {
        return $this->respond($this->service->listPayments($request->user()->id));
    }

    // ─── V3: Sandbox helpers ───

    public function sandboxMode(Request $request): JsonResponse
    {
        $isSandbox = $request->header('X-Sandbox', 'false') === 'true';
        return $this->respond(['sandbox' => $isSandbox, 'message' => $isSandbox ? 'Using sandbox environment — no real funds' : 'Production environment']);
    }
}
