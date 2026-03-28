<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_drafts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('channel', 32)->default('pos');
            $table->string('status', 32)->default('draft');
            $table->text('notes')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('sale_draft_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_draft_id')->constrained('sale_drafts')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('name_snapshot');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('quantity', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->string('status', 32)->default('completed');
            $table->string('document_type', 32)->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2);
            $table->decimal('paid_total', 14, 2)->default(0);
            $table->decimal('change_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestampTz('sold_at');
            $table->timestampsTz();
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('name_snapshot');
            $table->string('sku_snapshot')->nullable();
            $table->decimal('unit_price', 14, 2);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->decimal('quantity', 14, 2);
            $table->decimal('line_subtotal', 14, 2);
            $table->decimal('line_tax', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->json('metadata')->nullable();
            $table->timestampsTz();
        });

        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('method', 32);
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->timestampTz('paid_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('sale_draft_items');
        Schema::dropIfExists('sale_drafts');
    }
};
