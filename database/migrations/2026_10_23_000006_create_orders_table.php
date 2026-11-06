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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 40)->unique();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('unpaid');
            $table->foreignUuid('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->string('currency_code', 12);
            $table->string('customer_name_snapshot', 150);
            $table->string('customer_phone_snapshot', 40);
            $table->string('customer_email_snapshot', 255)->nullable();
            $table->text('delivery_address_snapshot');
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestampTz('whatsapp_opened_at')->nullable();
            $table->timestampsTz();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'))");
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_status_check CHECK (payment_status IN ('unpaid', 'awaiting_verification', 'partially_paid', 'paid', 'refunded'))");
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_subtotal_check CHECK (subtotal >= 0)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_delivery_fee_check CHECK (delivery_fee >= 0)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_check CHECK (total = subtotal + delivery_fee)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
