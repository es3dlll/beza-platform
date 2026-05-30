<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\EducationAdminService;

final class EducationAdminController extends Controller
{
    public function __construct(
        private readonly EducationAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function institutions(): JsonResponse
    {
        return response()->json(['data' => $this->service->institutions()]);
    }

    public function institutionDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->institutionDetail($id)]);
    }

    public function createInstitution(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $institution = $this->service->createInstitution($request->all());
        return response()->json(['data' => $institution], 201);
    }

    public function updateInstitution(Request $request, string $id): JsonResponse
    {
        $this->service->updateInstitution($id, $request->all());
        return response()->json(['data' => ['message' => 'Institution updated']]);
    }

    public function listOverdueStudents(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listOverdueStudents($request->query('institution_id'))]);
    }

    public function collectionReport(Request $request): JsonResponse
    {
        $request->validate(['institution_id' => 'required|string']);
        return response()->json(['data' => $this->service->collectionReport(
            $request->input('institution_id'),
            $request->query('from'),
            $request->query('to'),
        )]);
    }
}
