<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')
                    ->constrained('branches')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('customers', 'advance_balance')) {
                $table->decimal('advance_balance', 12, 2)->default(0)->after('notes');
            }

            if (! Schema::hasColumn('customers', 'due_limit')) {
                $table->decimal('due_limit', 12, 2)->default(0)->after('advance_balance');
            }

            if (! Schema::hasColumn('customers', 'opening_balance')) {
                $table->decimal('opening_balance', 12, 2)->default(0)->after('due_limit');
            }
        });

        if (! Schema::hasTable('company_role_permissions')) {
            Schema::create('company_role_permissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->nullOnDelete();
                $table->string('role', 50);
                $table->json('permissions');
                $table->timestamps();

                $table->unique(['company_id', 'branch_id', 'role'], 'comp_branch_role_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_role_permissions');

        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('customers', 'advance_balance')) {
                $table->dropColumn('advance_balance');
            }
            if (Schema::hasColumn('customers', 'due_limit')) {
                $table->dropColumn('due_limit');
            }
            if (Schema::hasColumn('customers', 'opening_balance')) {
                $table->dropColumn('opening_balance');
            }
        });
    }
};
