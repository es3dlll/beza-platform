<?php

declare(strict_types=1);

namespace Modules\GovCollections\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\GovCollections\Services\GovCollectionService;
use Modules\GovCollections\Exceptions\GovServiceProviderNotFoundException;
use Modules\GovCollections\Exceptions\GovInquiryExpiredException;

final class GovCollectionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly GovCollectionService $service) {}

    public function providers(): JsonResponse
    {
        return $this->respond($this->service->listProviders());
    }

    public function inquire(Request $request): JsonResponse
    {
        $request->validate(['provider_id' => 'required|string|size:26','service_code' => 'required|string|max:50','account_number' => 'required|string|max:100']);
        try {
            $inquiry = $this->service->inquire($request->user()->id, $request->input('provider_id'), $request->input('service_code'), $request->input('account_number'));
        } catch (GovServiceProviderNotFoundException $e) {
            return $this->respondError('PROVIDER_NOT_FOUND', null, null, 404);
        }
        return $this->respondCreated($inquiry);
    }

    public function pay(Request $request, string $id): JsonResponse
    {
        try {
            $collection = $this->service->pay($request->user()->id, $id, $request->input('channel', 'mobile'));
        } catch (GovInquiryExpiredException $e) {
            return $this->respondError('INQUIRY_EXPIRED', null, null, 422);
        }
        return $this->respondCreated($collection);
    }

    public function history(Request $request): JsonResponse
    {
        return $this->respond($this->service->history($request->user()->id));
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

        return $this->respond([
            'total_inquiries' => $totalInquiries,
            'total_payments' => $totalPayments,
            'total_volume' => $totalVolume,
            'by_provider' => $byProvider,
        ]);
    }
}
