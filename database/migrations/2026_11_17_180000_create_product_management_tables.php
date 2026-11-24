<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Brands table
        if (! Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['company_id', 'id']);
                $table->unique(['company_id', 'name']);
            });
        }

        // 2. Unit Types table
        if (! Schema::hasTable('unit_types')) {
            Schema::create('unit_types', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
                $table->string('name', 60);
                $table->string('code', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['company_id', 'id']);
                $table->unique(['company_id', 'name']);
            });
        }

        // 3. Variations table
        if (! Schema::hasTable('variations')) {
            Schema::create('variations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
                $table->string('name', 60);
                $table->json('items')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['company_id', 'id']);
                $table->unique(['company_id', 'name']);
            });
        }

        // 4. Product Variations table
        if (! Schema::hasTable('product_variations')) {
            Schema::create('product_variations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
                $table->unsignedBigInteger('product_id');
                $table->string('name', 120);
                $table->decimal('cost_price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->default(0);
                $table->string('barcode', 100)->nullable();
                $table->string('sku', 80)->nullable();
                $table->timestamps();
                $table->foreign(['company_id', 'product_id'])
                    ->references(['company_id', 'id'])
                    ->on('products')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }

        // 5. Barcode Print Logs table
        if (! Schema::hasTable('barcode_print_logs')) {
            Schema::create('barcode_print_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('product_variation_id')->nullable();
                $table->foreignId('printed_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->string('lot', 60)->nullable();
                $table->string('variant_name', 120)->nullable();
                $table->string('variation', 120)->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('price', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        // 6. Expand products table with additional fields for Screenshot 2
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('products', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('brand_id');
            }
            if (! Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('barcode');
            }
            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('image');
            }
            if (! Schema::hasColumn('products', 'alert_quantity')) {
                $table->decimal('alert_quantity', 12, 2)->default(5)->after('track_stock');
            }
            if (! Schema::hasColumn('products', 'has_warranty')) {
                $table->boolean('has_warranty')->default(false)->after('alert_quantity');
            }
            if (! Schema::hasColumn('products', 'discount_status')) {
                $table->string('discount_status', 20)->default('no')->after('has_warranty');
            }
            if (! Schema::hasColumn('products', 'vat_status')) {
                $table->boolean('vat_status')->default(false)->after('discount_status');
            }
            if (! Schema::hasColumn('products', 'it_status')) {
                $table->boolean('it_status')->default(false)->after('vat_status');
            }
            if (! Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 30)->default('simple')->after('it_status');
            }
            if (! Schema::hasColumn('products', 'alternative_prices')) {
                $table->json('alternative_prices')->nullable()->after('product_type');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_print_logs');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('unit_types');
        Schema::dropIfExists('brands');

        Schema::table('products', function (Blueprint $table): void {
            $cols = [
                'brand_id', 'unit_id', 'image', 'description', 'alert_quantity',
                'has_warranty', 'discount_status', 'vat_status', 'it_status',
                'product_type', 'alternative_prices',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
