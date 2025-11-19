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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('sku')->unique();
            $table->string('name'); // e.g., "500mg", "Blue - Large"

            // Pricing & inventory
            $table->decimal('price', 10, 2)->nullable(); // overrides product price if set
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);         // real-time inventory
            $table->integer('low_stock_threshold')->default(5); // alert threshold

            $table->boolean('low_stock_notified')->default(false);
            // (Used so your queue job does not spam notifications repeatedly)

            // Variant specific
            $table->json('attributes')->nullable(); // e.g. size, color, package type
            $table->string('barcode')->nullable();
            $table->decimal('weight', 8, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('sku');
            $table->index('name');
            $table->index('price');
            $table->index('stock');
            $table->index('is_active');
            $table->index(['product_id', 'is_active']);
            $table->index(['sku', 'is_active']);

            // Full-text search
            $table->fullText(['sku', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
