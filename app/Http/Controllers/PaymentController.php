<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePaymentRequest;
use App\Services\Payment\SikeuPaymentService;
use App\Exceptions\SikeuPaymentException;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected SikeuPaymentService $paymentService;

    public function __construct(SikeuPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function create(CreatePaymentRequest $request): JsonResponse
    {
        try {
            $result = $this->paymentService->createPaymentRequest($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Payment request created successfully',
                'data' => $result['data'] ?? $result,
            ], 201);

        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing payment request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(string $paymentRequestId): JsonResponse
    {
        try {
            $result = $this->paymentService->getPaymentRequest($paymentRequestId);

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? $result,
            ]);

        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching payment request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function cancel(string $paymentRequestId): JsonResponse
    {
        try {
            $result = $this->paymentService->cancelPaymentRequest($paymentRequestId);

            return response()->json([
                'success' => true,
                'message' => 'Payment request cancelled successfully',
                'data' => $result['data'] ?? $result,
            ]);

        } catch (SikeuPaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling payment request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
