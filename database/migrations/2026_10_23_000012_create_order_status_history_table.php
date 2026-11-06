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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE order_status_history ADD CONSTRAINT order_status_history_from_status_check CHECK (from_status IS NULL OR from_status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE order_status_history ADD CONSTRAINT order_status_history_to_status_check CHECK (to_status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
