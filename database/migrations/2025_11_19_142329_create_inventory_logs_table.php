<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('quantity'); // Negative for deductions, positive for additions
            $table->enum('action', ['deduct', 'restore', 'adjust', 'return']);
            $table->enum('reason', ['order_confirmation', 'order_cancellation', 'manual_adjustment', 'return', 'damage']);
            $table->text('notes')->nullable();
            $table->integer('previous_stock')->nullable();
            $table->integer('new_stock')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index('order_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};