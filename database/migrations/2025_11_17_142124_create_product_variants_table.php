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

            $table->decimal('price', 10, 2)->nullable(); // overrides product price if set

            $table->integer('stock')->default(0);         // real-time inventory
            $table->integer('low_stock_threshold')->default(5); // alert threshold

            $table->boolean('low_stock_notified')->default(false);
            // (Used so your queue job does not spam notifications repeatedly)

            $table->json('attributes')->nullable(); // e.g. size, color, package type

            $table->boolean('is_active')->default(true);

            $table->timestamps();
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
