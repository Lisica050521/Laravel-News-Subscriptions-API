<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'test@example.com']);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->category = Category::factory()->create(['slug' => 'technology']);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Тест успешной подписки пользователя на категорию
     */
    public function test_subscribes_user_to_category()
    {
        $response = $this->postJson('/api/v1/subscriptions', [
            'email' => $this->user->email,
            'category' => $this->category->slug
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'subscription' => ['user_id', 'category_id'],
                    'category'
                ]
            ]);
    }

    /**
     * Тест валидации входных данных при подписке
     */
    public function test_validation_on_subscription()
    {
        $response = $this->postJson('/api/v1/subscriptions', [
            'email' => 'invalid-email',
            'category' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'category']);
    }

    /**
     * Тест подписки с несуществующим email
     */
    public function test_subscribe_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/subscriptions', [
            'email' => 'nonexistent@example.com',
            'category' => $this->category->slug
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Тест подписки на несуществующую категорию
     */
    public function test_subscribe_with_nonexistent_category()
    {
        $response = $this->postJson('/api/v1/subscriptions', [
            'email' => $this->user->email,
            'category' => 'nonexistent-category'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => [
                    'category' => [
                        'The selected category is invalid.'
                    ]
                ]
            ]);
    }

    /**
     * Тест получения списка подписок пользователя
     */
    public function test_lists_user_subscriptions()
    {
        Subscription::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/v1/subscriptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subscriptions' => [
                        '*' => ['id', 'user_id', 'category_id']
                    ]
                ]
            ]);
    }

    /**
     * Тест отписки от конкретной категории
     */
    public function test_unsubscribes_from_category()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ]);

        $response = $this->deleteJson("/api/v1/subscriptions/{$this->category->slug}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    /**
     * Тест массовой отписки от всех категорий
     */
    public function test_unsubscribes_from_all_categories()
    {
        Subscription::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson('/api/v1/subscriptions');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $this->user->id]);
    }

    /**
     * Тест получения списка подписчиков категории
     */
    public function test_lists_category_subscribers()
    {
        Subscription::factory()->count(3)->create([
            'category_id' => $this->category->id
        ]);

        $response = $this->getJson("/api/v1/subscriptions/category/{$this->category->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subscribers' => [
                        '*' => ['id', 'user_id']
                    ]
                ]
            ]);
    }
}
