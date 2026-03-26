<?php

namespace Tests\Feature;

use App\Services\Payment\SikeuPaymentService;
use App\Exceptions\SikeuPaymentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SikeuPaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sikeu.api.base_url' => 'http://localhost:8080',
            'sikeu.auth.api_key' => 'test-api-key',
            'sikeu.auth.shared_secret' => 'test-shared-secret',
            'sikeu.auth.source_app' => 'TEST_APP',
        ]);
    }

    public function test_create_payment_request_success(): void
    {
        Http::fake([
            '*/api/payment-requests' => Http::response([
                'success' => true,
                'message' => 'Payment request created',
                'data' => [
                    'paymentRequestId' => 'PR123456',
                    'virtualAccountNo' => '8808012345678901',
                    'amount' => 5000000,
                ],
            ], 201),
        ]);

        $service = app(SikeuPaymentService::class);

        $result = $service->createPaymentRequest([
            'service_category' => 'UKT',
            'customer_no' => '2024000001',
            'customer_name' => 'John Doe',
            'amount' => 5000000,
            'description' => 'Test payment',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('PR123456', $result['data']['paymentRequestId']);
    }

    public function test_create_payment_request_failure(): void
    {
        Http::fake([
            '*/api/payment-requests' => Http::response([
                'success' => false,
                'message' => 'Invalid request',
            ], 400),
        ]);

        $this->expectException(SikeuPaymentException::class);

        $service = app(SikeuPaymentService::class);
        $service->createPaymentRequest([
            'service_category' => 'UKT',
            'customer_no' => '2024000001',
            'customer_name' => 'John Doe',
            'amount' => 5000000,
        ]);
    }

    public function test_get_payment_request(): void
    {
        Http::fake([
            '*/api/payment-requests/*' => Http::response([
                'success' => true,
                'data' => [
                    'paymentRequestId' => 'PR123456',
                    'status' => 'PENDING',
                ],
            ], 200),
        ]);

        $service = app(SikeuPaymentService::class);
        $result = $service->getPaymentRequest('PR123456');

        $this->assertTrue($result['success']);
        $this->assertEquals('PENDING', $result['data']['status']);
    }
}
