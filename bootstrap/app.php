<?php

use App\Common\ResponseMessages;
use App\Helpers\UtilityHelper;
use App\Http\Middleware\CamelCaseMiddleware;
use App\Http\Middleware\ClientPublicApiMiddleware;
use App\Http\Middleware\Cors;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleAuthProvider;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\LogApiCall;
use App\Http\Middleware\RoleCheckMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // append or prepend middlewares here
        $middleware->append(ForceJsonResponse::class);
        $middleware->append(Cors::class);
        $middleware->append(CamelCaseMiddleware::class);

        // register all middleware alias here
        $middleware->alias([
            'json.response' => ForceJsonResponse::class,
            'cors' => Cors::class,
            'scopes' => CheckScopes::class,
            'scope' => CheckForAnyScope::class,
            'camel.case' => CamelCaseMiddleware::class,
            'admin' => IsAdmin::class,
            'auth.provider' => HandleAuthProvider::class,
            'roleCheck' => RoleCheckMiddleware::class,
            'clientAuth' => ClientPublicApiMiddleware::class,
            'log.api' => LogApiCall::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $throwable, Request $request) {

            // Check if the request is for an API
            if (! $request->is('api/*')) {
                return null;
            }

            // Prepare a generic error structure
            $error = ['success' => false];
            $statusCode = 500;

            // Handle specific exception types
            switch (get_class($throwable)) {
                case ValidationException::class:
                    $error['message'] = ResponseMessages::INVALID_PARAMS;
                    $error['errors'] = $throwable->errors();
                    $statusCode = 400;
                    break;

                case AuthenticationException::class:
                    $error['message'] = ResponseMessages::UNAUTHENTICATED;
                    $statusCode = 401;
                    break;

                case UnauthorizedException::class:
                    $error['message'] = ResponseMessages::UNAUTHORIZED;
                    $statusCode = 403;
                    break;

                case NotFoundHttpException::class:
                    $error['message'] = ResponseMessages::NOT_FOUND_HTTP_EXCEPTION;
                    $statusCode = 404;
                    break;

                case QueryException::class:
                    Log::error('SQL Error: '.$throwable->getMessage());
                    $error['message'] = ResponseMessages::ERROR_PROCESSING_REQUEST;
                    $statusCode = 500;
                    break;

                default:
                    $error['message'] = UtilityHelper::getExceptionMessage($throwable);
                    $statusCode = UtilityHelper::getStatusCode($throwable);
                    break;
            }

            // In debug mode, return additional error details
            if (config('app.debug')) {
                $error['exception_message'] = $throwable->getMessage();
                $error['trace'] = $throwable->getTraceAsString();
            }

            // Ensure the status code is within valid HTTP status code range
            $statusCode = ($statusCode < 100 || $statusCode > 511) ? 500 : $statusCode;

            // Return the JSON response with the error details
            return new JsonResponse($error, $statusCode);
        });
    })->create();
