<?php

declare(strict_types=1);

namespace App\Traits;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Model;

trait ThrowsApiExceptions
{
    protected function throwApiException(
        string $message,
        int $statusCode = 400,
        string $errorType = 'general_error',
        array $data = []
    ): never {
        throw (new ApiException($message))
            ->withStatusCode($statusCode)
            ->withErrorType($errorType)
            ->withData($data);
    }

    protected function throwNotFound(string $resource, ?string $id = null): never
    {
        throw (new ApiException("{$resource} not found".($id ? " with ID: {$id}" : '')))
            ->withStatusCode(404)
            ->withErrorType('not_found');
    }

    protected function throwUnauthorized(string $message = 'Unauthorized action'): never
    {
        throw (new ApiException($message))
            ->withStatusCode(403)
            ->withErrorType('authorization_error');
    }

    protected function throwUnauthenticated(string $message = 'Unauthenticated'): never
    {
        throw (new ApiException($message))
            ->withStatusCode(401)
            ->withErrorType('authentication_error');
    }

    protected function findOrFail(string $modelClass, $id, ?string $resourceName = null): Model
    {
        try {
            return $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $resource = $resourceName ?? $this->getResourceNameFromModel($modelClass);
            $this->throwNotFound($resource, $id);
        }
    }

    protected function getResourceNameFromModel(string $modelClass): string
    {
        $parts = explode('\\', $modelClass);

        return strtolower(array_pop($parts));
    }

    /**
     * Throw an API exception from an existing exception
     * Uses exception code if it's in the 400-499 range, otherwise defaults to 500
     *
     * @param  \Exception  $exception  The caught exception
     * @param  string  $errorType  The error type identifier
     * @param  array  $data  Additional data to include in the exception
     */
    protected function throwFromException(
        \Exception $exception,
        string $errorType,
        array $data = []
    ): never {
        if ($exception instanceof ApiException) {
            $this->throwApiException(
                $exception->getMessage(),
                $exception->getStatusCode(),
                $exception->getErrorType(),
                $exception->getAdditionalData()
            );
        }

        $statusCode = $exception->getCode() >= 400 && $exception->getCode() < 500
            ? (int) $exception->getCode()
            : 500;
        $this->throwApiException(
            $exception->getMessage(),
            $statusCode,
            $errorType,
            $data
        );
    }
}
