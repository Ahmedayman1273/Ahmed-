<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Custom handling for unauthenticated token.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. Token is missing or invalid.'
        ], 401);
    }

    /**
     * Master exception handler for structured responses.
     */
    public function render($request, Throwable $exception)
    {
        // Forbidden (e.g. user type is not admin)
        if ($exception instanceof AccessDeniedHttpException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. You do not have permission.'
            ], 403);
        }

        // Record not found (invalid ID)
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        return parent::render($request, $exception);
    }

    /**
     * Empty default renderable block.
     */
    public function register(): void
    {
        //
    }
}
