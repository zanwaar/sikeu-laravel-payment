<?php

namespace Sikeu\LaravelPayment\Services;

class PaymentResponse
{
    protected array $data;
    protected int $statusCode;
    protected bool $success;

    public function __construct(array $data, int $statusCode)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->success = $statusCode >= 200 && $statusCode < 300;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getMessage(): ?string
    {
        return $this->data['message'] ?? null;
    }

    public function getPaymentRequestId(): ?string
    {
        return $this->data['data']['paymentRequestId'] ?? null;
    }

    public function getVirtualAccountNo(): ?string
    {
        return $this->data['data']['virtualAccountNo'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status_code' => $this->statusCode,
            'data' => $this->data,
        ];
    }
}
