<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionController extends Controller
{
    /**
     * Подписка пользователя на рубрику
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'category' => 'required|string|exists:categories,slug',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error(
                'Ошибка валидации данных',
                $validator->errors()->toArray(),
                422
            );
        }

        try {
            $user = User::where('email', $request->email)->firstOrFail();

            // Обновляем имя пользователя, если оно изменилось
            if ($user->name !== $request->name) {
                $user->name = $request->name;
                $user->save();
            }

            $category = Category::where('slug', $request->category)->firstOrFail();

            $subscription = Subscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                ],
                [
                    'unsubscribe_key' => Str::uuid(),
                    'user_name' => $request->name,
                ]
            );

            return $this->success(
                'Вы успешно подписались на рубрику',
                [
                    'unsubscribe_key' => $subscription->unsubscribe_key,
                    'category' => $category->slug,
                    'user_name' => $request->name
                ],
                201
            );

        } catch (ModelNotFoundException $e) {
            return $this->error(
                'Ресурс не найден',
                ['details' => $e->getMessage()],
                404
            );
        } catch (\Exception $e) {
            return $this->error(
                'Ошибка при подписке',
                ['system' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Отписка от конкретной рубрики
     */
    public function unsubscribe($categorySlug, $unsubscribeKey)
    {
        try {
            if (!Str::isUuid($unsubscribeKey)) {
                return $this->error(
                    'Неверный формат ключа отписки',
                    ['unsubscribe_key' => $unsubscribeKey],
                    422
                );
            }

            $category = Category::where('slug', $categorySlug)->firstOrFail();

            $deleted = Subscription::where('user_id', Auth::id())
                ->where('category_id', $category->id)
                ->where('unsubscribe_key', $unsubscribeKey)
                ->delete();

            if ($deleted) {
                return $this->success(
                    'Вы отписались от рубрики ' . $category->name,
                    [
                        'category' => $category->slug,
                        'unsubscribe_key' => $unsubscribeKey
                    ]
                );
            }

            return $this->error(
                'Подписка не найдена или неверный ключ',
                [
                    'category' => $categorySlug,
                    'unsubscribe_key' => $unsubscribeKey
                ],
                404
            );

        } catch (ModelNotFoundException $e) {
            return $this->error(
                'Рубрика не найдена',
                ['category' => $categorySlug],
                404
            );
        } catch (\Exception $e) {
            return $this->error(
                'Ошибка при отписке',
                ['system' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Удаление всех подписок текущего пользователя
     */
    public function unsubscribeAll()
    {
        try {
            $deletedCount = Subscription::where('user_id', Auth::id())->delete();

            return $this->success(
                $deletedCount > 0
                    ? 'Все подписки успешно удалены'
                    : 'Нет подписок для удаления',
                ['deleted_count' => $deletedCount]
            );

        } catch (\Exception $e) {
            return $this->error(
                'Ошибка при удалении подписок',
                ['system' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Генерация нового ключа отписки для рубрики
     */
    public function generateUnsubscribeKey($categorySlug)
    {
        try {
            $category = Category::where('slug', $categorySlug)->firstOrFail();

            $subscription = Subscription::where('user_id', Auth::id())
                ->where('category_id', $category->id)
                ->firstOrFail();

            $newKey = Str::uuid();
            $subscription->update(['unsubscribe_key' => $newKey]);

            return $this->success(
                'Новый ключ отписки сгенерирован',
                [
                    'unsubscribe_key' => $newKey,
                    'category' => $category->slug
                ]
            );

        } catch (ModelNotFoundException $e) {
            return $this->error(
                'Подписка не найдена',
                ['category' => $categorySlug],
                404
            );
        } catch (\Exception $e) {
            return $this->error(
                'Ошибка при генерации ключа',
                ['system' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Получение списка подписок текущего пользователя
     */
    public function listSubscriptions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'offset' => 'sometimes|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error(
                    'Неверные параметры запроса',
                    $validator->errors()->toArray(),
                    422
                );
            }

            $perPage = $request->input('limit', 15);
            $offset = $request->input('offset', 0);
            $total = Subscription::where('user_id', Auth::id())->count();

            $subscriptions = Subscription::with('category')
                ->where('user_id', Auth::id())
                ->offset($offset)
                ->limit($perPage)
                ->get()
                ->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'category' => $sub->category->slug,
                        'user_name' => $sub->user_name,
                        'unsubscribe_key' => $sub->unsubscribe_key,
                        'created_at' => $sub->created_at->toDateTimeString()
                    ];
                });

            return $this->success(
                'Список подписок получен',
                [
                    'meta' => [
                        'limit' => $perPage,
                        'offset' => $offset,
                        'total' => $total
                    ],
                    'subscriptions' => $subscriptions
                ]
            );

        } catch (\Exception $e) {
            return $this->error(
                'Ошибка при получении списка подписок',
                ['system' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Получение списка пользователей подписанных на рубрику
     */
    public function listCategorySubscribers($categorySlug, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'offset' => 'sometimes|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error(
                    'Неверные параметры запроса',
                    $validator->errors()->toArray(),
                    422
                );
            }

            $category = Category::where('slug', $categorySlug)->firstOrFail();
            $perPage = $request->input('limit', 15);
            $offset = $request->input('offset', 0);
            $total = Subscription::where('category_id', $category->id)->count();

            $subscriptions = Subscription::with('user')
                ->where('category_id', $category->id)
                ->offset($offset)
                ->limit($perPage)
                ->get()
                ->map(function ($sub) {
                    return [
                        'user_id' => $sub->user_id,
                        'user_name' => $sub->user_name,
                        'user_email' => $sub->user->email,
                        'unsubscribe_key' => $sub->unsubscribe_key,
                        'subscribed_at' => $sub->created_at->toDateTimeString()
                    ];
                });

            return $this->success(
                'Список подписчиков получен',
                [
                    'meta' => [
                        'limit' => $perPage,
                        'offset' => $offset,
                        'total' => $total
                    ],
                    'category' => $category->slug,
                    'subscribers' => $subscriptions
                ]
            );

        } catch (ModelNotFoundException $e) {
            return $this->error(
                'Рубрика не найдена',
                ['category' => $categorySlug],
                404
            );
        } catch (\Exception $e) {
            return $this->error(
                'Ошибка при получении списка подписчиков',
                ['system' => config('app.debug') ? $e->getMessage() : null],
                500
            );
        }
    }

    /**
     * Успешный ответ
     */
    protected function success($message, $data = [], $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Ответ с ошибкой
     */
    protected function error($message, $errors = [], $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
