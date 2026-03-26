<?php

namespace App\Exceptions;

use Exception;

class SikeuPaymentException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function report(): void
    {
        if (config('sikeu.logging.enabled')) {
            \Log::channel(config('sikeu.logging.channel'))->error('SIKEU Payment Exception', [
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
            ]);
        }
    }
}
