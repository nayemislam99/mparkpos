<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type', 50)->default('regular')->after('customer_group');
                $table->index(['company_id', 'customer_type']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'customer_type')) {
                $table->dropIndex(['company_id', 'customer_type']);
                $table->dropColumn('customer_type');
            }
        });
    }
};
