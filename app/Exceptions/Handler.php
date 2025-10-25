<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ImportErrorException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {});

        $this->renderable(function (Throwable $e) {
            if (request()->expectsJson()) {
                return $this->handleException($e);
            }
        });
    }

    public function handleException(\Exception|\TypeError|\Error $exception)
    {
        if ($exception instanceof AuthenticationException) {
            return response([
                'message' => trans('auth.login.errors.unauthenticated'),
                'reload' => true,
            ], 401);
        } elseif ($exception instanceof TokenMismatchException) {
            return response([
                'message' => trans('general.errors.csrf_token_mismatch'),
                'reload' => true,
            ], 419);
        } elseif ($exception instanceof ModelNotFoundException) {
            return response([
                'message' => trans('global.could_not_find', ['attribute' => trans('general.data')]),
            ], 404);
        } elseif ($exception instanceof NotFoundHttpException) {
            return response([
                'message' => trans('global.could_not_find', ['attribute' => trans('general.data')]),
            ], 404);
        }
    }

    public function context()
    {
        return [
            'user' => auth()?->user()?->id,
            'url' => request()->method().' '.request()->fullUrl(),
        ];
    }
}
