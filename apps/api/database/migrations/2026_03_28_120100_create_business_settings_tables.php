<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('tax_id')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique();
            $table->string('symbol', 8);
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
        });

        Schema::create('locales', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
        });

        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('locales');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('company_profiles');
    }
};
