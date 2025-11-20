<?php

namespace Tests\Feature\Api\V1\Products;

use App\Models\Product;
use App\Models\User;
use App\Services\Products\ProductService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected $serviceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable ALL middleware first (Laravel 12 requires this)
        $this->withoutMiddleware();

        // Then disable specific JWT middleware aliases
        $this->withoutMiddleware([
            \Tymon\JWTAuth\Http\Middleware\Authenticate::class, // jwt.verify
            \App\Http\Middleware\JwtRefreshMiddleware::class,    // jwt.refresh
        ]);

        // Seed permissions
        $this->seed(PermissionSeeder::class);

        // Auth user with roles
        $this->user = User::factory()->create();
        $this->user->assignRole('Admin');
        $this->actingAs($this->user, 'api');

        // Mock ProductService
        $this->serviceMock = $this->createMock(ProductService::class);
        $this->app->instance(ProductService::class, $this->serviceMock);

        // Allow permissions
        Gate::shouldReceive('check')->andReturn(true);
    }

    public function test_lists_products()
    {
        $fakeProducts = Product::factory()->count(2)->make();

        $this->serviceMock
            ->expects($this->once())
            ->method('list')
            ->withAnyParameters()
            ->willReturn($fakeProducts);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_product()
    {
        $payload = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'test desc',
            'base_price' => 120,
        ];

        $fakeProduct = Product::factory()->make($payload);

        $this->serviceMock
            ->expects($this->once())
            ->method('create')
            ->with($payload)
            ->willReturn($fakeProduct);

        $response = $this->postJson('/api/v1/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Product');
    }

    public function test_shows_a_product()
    {
        $product = Product::factory()->create();

        $product->variants()->createMany([
            ['name' => 'Variant 1', 'price' => 100, 'sku' => 'Variant 1'],
            ['name' => 'Variant 2', 'price' => 120, 'sku' => 'Variant 2'],
        ]);

        $this->serviceMock
            ->expects($this->once())
            ->method('find')
            ->withAnyParameters()
            ->willReturn($product);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(201)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_updates_a_product()
    {
        $product = Product::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'description' => 'Updated desc',
            'base_price' => 500,
        ];

        $updatedProduct = Product::factory()->make($payload);

        $this->serviceMock
            ->expects($this->once())
            ->method('update')
            ->with($product->id, $payload)
            ->willReturn($updatedProduct);

        $response = $this->putJson("/api/v1/products/{$product->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_deletes_a_product()
    {
        $product = Product::factory()->create();

        $this->serviceMock
            ->expects($this->once())
            ->method('delete')
            ->with($product->id)
            ->willReturn(true);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Deleted');
    }
}
