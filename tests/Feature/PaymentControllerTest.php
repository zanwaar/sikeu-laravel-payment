<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
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

    public function test_create_payment_endpoint(): void
    {
        Http::fake([
            '*/api/payment-requests' => Http::response([
                'success' => true,
                'message' => 'Payment request created',
                'data' => [
                    'paymentRequestId' => 'PR123456',
                    'virtualAccountNo' => '8808012345678901',
                ],
            ], 201),
        ]);

        $response = $this->postJson('/api/payments', [
            'service_category' => 'UKT',
            'customer_no' => '2024000001',
            'customer_name' => 'John Doe',
            'amount' => 5000000,
            'description' => 'Test payment',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Payment request created successfully',
            ]);
    }

    public function test_create_payment_validation_error(): void
    {
        $response = $this->postJson('/api/payments', [
            'service_category' => 'UKT',
            // missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_no', 'customer_name', 'amount']);
    }

    public function test_show_payment_endpoint(): void
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

        $response = $this->getJson('/api/payments/PR123456');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_cancel_payment_endpoint(): void
    {
        Http::fake([
            '*/api/payment-requests/*' => Http::response([
                'success' => true,
                'message' => 'Payment cancelled',
            ], 200),
        ]);

        $response = $this->deleteJson('/api/payments/PR123456');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment request cancelled successfully',
            ]);
    }
}
