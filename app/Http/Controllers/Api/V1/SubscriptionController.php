<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
            $category = Category::where('slug', $request->category)->firstOrFail();

            $subscription = Subscription::firstOrCreate([
                'user_id' => $user->id,
                'category_id' => $category->id,
            ]);

            return $this->success(
                'Вы успешно подписались на рубрику',
                [
                    'subscription' => $subscription,
                    'category' => $category->name
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
    public function unsubscribe($categorySlug)
    {
        try {
            $category = Category::where('slug', $categorySlug)->firstOrFail();

            $deleted = Subscription::where('user_id', Auth::id())
                ->where('category_id', $category->id)
                ->delete();

            if ($deleted) {
                return $this->success(
                    'Вы отписались от рубрики ' . $category->name,
                    ['category' => $category->slug]
                );
            }

            return $this->error(
                'Подписка не найдена',
                ['category' => $categorySlug],
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
     * Удаление всех подписок пользователя
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
     * Список подписок текущего пользователя
     */
    public function listSubscriptions(Request $request)
    {
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

        try {
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $subscriptions = Subscription::with(['user', 'category'])
                ->where('user_id', Auth::id())
                ->skip($offset)
                ->take($limit)
                ->get();

            return $this->success(
                'Список подписок получен',
                [
                    'meta' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => Subscription::where('user_id', Auth::id())->count()
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
     * Список подписчиков конкретной рубрики
     */
    public function listCategorySubscribers($categorySlug, Request $request)
    {
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

        try {
            $category = Category::where('slug', $categorySlug)->firstOrFail();
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $subscribers = Subscription::with(['user', 'category'])
                ->where('category_id', $category->id)
                ->skip($offset)
                ->take($limit)
                ->get();

            return $this->success(
                'Список подписчиков получен',
                [
                    'meta' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => Subscription::where('category_id', $category->id)->count()
                    ],
                    'subscribers' => $subscribers,
                    'category' => $category->name
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
}
