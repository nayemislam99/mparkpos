<?php

namespace App\Services\DataTable;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class DataTableOrder
{
    public function apply(Builder $query, Request $request, array $definition): void
    {
        $columns = $definition['columns'] ?? [];
        $orderable = $definition['orderable'] ?? [];
        $orders = $request->input('order', []);

        $applied = false;

        foreach (array_slice($orders, 0, 3) as $order) {
            $index = (int) ($order['column'] ?? -1);
            $key = $request->input("columns.{$index}.data");
            $direction = strtolower((string) ($order['dir'] ?? 'asc'));

            if (
                is_string($key)
                && isset($columns[$key])
                && in_array($key, $orderable, true)
                && in_array($direction, ['asc', 'desc'], true)
            ) {
                $query->orderBy($this->column($columns[$key]), $direction);
                $applied = true;
            }
        }

        if ($applied) {
            return;
        }

        [$key, $direction] = $definition['default_order'] ?? [null, 'asc'];

        if (is_string($key) && isset($columns[$key]) && in_array($key, $orderable, true)) {
            $query->orderBy($this->column($columns[$key]), $direction === 'desc' ? 'desc' : 'asc');
        }
    }

    private function column(string $expression): string
    {
        return preg_split('/\s+as\s+/i', $expression)[0];
    }
}
