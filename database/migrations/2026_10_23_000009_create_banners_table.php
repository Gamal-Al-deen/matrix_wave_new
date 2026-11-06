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
        Schema::create('banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 200)->nullable();
            $table->string('image_storage_path', 500);
            $table->string('image_public_url', 1000)->nullable();
            $table->string('alt_text', 255);
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampsTz();

            $table->index(['is_active', 'sort_order']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE banners ADD CONSTRAINT banners_sort_order_check CHECK (sort_order >= 0)');
            DB::statement('ALTER TABLE banners ADD CONSTRAINT banners_schedule_check CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at > starts_at)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
