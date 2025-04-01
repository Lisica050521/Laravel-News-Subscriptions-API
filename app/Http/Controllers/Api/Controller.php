<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Форматирует ответ согласно Accept-заголовку
     */
    protected function formatResponse($data, $status = 200)
    {
        $format = request()->header('Accept', 'json');

        if ($format === 'xml') {
            return Response::xml($data, $status);
        }

        return response()->json($data, $status);
    }

    /**
     * Упрощенный успешный ответ
     */
    protected function success($message, $data = [], $status = 200)
    {
        return $this->formatResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Упрощенный ответ с ошибкой
     */
    protected function error($message, $errors = [], $status = 400)
    {
        return $this->formatResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Обработка исключений API
     */
    protected function handleException(\Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return $this->error(
                'Ошибка валидации данных',
                $e->errors(),
                422
            );
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->error(
                'Ресурс не найден',
                ['resource' => 'Запрашиваемый ресурс не существует'],
                404
            );
        }

        if ($e instanceof HttpException) {
            return $this->error(
                $e->getMessage() ?: 'Ошибка HTTP',
                [],
                $e->getStatusCode()
            );
        }

        return $this->error(
            'Внутренняя ошибка сервера',
            ['system' => config('app.debug') ? $e->getMessage() : null],
            500
        );
    }
}
