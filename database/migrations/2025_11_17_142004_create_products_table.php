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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Vendor relationship (nullable for admin-created products)
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('cascade');

            // Basic product info
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            // Pricing
            $table->decimal('base_price', 10, 2)->nullable(); // optional, variant might override

            // Status & ownership
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['draft', 'published', 'pending_review'])->default('draft');
            $table->boolean('is_approved')->default(false); // For vendor products

             // Searchable metadata
            $table->json('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for search and performance
            $table->index('name');
            $table->index('slug');
            $table->index('is_active');
            $table->index('status');
            $table->index('is_approved');
            $table->index('vendor_id');
            $table->index(['is_active', 'status']);
            $table->index(['vendor_id', 'is_active']);
            
            // Full-text indexes for search
            $table->fullText(['name', 'description', 'short_description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
