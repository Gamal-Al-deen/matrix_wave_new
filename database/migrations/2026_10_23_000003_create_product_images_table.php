<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('storage_path', 500)->unique();
            $table->string('public_url', 1000)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100);
            $table->bigInteger('size_bytes');
            $table->integer('sort_order')->default(0);
            $table->string('alt_text', 255)->nullable();
            $table->timestampsTz();

            $table->index(['product_id', 'sort_order']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_images ADD CONSTRAINT product_images_size_bytes_check CHECK (size_bytes > 0)');
            DB::statement('ALTER TABLE product_images ADD CONSTRAINT product_images_sort_order_check CHECK (sort_order >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
