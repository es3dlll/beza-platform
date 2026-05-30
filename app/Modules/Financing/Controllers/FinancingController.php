<?php

declare(strict_types=1);

namespace Modules\Financing\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Financing\Services\FinancingService;

class FinancingController extends Controller
{
    public function __construct(private readonly FinancingService $service) {}

    public function products(): JsonResponse { return response()->json(['data' => $this->service->listProducts()]); }

    public function apply(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|string|size:26','amount' => 'required|integer|min:1000','term_days' => 'required|integer|min:7|max:365','purpose' => 'nullable|string|max:255']);
        return response()->json(['data' => $this->service->apply($request->user()->id, $request->input('product_id'), $request->integer('amount'), $request->integer('term_days'), $request->input('purpose'))], 201);
    }

    public function approve(string $id): JsonResponse { return response()->json(['data' => $this->service->approve($id)]); }

    public function disburse(string $id): JsonResponse { return response()->json(['data' => $this->service->disburse($id)]); }

    public function repay(Request $request, string $id): JsonResponse
    {
        $request->validate(['amount' => 'required|integer|min:1']);
        return response()->json(['data' => $this->service->repay($id, $request->integer('amount'))]);
    }

    public function myLoans(Request $request): JsonResponse { return response()->json(['data' => $this->service->userLoans($request->user()->id)]); }
}
