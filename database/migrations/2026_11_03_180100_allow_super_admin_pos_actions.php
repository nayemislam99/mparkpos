<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->ensureUserForeignKey('sales', 'created_by');
        $this->ensureUserForeignKey('sale_payments', 'received_by');
        $this->ensureUserForeignKey('stock_movements', 'created_by');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->ensureCompanyUserForeignKey('sales', 'created_by');
        $this->ensureCompanyUserForeignKey('sale_payments', 'received_by');
        $this->ensureCompanyUserForeignKey('stock_movements', 'created_by');
    }

    private function ensureUserForeignKey(string $tableName, string $columnName): void
    {
        $hasUserForeignKey = false;
        foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
            if ($foreignKey['columns'] === ['company_id', $columnName]) {
                Schema::table($tableName, function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            } elseif ($foreignKey['columns'] === [$columnName]
                && $foreignKey['foreign_table'] === 'users'
                && $foreignKey['foreign_columns'] === ['id']) {
                $hasUserForeignKey = true;
            }
        }

        if (! $hasUserForeignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columnName): void {
                $table->foreign($columnName, $tableName.'_'.$columnName.'_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }

    private function ensureCompanyUserForeignKey(string $tableName, string $columnName): void
    {
        $foreignKeys = Schema::getForeignKeys($tableName);
        $hasCompanyUserForeignKey = false;
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey['columns'] === [$columnName]) {
                Schema::table($tableName, function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            } elseif ($foreignKey['columns'] === ['company_id', $columnName]) {
                $hasCompanyUserForeignKey = true;
            }
        }

        if (! $hasCompanyUserForeignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columnName): void {
                $table->foreign(['company_id', $columnName], $tableName.'_company_id_'.$columnName.'_foreign')
                    ->references(['company_id', 'id'])
                    ->on('users')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }
};
