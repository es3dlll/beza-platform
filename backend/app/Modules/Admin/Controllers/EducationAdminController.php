<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\EducationAdminService;
use App\Support\ApiResponse;

final class EducationAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly EducationAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function institutions(): JsonResponse
    {
        return $this->respond($this->service->institutions());
    }

    public function institutionDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->institutionDetail($id));
    }

    public function createInstitution(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $institution = $this->service->createInstitution($request->all());
        return $this->respondCreated($institution);
    }

    public function updateInstitution(Request $request, string $id): JsonResponse
    {
        $this->service->updateInstitution($id, $request->all());
        return $this->respond(['message' => 'Institution updated']);
    }

    public function listOverdueStudents(Request $request): JsonResponse
    {
        return $this->respond($this->service->listOverdueStudents($request->query('institution_id')));
    }

    public function collectionReport(Request $request): JsonResponse
    {
        $request->validate(['institution_id' => 'required|string']);
        return $this->respond($this->service->collectionReport(
            $request->input('institution_id'),
            $request->query('from'),
            $request->query('to'),
        ));
    }
}
