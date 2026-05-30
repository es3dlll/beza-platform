<?php

declare(strict_types=1);

namespace Modules\GovCollections\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\GovCollections\Services\GovCollectionService;
use Modules\GovCollections\Exceptions\GovServiceProviderNotFoundException;
use Modules\GovCollections\Exceptions\GovInquiryExpiredException;

class GovCollectionController extends Controller
{
    public function __construct(private readonly GovCollectionService $service) {}

    public function providers(): JsonResponse
    {
        return response()->json(['data' => $this->service->listProviders()]);
    }

    public function inquire(Request $request): JsonResponse
    {
        $request->validate(['provider_id' => 'required|string|size:26','service_code' => 'required|string|max:50','account_number' => 'required|string|max:100']);
        try {
            $inquiry = $this->service->inquire($request->user()->id, $request->input('provider_id'), $request->input('service_code'), $request->input('account_number'));
        } catch (GovServiceProviderNotFoundException $e) {
            return response()->json(['error' => 'PROVIDER_NOT_FOUND'], 404);
        }
        return response()->json(['data' => $inquiry], 201);
    }

    public function pay(Request $request, string $id): JsonResponse
    {
        try {
            $collection = $this->service->pay($request->user()->id, $id, $request->input('channel', 'mobile'));
        } catch (GovInquiryExpiredException $e) {
            return response()->json(['error' => 'INQUIRY_EXPIRED'], 422);
        }
        return response()->json(['data' => $collection], 201);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->history($request->user()->id)]);
    }

    public function adminSummary(): JsonResponse
    {
        $totalInquiries = \Modules\GovCollections\Models\GovPaymentInquiry::count();
        $totalPayments = \Modules\GovCollections\Models\GovCollection::where('status', 'completed')->count();
        $totalVolume = \Modules\GovCollections\Models\GovCollection::where('status', 'completed')->sum('amount');
        $byProvider = \Modules\GovCollections\Models\GovCollection::where('status', 'completed')
            ->selectRaw('provider_id, count(*) as count, sum(amount) as volume')
            ->groupBy('provider_id')
            ->get();

        return response()->json(['data' => [
            'total_inquiries' => $totalInquiries,
            'total_payments' => $totalPayments,
            'total_volume' => $totalVolume,
            'by_provider' => $byProvider,
        ]]);
    }
}
