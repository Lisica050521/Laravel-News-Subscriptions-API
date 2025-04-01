<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Subscription routes
        Route::prefix('subscriptions')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'subscribe']);
            Route::delete('/{category}', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'unsubscribe']);
            Route::delete('/', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'unsubscribeAll']);
            Route::get('/', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'listSubscriptions']);
            Route::get('/category/{category}', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'listCategorySubscribers']);
        });
    });
});

Route::prefix('v2')->group(function () {
    // Auth routes
    Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Subscription routes
        Route::prefix('subscriptions')->group(function () {
            // Подписка (с обязательным именем пользователя в V2)
            Route::post('/', [\App\Http\Controllers\Api\V2\SubscriptionController::class, 'subscribe']);

            // Управление подписками
            Route::delete('/{categorySlug}/{unsubscribeKey}', [\App\Http\Controllers\Api\V2\SubscriptionController::class, 'unsubscribe']);
            Route::delete('/', [\App\Http\Controllers\Api\V2\SubscriptionController::class, 'unsubscribeAll']);

            // Получение информации
            Route::get('/', [\App\Http\Controllers\Api\V2\SubscriptionController::class, 'listSubscriptions']);
            Route::get('/category/{categorySlug}', [\App\Http\Controllers\Api\V2\SubscriptionController::class, 'listCategorySubscribers']);

            // Управление ключами отписки (новый функционал V2)
            Route::get('/unsubscribe-key/{categorySlug}', [\App\Http\Controllers\Api\V2\SubscriptionController::class, 'generateUnsubscribeKey']);
        });
    });
});
