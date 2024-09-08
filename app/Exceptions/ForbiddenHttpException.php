<?php

namespace App\Exceptions;

use Exception;

class ForbiddenHttpException extends Exception
{
    /**
     * Create a new ForbiddenHttpException instance.
     *
     * @param  string|null  $message
     * @param  int  $code
     */
    public function __construct($message = 'Forbidden', ?\Throwable $previous = null, $code = 0, array $headers = [])
    {
        // Set the 403 HTTP status code for "Forbidden"
        parent::__construct(403, $message, $previous, $headers, $code);
    }
}
