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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->smallInteger('id')->primary();
            $table->string('site_name', 150);
            $table->string('logo_storage_path', 500)->nullable();
            $table->string('logo_public_url', 1000)->nullable();
            $table->text('description')->nullable();
            $table->string('currency_code', 12);
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE site_settings ADD CONSTRAINT site_settings_single_row_check CHECK (id = 1)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
