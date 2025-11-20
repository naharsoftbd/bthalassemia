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
    
    echo "=== COMPLETE DEBUG ===\n";
    
    // 1. Check what's actually in the database
    $dbProduct = Product::find($product->id);
    echo "Raw DB Product: ";
    print_r($dbProduct->toArray());
    echo "\n";
    
    // 2. Check if factory is working
    echo "Factory defined fields:\n";
    $newProduct = Product::factory()->make();
    print_r($newProduct->toArray());
    echo "\n";
    
    // 3. Check variants
    $variants = $product->variants()->get();
    echo "Variants count: " . $variants->count() . "\n";
    print_r($variants->toArray());
    
    $url = route('products.show', $product);
    $response = $this->getJson($url);

    echo "API Response:\n";
    print_r($response->json());
    echo "\n=== END DEBUG ===\n";

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $product->id);
}

    
    public function test_updates_a_product()
    {
        $product = Product::factory()->create();

        $payload = [
            'title' => 'Updated Name',
            'description' => 'Updated desc',
            'category_id' => 1,
            'price' => 500,
        ];

        $updatedProduct = Product::factory()->make($payload);

        $this->serviceMock
            ->expects($this->once())
            ->method('update')
            ->with($product->id, $payload)
            ->willReturn($updatedProduct);

        $response = $this->putJson("/api/v1/products/{$product->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Name');
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

    
    public function test_denies_access_without_permission()
    {
        $this->seed(PermissionSeeder::class); // make sure permissions exist

        $user = User::factory()->create(); // user has no permissions
        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(403);
    } 
}
