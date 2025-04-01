<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use ReflectionClass;

class BaseControllerTest extends TestCase
{
    /**
     * Вызывает protected метод контроллера
     */
    private function invokeProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Тестирование успешного JSON-ответа
     */
    public function test_success_response()
    {
        $controller = new Controller();

        $response = $this->invokeProtectedMethod($controller, 'success', [
            'Тестовое сообщение',
            ['key' => 'value'],
            200
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => true,
                'message' => 'Тестовое сообщение',
                'data' => ['key' => 'value']
            ]),
            $response->getContent()
        );
    }

    /**
     * Тестирование ответа с ошибкой
     */
    public function test_error_response()
    {
        $controller = new Controller();

        $response = $this->invokeProtectedMethod($controller, 'error', [
            'Ошибка валидации',
            ['field' => 'Некорректное значение'],
            422
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => ['field' => 'Некорректное значение']
            ]),
            $response->getContent()
        );
    }

    /**
     * Тест обработки ValidationException
     */
    public function test_handle_validation_exception()
    {
        $controller = new Controller();
        $exception = ValidationException::withMessages([
            'email' => ['Некорректный email']
        ]);

        $response = $this->invokeProtectedMethod($controller, 'handleException', [$exception]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => ['email' => ['Некорректный email']]
            ]),
            $response->getContent()
        );
    }

    /**
     * Тест обработки ModelNotFoundException
     */
    public function test_handle_model_not_found_exception()
    {
        $controller = new Controller();
        $exception = new ModelNotFoundException();

        $response = $this->invokeProtectedMethod($controller, 'handleException', [$exception]);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'message' => 'Ресурс не найден',
                'errors' => ['resource' => 'Запрашиваемый ресурс не существует']
            ]),
            $response->getContent()
        );
    }

    /**
     * Тест обработки HttpException
     */
    public function test_handle_http_exception()
    {
        $controller = new Controller();
        $exception = new HttpException(403, 'Доступ запрещен');

        $response = $this->invokeProtectedMethod($controller, 'handleException', [$exception]);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'success' => false,
                'message' => 'Доступ запрещен',
                'errors' => []
            ]),
            $response->getContent()
        );
    }

    /**
     * Тест обработки общего исключения в режиме разработки
     */
    public function test_handle_generic_exception_in_debug()
    {
        config(['app.debug' => true]);
        $controller = new Controller();
        $exception = new \Exception('Тестовая ошибка');

        $response = $this->invokeProtectedMethod($controller, 'handleException', [$exception]);

        $this->assertEquals(500, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Внутренняя ошибка сервера', $content['message']);
        $this->assertEquals('Тестовая ошибка', $content['errors']['system']);
    }

    /**
     * Тест обработки общего исключения в production
     */
    public function test_handle_generic_exception_in_production()
    {
        config(['app.debug' => false]);
        $controller = new Controller();
        $exception = new \Exception('Тестовая ошибка');

        $response = $this->invokeProtectedMethod($controller, 'handleException', [$exception]);

        $this->assertEquals(500, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Внутренняя ошибка сервера', $content['message']);
        $this->assertNull($content['errors']['system']);
    }
}
