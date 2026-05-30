<?php

declare(strict_types=1);

namespace Modules\Education\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Education\Services\EducationService;

class EducationController extends Controller
{
    public function __construct(private readonly EducationService $service) {}

    public function institutions(): JsonResponse { return response()->json(['data' => $this->service->listInstitutions()]); }

    public function registerStudent(Request $request): JsonResponse
    {
        $request->validate(['institution_id'=>'required|string|size:26','student_id'=>'required|string|max:50','full_name'=>'required|string|max:100','full_name_ar'=>'required|string|max:100','grade'=>'nullable|string|max:30']);
        return response()->json(['data' => $this->service->registerStudent($request->user()->id, $request->input('institution_id'), $request->input('student_id'), $request->input('full_name'), $request->input('full_name_ar'), $request->input('grade'))], 201);
    }

    public function createFee(Request $request): JsonResponse
    {
        $request->validate(['student_id'=>'required|string|size:26','fee_type'=>'required|string|max:50','amount'=>'required|integer|min:1000','due_date'=>'required|date']);
        return response()->json(['data' => $this->service->createFee($request->input('student_id'), $request->input('fee_type'), $request->integer('amount'), $request->input('due_date'))], 201);
    }

    public function payFee(Request $request, string $id): JsonResponse
    {
        $request->validate(['amount'=>'required|integer|min:1']);
        return response()->json(['data' => $this->service->payFee($id, $request->integer('amount'))]);
    }

    public function studentFees(string $id): JsonResponse { return response()->json(['data' => $this->service->studentFees($id)]); }

    public function institutionDashboard(string $id): JsonResponse { return response()->json(['data' => $this->service->institutionDashboard($id)]); }

    public function bulkCreateFees(Request $request): JsonResponse
    {
        $request->validate(['institution_id'=>'required|string|size:26','fees'=>'required|array','fees.*.student_id'=>'required|string','fees.*.fee_type'=>'required|string|max:50','fees.*.amount'=>'required|integer|min:1000','fees.*.due_date'=>'required|date']);
        return response()->json(['data' => $this->service->bulkCreateFees($request->input('institution_id'), $request->input('fees'))], 201);
    }

    public function overdueFees(string $id): JsonResponse { return response()->json(['data' => $this->service->overdueFees($id)]); }

    public function receipt(string $feeId): JsonResponse { return response()->json(['data' => $this->service->generateReceipt($feeId)]); }
}
