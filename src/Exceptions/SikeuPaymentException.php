<?php

namespace Sikeu\LaravelPayment\Exceptions;

use Exception;

class SikeuPaymentException extends Exception
{
    protected ?array $responseData = null;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, ?array $responseData = null)
    {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public function report(): void
    {
        if (config('sikeu.logging.enabled')) {
            \Log::channel(config('sikeu.logging.channel'))->error('SIKEU Payment Exception', [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'response_data' => $this->responseData,
            ]);
        }
    }
}
