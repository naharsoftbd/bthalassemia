<?php

namespace Tests\Feature\Api\V1\Orders;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\ProductVariant;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $vendor;
    protected $order;
    protected $product;
    protected $token;
    protected $CustomerUser;
    protected $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Vendor');
        $this->vendor = Vendor::factory()->create(['user_id' => $this->user->id]);
        $this->product = Product::factory()->create(['vendor_id' => $this->vendor->id, 'is_approved' => true]);
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'price' => 100.00,
            'sku' => 'TESTSKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->CustomerUser = User::factory()->create();
        $this->CustomerUser->assignRole('Customer');
        $this->actingAs($this->CustomerUser, 'api');

        $this->order = Order::factory()->create([
            'user_id' => $this->CustomerUser->id,
            'customer_email' => $this->CustomerUser->email,
        ]);

        $this->order->items()->create([
            'product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'product_name' => 'Test Product',
            'sku' => 'TEST-SKU',
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200,
        ]);



        // Generate JWT token for the user
        $this->token = JWTAuth::fromUser($this->CustomerUser);
    }

    
    public function test_can_list_orders_for_authenticated_user()
    {
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'customer_email' => $this->user->email,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Orders retrieved successfully.'
            ]);
    }

    
    public function test_can_show_an_order()
    {
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/orders/{$this->order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                    'created_at',
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->order->id,
                ],
                'message' => 'Order retrieved successfully.'
            ]);
    }

    
    public function test_returns_404_when_showing_non_existent_order()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/orders/9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found or unauthorized.'
            ]);
    }

    
    public function test_can_create_an_order()
    {
        $subtotal = $this->variant->price * 2;
        $orderData = [
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total' => $subtotal,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_variant_id' => $this->variant->id,
                    'vendor_id' => $this->vendor->id,
                    'product_name' => $this->product->name,
                    'variant_name' => $this->variant->name,
                    'sku' => $this->variant->sku,
                    'unit_price' => $this->variant->price,
                    'quantity' => 2,
                    'total_price' => 200.00,
                ]
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip_code' => '12345',
                'country' => 'Test Country'
            ],
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip_code' => '12345',
                'country' => 'Test Country'
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/orders', $orderData);
        $response->json();
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully.'
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->CustomerUser->id,
            'status' => 'Pending'
        ]);
    }

    
    public function test_validates_required_fields_when_creating_order()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items', 'shipping_address', 'billing_address']);
    }

    
    public function test_can_update_an_order()
    {
        $updateData = [
            'status' => 'Processing',
            'shipping_address' => [
                'street' => '456 Updated St',
                'city' => 'Updated City',
                'state' => 'US',
                'zip_code' => '54321',
                'country' => 'Updated Country'
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/v1/orders/{$this->order->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order updated successfully.'
            ]);
    }

    
    public function test_can_update_order_status()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson("/api/v1/orders/{$this->order->id}/status", [
            'status' => 'Processing',
            'notes' => 'Starting order processing'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order Status updated successfully.'
            ]);
    }

    
    public function test_can_confirm_an_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_email' => $this->user->email,
            'status' => 'Pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/orders/{$order->id}/confirm", [
            'notes' => 'Order confirmed'
        ]);

        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }

    
    public function test_can_cancel_an_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_email' => $this->user->email,
            'status' => 'Pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Customer requested cancellation'
        ]);

        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }

    
    public function test_can_cancel_vendor_order_items()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_email' => $this->user->email,
            'status' => 'Confirmed'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/orders/{$order->id}/cancel-vendor-items", [
            'reason' => 'Out of stock'
        ]);

        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }

    
    public function test_returns_unauthorized_without_jwt_token()
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }

    
    public function test_returns_unauthorized_with_invalid_jwt_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }

    
    public function test_returns_unauthorized_for_other_users_orders()
    {
        $otherUser = User::factory()->create();
        $otherUserOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'customer_email' => $otherUser->email,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/orders/{$otherUserOrder->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Order not found or unauthorized.'
            ]);
    }

    
    public function test_handles_order_creation_failure_gracefully()
    {
    
        $orderData = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_variant_id' => $this->variant->id,
                    'vendor_id' => $this->vendor->id,
                    'product_name' => $this->product->name,
                    'variant_name' => $this->variant->name,
                    'sku' => $this->variant->sku,
                    'unit_price' => $this->variant->price,
                    'quantity' => 2,
                    'total_price' => 200.00,
                ]
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip_code' => '12345',
                'country' => 'Test Country'
            ],
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip_code' => '12345',
                'country' => 'Test Country'
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }

   
    public function test_can_filter_orders_by_status()
    {
        Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_email' => $this->user->email,
            'status' => 'Confirmed'
        ]);

        Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_email' => $this->user->email,
            'status' => 'Cancelled'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/orders?status=Confirmed');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}