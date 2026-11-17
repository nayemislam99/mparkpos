<?php

namespace App\Services\DataTable;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DefinitionResolver
{
    public function resolve(array $definition, Request $request): Builder
    {
        if (in_array($definition['type'] ?? 'table', ['query', 'template', 'custom'], true)) {
            $queryClass = $definition['query_class'] ?? null;

            if (! is_string($queryClass) || ! is_subclass_of($queryClass, DataTableQuery::class)) {
                throw new InvalidArgumentException('A custom DataTable query must implement DataTableQuery.');
            }

            $query = app($queryClass)->build($request);

            if ($softDeleteColumn = ($definition['soft_delete_column'] ?? null)) {
                $query->whereNull($softDeleteColumn);
            }

            if ($scopeColumn = ($definition['company_scope_column'] ?? null)) {
                $query->where($scopeColumn, $request->user()->company_id);
            }

            $columns = $definition['columns'] ?? [];

            return $columns === [] ? $query : $query->select(array_values($columns));
        }

        if (($definition['type'] ?? 'table') !== 'table') {
            throw new InvalidArgumentException('Unsupported DataTable definition type.');
        }

        $table = $definition['table'] ?? null;
        $columns = $definition['columns'] ?? [];

        if (! is_string($table) || $table === '' || $columns === []) {
            throw new InvalidArgumentException('A DataTable definition needs a table and columns.');
        }

        $query = DB::table($table);

        foreach ($definition['joins'] ?? [] as $join) {
            $method = $join['type'] ?? 'left';
            $callback = static function ($joinQuery) use ($join): void {
                $joinQuery->on($join['first'], $join['operator'] ?? '=', $join['second']);
            };

            match ($method) {
                'inner' => $query->join($join['table'], $callback),
                'left' => $query->leftJoin($join['table'], $callback),
                default => throw new InvalidArgumentException('Unsupported DataTable join type.'),
            };
        }

        if ($softDeleteColumn = ($definition['soft_delete_column'] ?? null)) {
            $query->whereNull($softDeleteColumn);
        }

        if ($scopeColumn = ($definition['company_scope_column'] ?? null)) {
            $query->where($scopeColumn, $request->user()->company_id);
        }

        return $query->select(array_values($columns));
    }
}
