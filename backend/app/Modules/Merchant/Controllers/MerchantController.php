<?php

declare(strict_types=1);

namespace Modules\Merchant\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Merchant\DTOs\RegisterMerchantDto;
use Modules\Merchant\DTOs\CreateStoreDto;
use Modules\Merchant\DTOs\MerchantPaymentDto;
use Modules\Merchant\Http\Requests\RegisterMerchantRequest;
use Modules\Merchant\Http\Requests\CreateStoreRequest;
use Modules\Merchant\Http\Requests\MerchantPayRequest;
use Modules\Merchant\Http\Requests\MerchantRefundRequest;
use Modules\Merchant\Services\MerchantService;
use Modules\Merchant\Services\MerchantPaymentService;
use App\Support\ApiResponse;

final class MerchantController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly MerchantService $merchantService,
        private readonly MerchantPaymentService $paymentService,
    ) {}

    public function register(RegisterMerchantRequest $request): JsonResponse
    {
        $dto = new RegisterMerchantDto(
            userId: $request->user()->id,
            businessName: $request->input('business_name'),
            businessNameAr: $request->input('business_name_ar'),
            phone: $request->input('phone'),
            governorate: $request->input('governorate'),
            city: $request->input('city'),
            commercialRegistration: $request->input('commercial_registration'),
            taxNumber: $request->input('tax_number'),
            email: $request->input('email'),
            address: $request->input('address'),
            category: $request->input('category'),
        );

        $merchant = $this->merchantService->register($dto);
        return $this->respondCreated($merchant);
    }

    public function myMerchant(Request $request): JsonResponse
    {
        $merchant = $this->merchantService->findByUser($request->user()->id);
        if (!$merchant) {
            return $this->respondError('MERCHANT_NOT_FOUND', 'Merchant not found', 'التاجر غير موجود', 404);
        }
        return $this->respond($merchant);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $merchant = $this->merchantService->approve($id, $request->user()->id);
        return $this->respond($merchant);
    }

    public function suspend(string $id): JsonResponse
    {
        $merchant = $this->merchantService->suspend($id);
        return $this->respond($merchant);
    }

    public function createStore(CreateStoreRequest $request): JsonResponse
    {
        $dto = new CreateStoreDto(
            merchantId: $request->input('merchant_id'),
            name: $request->input('name'),
            nameAr: $request->input('name_ar'),
            governorate: $request->input('governorate'),
            city: $request->input('city'),
            phone: $request->input('phone'),
            address: $request->input('address'),
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
        );

        $store = $this->merchantService->createStore($dto);
        return $this->respondCreated($store);
    }

    public function listStores(string $merchantId): JsonResponse
    {
        $stores = $this->merchantService->getStores($merchantId);
        return $this->respond($stores);
    }

    public function generateQr(Request $request): JsonResponse
    {
        $qrCode = $this->merchantService->generateQrCode();
        $merchant = $this->merchantService->findByUser($request->user()->id);

        return $this->respond([
            'qr_code' => $qrCode,
            'merchant_id' => $merchant?->id,
            'type' => 'static',
        ]);
    }

    public function pay(MerchantPayRequest $request): JsonResponse
    {
        $dto = new MerchantPaymentDto(
            qrCode: $request->input('qr_code'),
            merchantId: $request->input('merchant_id'),
            payerUserId: $request->user()->id,
            amount: (int) $request->input('amount'),
            storeId: $request->input('store_id'),
        );

        $payment = $this->paymentService->pay($dto);
        return $this->respondCreated($payment);
    }

    public function refund(MerchantRefundRequest $request, string $id): JsonResponse
    {
        $payment = $this->paymentService->refund($id, $request->input('reason'));
        return $this->respond($payment);
    }

    public function myPayments(Request $request): JsonResponse
    {
        $payments = $this->paymentService->findByUser(
            $request->user()->id,
            (int) $request->input('per_page', 15),
        );
        return $this->respond($payments);
    }

    public function merchantPayments(Request $request, string $merchantId): JsonResponse
    {
        $payments = $this->paymentService->findByMerchant($merchantId, (int) $request->input('per_page', 15));
        return $this->respond($payments);
    }

    public function showPayment(string $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        if (!$payment) {
            return $this->respondError('MERCHANT_NOT_FOUND', 'Merchant payment not found', 'الدفعة غير موجودة', 404);
        }
        return $this->respond($payment);
    }
}
