<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'nid')) {
                $table->string('nid', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('customers', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable()->after('address');
            }
            if (! Schema::hasColumn('customers', 'city')) {
                $table->string('city', 100)->nullable()->after('address_line_2');
            }
            if (! Schema::hasColumn('customers', 'customer_group')) {
                $table->string('customer_group', 50)->default('retail')->after('city');
            }
            if (! Schema::hasColumn('customers', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('customers', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        if (! Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->nullOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('received_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('method', 30)->default('cash');
                $table->string('reference_no', 100)->nullable();
                $table->text('note')->nullable();
                $table->timestamp('paid_at');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'customer_id', 'paid_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');

        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'nid')) {
                $table->dropColumn('nid');
            }
            if (Schema::hasColumn('customers', 'address_line_2')) {
                $table->dropColumn('address_line_2');
            }
            if (Schema::hasColumn('customers', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('customers', 'customer_group')) {
                $table->dropColumn('customer_group');
            }
            if (Schema::hasColumn('customers', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('customers', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
