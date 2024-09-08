<?php

namespace App\Http\Controllers;

use App\Exceptions\ForbiddenHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class Controller
{
    /**
     * handle return json response
     *
     * @param [type] $data
     * @param  int  $status
     */
    public function returnJsonResponse(string $message = 'data retrieved', $data = null, string $status = 'success', $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => $status,
        ], $statusCode);
    }

    /**
     * Handle error response
     */
    public function handleErrorResponse(\Throwable $th, $status = null): JsonResponse
    {
        if ($th instanceof BadRequestHttpException) {
            $status = Response::HTTP_BAD_REQUEST;
        } elseif ($th instanceof UnauthorizedHttpException) {
            $status = Response::HTTP_UNAUTHORIZED;
        } elseif ($th instanceof ForbiddenHttpException) {
            $status = Response::HTTP_FORBIDDEN;
        } elseif ($th instanceof NotFoundHttpException) {
            $status = Response::HTTP_NOT_FOUND;
        } elseif ($th instanceof MethodNotAllowedHttpException) {
            $status = Response::HTTP_METHOD_NOT_ALLOWED;
        } elseif ($th instanceof NotAcceptableHttpException) {
            $status = Response::HTTP_NOT_ACCEPTABLE; // 406
        } elseif ($th instanceof ConflictHttpException) {
            $status = Response::HTTP_CONFLICT; // 409
        } elseif ($th instanceof TooManyRequestsHttpException) {
            $status = Response::HTTP_TOO_MANY_REQUESTS; // 429
        } elseif ($th instanceof ServiceUnavailableHttpException) {
            $status = Response::HTTP_SERVICE_UNAVAILABLE; // 503
        } else {
            // Default to internal server error (500) if no specific exception type matches
            $status = $status ?? Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return response()->json([
            'status' => 'error',
            'message' => $th->getMessage(),
            'data' => null,
            'errors' => [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
                'previous' => $th->getPrevious(),
                'code' => $th->getCode(),
            ],
        ], $status);
    }
}
