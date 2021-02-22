<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $exception)
    {
        /**
         *  在 laravel API接口开发时, 若涉及到表单验证发生错误, 会被自动重定向到首页
         *  定位源码到 : Illuminate\Foundation\Http\FormRequest 的 failedValidation 方法即可发现
         */
        if ($request->is("api/*")) {
            if ($exception instanceof ValidationException) {
                return api_response(1, [], array_values($exception->errors())[0][0]);
            }
        }

        return parent::render($request, $exception);
    }
}
