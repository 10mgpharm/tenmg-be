<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception implements Responsable
{
    protected int $statusCode = 400;

    protected string $errorType = 'general_error';

    protected array $additionalData = [];

    protected ?string $errorReference = null;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message ?: 'An error occurred', $code, $previous);
        $this->errorReference = (string) \Illuminate\Support\Str::uuid();
        $this->statusCode = $this->sanitizeStatusCode($this->statusCode);
    }

    public function withStatusCode(int $statusCode): self
    {
        $this->statusCode = $this->sanitizeStatusCode($statusCode);

        return $this;
    }

    public function withErrorType(string $errorType): self
    {
        $this->errorType = $errorType;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->additionalData = array_merge($this->additionalData, $data);

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function sanitizeStatusCode(int $statusCode): int
    {
        if ($statusCode < 100 || $statusCode > 599) {
            return 500;
        }

        return $statusCode;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function getErrorReference(): ?string
    {
        return $this->errorReference;
    }

    public function context(): array
    {
        return array_merge([
            'error_type' => $this->errorType,
            'error_reference' => $this->errorReference,
        ], $this->additionalData);
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'message' => $this->getMessage(),
                'type' => $this->errorType,
                'reference' => $this->errorReference,
            ] + $this->additionalData,
        ], $this->statusCode);
    }
}
