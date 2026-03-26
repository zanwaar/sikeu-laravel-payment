<?php

namespace App\Jobs;

use App\Services\Payment\SikeuPaymentService;
use App\Exceptions\SikeuPaymentException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreatePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected array $paymentData;

    public function __construct(array $paymentData)
    {
        $this->paymentData = $paymentData;
    }

    public function handle(SikeuPaymentService $paymentService): void
    {
        try {
            $result = $paymentService->createPaymentRequest($this->paymentData);

            Log::info('Payment request created successfully via job', [
                'payment_request_id' => $result['data']['paymentRequestId'] ?? null,
                'customer_no' => $this->paymentData['customer_no'],
            ]);

        } catch (SikeuPaymentException $e) {
            Log::error('Payment job failed', [
                'error' => $e->getMessage(),
                'customer_no' => $this->paymentData['customer_no'],
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Payment job failed permanently', [
            'error' => $exception->getMessage(),
            'customer_no' => $this->paymentData['customer_no'],
        ]);
    }
}
