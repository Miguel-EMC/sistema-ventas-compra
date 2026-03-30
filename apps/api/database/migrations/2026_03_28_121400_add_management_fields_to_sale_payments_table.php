<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->unique()->after('id');
            $table->foreignId('cash_session_id')->nullable()->after('sale_id')->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('cash_session_id')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('reference');
            $table->json('metadata')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('cash_session_id');
            $table->dropUnique('sale_payments_public_id_unique');
            $table->dropColumn(['public_id', 'notes', 'metadata']);
        });
    }
};
