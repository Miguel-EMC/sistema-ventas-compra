<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_profile_id')->nullable()->constrained('company_profiles')->nullOnDelete();
            $table->string('name');
            $table->string('authorization_number', 120);
            $table->string('series', 32)->nullable();
            $table->unsignedInteger('invoice_number_start')->default(1);
            $table->unsignedInteger('invoice_number_end');
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->string('technical_key')->nullable();
            $table->string('legend', 500)->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['is_active', 'starts_at']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->nullable()->unique();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('tax_resolution_id')->nullable()->constrained('tax_resolutions')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status', 32)->default('issued');
            $table->unsignedInteger('sequence_number');
            $table->string('invoice_number', 80);
            $table->string('authorization_number_snapshot', 120)->nullable();
            $table->string('company_name_snapshot');
            $table->string('company_tax_id_snapshot')->nullable();
            $table->string('customer_name_snapshot');
            $table->string('customer_document_type_snapshot', 32)->nullable();
            $table->string('customer_document_number_snapshot', 120)->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->text('footer')->nullable();
            $table->string('legend', 500)->nullable();
            $table->timestampTz('issued_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->unique('sale_id');
            $table->unique(['tax_resolution_id', 'sequence_number']);
            $table->index(['issued_at', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->string('sku_snapshot')->nullable();
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_subtotal', 14, 2);
            $table->decimal('line_tax', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tax_resolutions');
    }
};
