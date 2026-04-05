<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCompanyId('product_categories');
        $this->addCompanyId('products');
        $this->addCompanyId('asset_categories');
        $this->addCompanyId('assets');
        $this->addCompanyId('customers');
        $this->addCompanyId('suppliers');
        $this->addCompanyId('cash_registers');
        $this->addCompanyId('cash_sessions');
        $this->addCompanyId('cash_movements');
        $this->addCompanyId('sale_drafts');
        $this->addCompanyId('sales');
        $this->addCompanyId('purchase_orders');

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropUnique('product_categories_name_unique');
            $table->dropUnique('product_categories_slug_unique');
            $table->unique(['company_id', 'name'], 'product_categories_company_name_unique');
            $table->unique(['company_id', 'slug'], 'product_categories_company_slug_unique');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_sku_unique');
            $table->dropUnique('products_barcode_unique');
            $table->unique(['company_id', 'sku'], 'products_company_sku_unique');
            $table->unique(['company_id', 'barcode'], 'products_company_barcode_unique');
        });

        Schema::table('asset_categories', function (Blueprint $table): void {
            $table->dropUnique('asset_categories_name_unique');
            $table->dropUnique('asset_categories_slug_unique');
            $table->unique(['company_id', 'name'], 'asset_categories_company_name_unique');
            $table->unique(['company_id', 'slug'], 'asset_categories_company_slug_unique');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropUnique('assets_code_unique');
            $table->unique(['company_id', 'code'], 'assets_company_code_unique');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_document_number_unique');
            $table->unique(['company_id', 'document_number'], 'customers_company_document_unique');
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropUnique('suppliers_document_number_unique');
            $table->unique(['company_id', 'document_number'], 'suppliers_company_document_unique');
        });

        Schema::table('cash_registers', function (Blueprint $table): void {
            $table->dropUnique('cash_registers_name_unique');
            $table->dropUnique('cash_registers_code_unique');
            $table->unique(['company_id', 'name'], 'cash_registers_company_name_unique');
            $table->unique(['company_id', 'code'], 'cash_registers_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table): void {
            $table->dropUnique('cash_registers_company_name_unique');
            $table->dropUnique('cash_registers_company_code_unique');
            $table->unique('name');
            $table->unique('code');
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropUnique('suppliers_company_document_unique');
            $table->unique('document_number');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_company_document_unique');
            $table->unique('document_number');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropUnique('assets_company_code_unique');
            $table->unique('code');
        });

        Schema::table('asset_categories', function (Blueprint $table): void {
            $table->dropUnique('asset_categories_company_name_unique');
            $table->dropUnique('asset_categories_company_slug_unique');
            $table->unique('name');
            $table->unique('slug');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_company_sku_unique');
            $table->dropUnique('products_company_barcode_unique');
            $table->unique('sku');
            $table->unique('barcode');
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropUnique('product_categories_company_name_unique');
            $table->dropUnique('product_categories_company_slug_unique');
            $table->unique('name');
            $table->unique('slug');
        });

        $this->dropCompanyId('purchase_orders');
        $this->dropCompanyId('sales');
        $this->dropCompanyId('sale_drafts');
        $this->dropCompanyId('cash_movements');
        $this->dropCompanyId('cash_sessions');
        $this->dropCompanyId('cash_registers');
        $this->dropCompanyId('suppliers');
        $this->dropCompanyId('customers');
        $this->dropCompanyId('assets');
        $this->dropCompanyId('asset_categories');
        $this->dropCompanyId('products');
        $this->dropCompanyId('product_categories');
    }

    private function addCompanyId(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignUuid('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });
    }

    private function dropCompanyId(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'company_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
