<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->nullable()->unique();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('tax_resolution_id')->nullable()->constrained('tax_resolutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('issued');
            $table->unsignedInteger('sequence_number');
            $table->string('credit_note_number', 120)->unique();
            $table->string('invoice_number_reference', 80);
            $table->string('authorization_number_snapshot', 120)->nullable();
            $table->string('company_name_snapshot');
            $table->string('company_tax_id_snapshot')->nullable();
            $table->string('customer_name_snapshot');
            $table->string('customer_document_type_snapshot', 32)->nullable();
            $table->string('customer_document_number_snapshot', 120)->nullable();
            $table->text('reason');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->text('footer')->nullable();
            $table->string('legend', 500)->nullable();
            $table->timestampTz('issued_at');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->unique('sale_return_id');
            $table->unique(['invoice_id', 'sequence_number']);
            $table->index(['issued_at', 'status']);
        });

        Schema::create('credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('credit_notes')->cascadeOnDelete();
            $table->foreignId('sale_return_item_id')->nullable()->constrained('sale_return_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->string('sku_snapshot')->nullable();
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_subtotal', 14, 2);
            $table->decimal('line_tax', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->string('reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
