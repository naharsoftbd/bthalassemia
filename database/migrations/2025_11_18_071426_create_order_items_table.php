<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            
            // Order relationship
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            // Product and variant relationships
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');
            
            // Vendor relationship - this is crucial for vendor order management
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            
            // Item details (snapshot at time of order)
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku');
            $table->text('attributes')->nullable(); // JSON for variant attributes
            
            // Pricing
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            
            // Quantity
            $table->integer('quantity');
            
            // Vendor-specific status
            $table->enum('fulfillment_status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            
            // Timestamps
            $table->timestamps();

            // Indexes for vendor order management
            $table->index('order_id');
            $table->index('product_id');
            $table->index('vendor_id');
            $table->index('fulfillment_status');
            $table->index(['vendor_id', 'fulfillment_status']);
            $table->index(['vendor_id', 'created_at']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};