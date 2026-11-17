<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->unique(['company_id', 'id']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['company_id', 'id']);
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'id']);
            $table->unique(['company_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name', 180);
            $table->string('sku', 80);
            $table->string('barcode', 100)->nullable();
            $table->string('unit', 24)->default('pcs');
            $table->decimal('sale_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'id']);
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'name']);
            $table->foreign(['company_id', 'category_id'])
                ->references(['company_id', 'id'])
                ->on('product_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'id']);
            $table->unique(['company_id', 'phone']);
            $table->index(['company_id', 'name']);
        });

        Schema::create('branch_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'branch_id', 'product_id']);
            $table->foreign(['company_id', 'branch_id'])
                ->references(['company_id', 'id'])
                ->on('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('invoice_number', 40);
            $table->string('customer_name', 160)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('line_discount_total', 12, 2)->default(0);
            $table->string('order_discount_type', 12)->default('flat');
            $table->decimal('order_discount_value', 12, 2)->default(0);
            $table->decimal('order_discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->decimal('due_total', 12, 2)->default(0);
            $table->string('payment_status', 16)->default('unpaid');
            $table->string('status', 16)->default('completed');
            $table->text('note')->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();
            $table->unique(['company_id', 'id']);
            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'branch_id', 'sold_at']);
            $table->index(['company_id', 'customer_id', 'payment_status']);
            $table->foreign(['company_id', 'branch_id'])
                ->references(['company_id', 'id'])
                ->on('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign(['company_id', 'customer_id'])
                ->references(['company_id', 'id'])
                ->on('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name', 180);
            $table->string('sku', 80);
            $table->string('barcode', 100)->nullable();
            $table->string('unit', 24);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->string('discount_type', 12)->default('flat');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->foreign(['company_id', 'sale_id'])
                ->references(['company_id', 'id'])
                ->on('sales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('sale_id');
            $table->foreignId('received_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('method', 24);
            $table->decimal('amount', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->foreign(['company_id', 'sale_id'])
                ->references(['company_id', 'id'])
                ->on('sales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->index(['company_id', 'sale_id', 'paid_at']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('movement_type', 24);
            $table->decimal('quantity_delta', 12, 3);
            $table->decimal('balance_after', 12, 3);
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at');
            $table->foreign(['company_id', 'branch_id'])
                ->references(['company_id', 'id'])
                ->on('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign(['company_id', 'sale_id'])
                ->references(['company_id', 'id'])
                ->on('sales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->index(['company_id', 'branch_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('branch_stocks');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'id']);
        });
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'id']);
        });
    }
};
