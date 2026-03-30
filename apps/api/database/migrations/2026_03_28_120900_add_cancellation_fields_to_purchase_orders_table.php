<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->foreignId('cancelled_by_id')->nullable()->after('received_by_id')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('notes');
            $table->timestampTz('cancelled_at')->nullable()->after('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancelled_by_id');
            $table->dropColumn(['cancellation_reason', 'cancelled_at']);
        });
    }
};
