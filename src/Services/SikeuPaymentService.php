<?php

namespace Sikeu\LaravelPayment\Services;

use Sikeu\LaravelPayment\Exceptions\SikeuPaymentException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SikeuPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $sharedSecret;
    protected string $sourceApp;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('sikeu.api.base_url');
        $this->apiKey = config('sikeu.auth.api_key');
        $this->sharedSecret = config('sikeu.auth.shared_secret');
        $this->sourceApp = config('sikeu.auth.source_app');
        $this->timeout = config('sikeu.api.timeout', 30);

        $this->validateConfig();
    }

    protected function validateConfig(): void
    {
        if (empty($this->apiKey)) {
            throw new SikeuPaymentException('SIKEU API Key is not configured');
        }

        if (empty($this->sharedSecret)) {
            throw new SikeuPaymentException('SIKEU Shared Secret is not configured');
        }
    }

    protected function generateSignature(string $method, string $endpoint, int $timestamp, array $body = []): string
    {
        $bodyJson = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_SLASHES);

        // Format: METHOD|ENDPOINT|BODY|TIMESTAMP (pipe-separated)
        $stringToSign = implode('|', [
            strtoupper($method),
            $endpoint,
            $bodyJson,
            $timestamp
        ]);

        $signature = hash_hmac('sha256', $stringToSign, $this->sharedSecret);

        // Return with sha256= prefix
        return 'sha256=' . $signature;
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): PaymentResponse
    {
        $timestamp = time(); // Unix timestamp
        $signature = $this->generateSignature($method, $endpoint, $timestamp, $data);

        $headers = [
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'X-Source-App' => $this->sourceApp,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if (config('sikeu.logging.enabled')) {
            Log::channel(config('sikeu.logging.channel'))->info('SIKEU API Request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'headers' => $headers,
                'data' => $data,
            ]);
        }

        try {
            $response = Http::withHeaders($headers)
                        ->timeout($this->timeout)
                ->{strtolower($method)}($this->baseUrl . $endpoint, $data);

            $responseData = $response->json() ?? [];
            $statusCode = $response->status();

            if (config('sikeu.logging.enabled')) {
                Log::channel(config('sikeu.logging.channel'))->info('SIKEU API Response', [
                    'status_code' => $statusCode,
                    'response' => $responseData,
                ]);
            }

            return new PaymentResponse($responseData, $statusCode);

        } catch (\Exception $e) {
            if (config('sikeu.logging.enabled')) {
                Log::channel(config('sikeu.logging.channel'))->error('SIKEU API Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            throw new SikeuPaymentException('Payment request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createPaymentRequest(array $data): array
    {
        $payload = [
            'source_app' => $this->sourceApp,
            'service_category' => $data['service_category'], // Use as-is, no mapping
            'customer_no' => $data['customer_no'],
            'customer_name' => $data['customer_name'],
            'amount' => (int) $data['amount'], // Convert to integer
            'description' => $data['description'] ?? '',
            'revenue_account_code' => $data['revenue_account_code'],
            'paymentGatewayProvider' => $data['provider'] ?? config('sikeu.payment.default_provider'),
        ];

        // Only add attributes if provided and not empty
        if (!empty($data['attributes'])) {
            $payload['attributes'] = $data['attributes'];
        }

        $response = $this->makeRequest('POST', '/api/payment-requests', $payload);

        if (!$response->isSuccess()) {
            throw new SikeuPaymentException(
                $response->getMessage() ?? 'Failed to create payment request',
                $response->getStatusCode(),
                null,
                $response->getData() // Pass full response data
            );
        }

        return $response->getData();
    }

    public function getPaymentRequest(string $paymentRequestId): array
    {
        $response = $this->makeRequest('GET', "/api/payment-requests/{$paymentRequestId}");

        if (!$response->isSuccess()) {
            throw new SikeuPaymentException(
                $response->getMessage() ?? 'Failed to get payment request',
                $response->getStatusCode(),
                null,
                $response->getData() // Pass full response data
            );
        }

        return $response->getData();
    }

    public function cancelPaymentRequest(string $paymentRequestId): array
    {
        $response = $this->makeRequest('DELETE', "/api/payment-requests/{$paymentRequestId}");

        if (!$response->isSuccess()) {
            throw new SikeuPaymentException(
                $response->getMessage() ?? 'Failed to cancel payment request',
                $response->getStatusCode(),
                null,
                $response->getData() // Pass full response data
            );
        }

        return $response->getData();
    }

    public function checkPaymentRequest(string $paymentRequestId): array
    {
        $response = $this->makeRequest('GET', "/api/payment-requests/{$paymentRequestId}/check");

        if (!$response->isSuccess()) {
            throw new SikeuPaymentException(
                $response->getMessage() ?? 'Failed to check payment status',
                $response->getStatusCode(),
                null,
                $response->getData()
            );
        }

        return $response->getData();
    }

    public function getAvailableServices(): array
    {
        $response = $this->makeRequest('GET', "/api/v1/service-categories");

        if (!$response->isSuccess()) {
            throw new SikeuPaymentException(
                $response->getMessage() ?? 'Failed to get available services',
                $response->getStatusCode(),
                null,
                $response->getData()
            );
        }

        return $response->getData();
    }

    public function getRevenueAccountCodes(): array
    {
        $response = $this->makeRequest('GET', "/api/v1/revenue-account-codes");

        if (!$response->isSuccess()) {
            throw new SikeuPaymentException(
                $response->getMessage() ?? 'Failed to get revenue account codes',
                $response->getStatusCode(),
                null,
                $response->getData()
            );
        }

        return $response->getData();
    }

    /**
     * Buat payment request dengan metode QRIS (BRI atau BSI).
     *
     * Wrapper dari createPaymentRequest yang secara eksplisit
     * menggunakan provider QRIS dan mengembalikan qrContent.
     *
     * @param array $data {
     *   service_category: string,
     *   customer_no: string,
     *   customer_name: string,
     *   amount: int,
     *   description: string,
     *   revenue_account_code: string,
     *   provider?: 'BRI_QRIS'|'BSI_QRIS'  (default dari config: BRI_QRIS)
     *   attributes?: array
     * }
     * @return array Response data termasuk qrContent, qrId, paymentRequestId
     * @throws SikeuPaymentException
     */
    public function createQrisPaymentRequest(array $data): array
    {
        if (empty($data['provider'])) {
            $data['provider'] = config('sikeu.payment.default_qris_provider', 'BRI_QRIS');
        }

        return $this->createPaymentRequest($data);
    }

    /**
     * Cek status pembayaran QRIS berdasarkan paymentRequestId.
     *
     * @param string $paymentRequestId
     * @return array
     * @throws SikeuPaymentException
     */
    public function checkQrisPaymentStatus(string $paymentRequestId): array
    {
        return $this->checkPaymentRequest($paymentRequestId);
    }
}
