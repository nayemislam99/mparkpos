<?php

namespace App\Services\DataTable;

class DataTableRegistry
{
    public function get(string $key): ?array
    {
        $definition = config("data-tables.{$key}");

        return is_array($definition) ? $definition : null;
    }

    public function keys(): array
    {
        return array_keys(config('data-tables', []));
    }
}
