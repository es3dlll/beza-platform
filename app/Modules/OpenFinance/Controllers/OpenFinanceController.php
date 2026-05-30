<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OpenFinance\Services\OpenFinanceService;

class OpenFinanceController extends Controller
{
    public function __construct(private readonly OpenFinanceService $service) {}

    public function registerApp(Request $request): JsonResponse
    {
        $request->validate(['name'=>'required|string|max:100','redirect_uris'=>'required|string|max:500','scopes'=>'required|array','scopes.*'=>'string']);
        return response()->json(['data' => $this->service->registerApp($request->user()->id, $request->input('name'), $request->input('redirect_uris'), $request->input('scopes'))], 201);
    }

    public function createConsent(Request $request): JsonResponse
    {
        $request->validate(['app_id'=>'required|string|size:26','scopes'=>'required|array','scopes.*'=>'string','ttl_days'=>'nullable|integer|min:1|max:365']);
        return response()->json(['data' => $this->service->createConsent($request->user()->id, $request->input('app_id'), $request->input('scopes'), $request->integer('ttl_days', 90))], 201);
    }

    public function generateToken(Request $request): JsonResponse
    {
        $request->validate(['consent_id'=>'required|string|size:26']);
        return response()->json(['data' => $this->service->generateToken($request->input('consent_id'))]);
    }

    public function revokeConsent(string $id): JsonResponse
    {
        $this->service->revokeConsent($id);
        return response()->json(['message' => 'Consent revoked']);
    }

    public function listApps(Request $request): JsonResponse { return response()->json(['data' => $this->service->listApps($request->user()->id)]); }

    public function listConsents(Request $request): JsonResponse { return response()->json(['data' => $this->service->listConsents($request->user()->id)]); }
}
