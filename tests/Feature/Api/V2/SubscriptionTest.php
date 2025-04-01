<?php

namespace Tests\Feature\Api\V2;

use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
     * Тест успешной подписки пользователя на рубрику (v2)
     */
    public function test_subscribes_user_to_category()
    {
        $response = $this->postJson('/api/v2/subscriptions', [
            'email' => $this->user->email,
            'category' => $this->category->slug,
            'name' => 'Test User'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'unsubscribe_key',
                    'category',
                    'user_name'
                ]
            ]);
    }

    /**
     * Тест валидации входных данных при подписке (v2)
     */
    public function test_validation_on_subscription()
    {
        $response = $this->postJson('/api/v2/subscriptions', [
            'email' => 'invalid-email',
            'category' => '',
            'name' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'category', 'name']);
    }

    /**
     * Тест подписки с несуществующим email (v2)
     */
    public function test_subscribe_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v2/subscriptions', [
            'email' => 'nonexistent@example.com',
            'category' => $this->category->slug,
            'name' => 'Test User'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Тест подписки на несуществующую категорию (v2)
     */
    public function test_subscribe_with_nonexistent_category()
    {
        $response = $this->postJson('/api/v2/subscriptions', [
            'email' => $this->user->email,
            'category' => 'nonexistent-category',
            'name' => 'Test User'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    /**
     * Тест получения списка подписок пользователя (v2)
     */
    public function test_lists_user_subscriptions()
    {
        // Используем фабрику с уникальными ключами
        Subscription::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'unsubscribe_key' => function() {
                return Str::uuid();
            }
        ]);

        $response = $this->getJson('/api/v2/subscriptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'meta' => ['limit', 'offset', 'total'],
                    'subscriptions' => [
                        '*' => ['id', 'category', 'user_name', 'unsubscribe_key']
                    ]
                ]
            ]);
    }

    /**
     * Тест отписки от конкретной категории (v2)
     */
    public function test_unsubscribes_from_category()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'unsubscribe_key' => $key = Str::uuid()
        ]);

        $response = $this->deleteJson("/api/v2/subscriptions/{$this->category->slug}/{$key}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    /**
     * Тест отписки с неверным ключом (v2)
     */
    public function test_unsubscribe_with_invalid_key()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'unsubscribe_key' => Str::uuid()
        ]);

        $response = $this->deleteJson("/api/v2/subscriptions/{$this->category->slug}/invalid-key");

        $response->assertStatus(422);
    }

    /**
     * Тест массовой отписки от всех категорий (v2)
     */
    public function test_unsubscribes_from_all_categories()
    {
        Subscription::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'unsubscribe_key' => function() {
                return Str::uuid();
            }
        ]);

        $response = $this->deleteJson('/api/v2/subscriptions');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $this->user->id]);
    }

    /**
     * Тест генерации нового ключа отписки (v2)
     */
    public function test_generates_unsubscribe_key()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'unsubscribe_key' => Str::uuid()
        ]);

        $response = $this->getJson("/api/v2/subscriptions/unsubscribe-key/{$this->category->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['unsubscribe_key', 'category']
            ]);
    }

    /**
     * Тест получения списка подписчиков категории (v2)
     */
    public function test_lists_category_subscribers()
    {
        Subscription::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'unsubscribe_key' => function() {
                return Str::uuid();
            }
        ]);

        $response = $this->getJson("/api/v2/subscriptions/category/{$this->category->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'meta' => ['limit', 'offset', 'total'],
                    'subscribers' => [
                        '*' => ['user_id', 'user_name', 'user_email']
                    ]
                ]
            ]);
    }
}
