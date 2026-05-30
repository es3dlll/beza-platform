<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\IAM\Models\Policy;
use Modules\IAM\Requests\StorePolicyRequest;
use Modules\IAM\Requests\UpdatePolicyRequest;

class PolicyController extends Controller
{
    public function index(): JsonResponse
    {
        $policies = Policy::all();

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    public function store(StorePolicyRequest $request): JsonResponse
    {
        $policy = Policy::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $policy,
            'message' => 'Policy created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $policy,
        ]);
    }

    public function update(UpdatePolicyRequest $request, string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);
        $policy->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $policy,
            'message' => 'Policy updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);
        $policy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Policy deleted successfully',
        ]);
    }
}
