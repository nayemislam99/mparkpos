<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('type', 'company_admin')->update(['type' => 'store_admin']);
        DB::table('users')->where('type', 'staff')->update(['type' => 'cashier']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('type', 30)->default('cashier')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('type', ['store_admin', 'manager', 'inventory_manager'])
            ->update(['type' => 'company_admin']);
        DB::table('users')->where('type', 'cashier')->update(['type' => 'staff']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('type', 30)->default('staff')->change();
        });
    }
};
