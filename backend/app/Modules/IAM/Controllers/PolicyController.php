<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\IAM\Models\Policy;
use Modules\IAM\Requests\StorePolicyRequest;
use Modules\IAM\Requests\UpdatePolicyRequest;
use App\Support\ApiResponse;

final class PolicyController extends Controller
{
    use ApiResponse;
    public function index(): JsonResponse
    {
        $policies = Policy::all();

        return $this->respond($policies);
    }

    public function store(StorePolicyRequest $request): JsonResponse
    {
        $policy = Policy::create($request->validated());

        return $this->respondCreated($policy, 'Policy created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);

        return $this->respond($policy);
    }

    public function update(UpdatePolicyRequest $request, string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);
        $policy->update($request->validated());

        return $this->respond($policy, 'Policy updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);
        $policy->delete();

        return $this->respondDeleted('Policy deleted successfully');
    }
}
