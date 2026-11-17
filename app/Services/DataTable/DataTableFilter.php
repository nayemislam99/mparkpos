<?php

namespace App\Services\DataTable;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DataTableFilter
{
    public function apply(Builder $query, Request $request, array $definition): void
    {
        $this->applyGlobalSearch($query, $request, $definition);
        $this->applyColumnSearch($query, $request, $definition);
        $this->applyConfiguredFilters($query, $request, $definition);
    }

    private function applyGlobalSearch(Builder $query, Request $request, array $definition): void
    {
        $term = mb_substr(trim((string) $request->input('search.value', '')), 0, 200);

        if ($term === '') {
            return;
        }

        $searchable = $definition['searchable'] ?? [];
        $columns = $definition['columns'] ?? [];

        if ($searchable === []) {
            return;
        }

        $query->where(function (Builder $query) use ($term, $searchable, $columns): void {
            foreach ($searchable as $key) {
                if (isset($columns[$key])) {
                    $query->orWhere($this->column($columns[$key]), 'like', "%{$term}%");
                }
            }
        });
    }

    private function applyColumnSearch(Builder $query, Request $request, array $definition): void
    {
        $columns = $definition['columns'] ?? [];
        $searchable = $definition['searchable'] ?? [];

        foreach ($request->input('columns', []) as $column) {
            $key = $column['data'] ?? null;
            $term = mb_substr(trim((string) data_get($column, 'search.value', '')), 0, 200);

            if ($term !== '' && is_string($key) && in_array($key, $searchable, true) && isset($columns[$key])) {
                $query->where($this->column($columns[$key]), 'like', "%{$term}%");
            }
        }
    }

    private function applyConfiguredFilters(Builder $query, Request $request, array $definition): void
    {
        $filters = $request->input('filters', []);

        if (! is_array($filters)) {
            throw ValidationException::withMessages([
                'filters' => 'Filters must be sent as a key/value object.',
            ]);
        }

        foreach ($definition['filters'] ?? [] as $key => $rule) {
            $column = $rule['column'] ?? null;

            if (! is_string($column)) {
                continue;
            }

            if (($rule['type'] ?? 'where') === 'date_between') {
                $this->applyDateRange(
                    $query,
                    $column,
                    $filters[$rule['from'] ?? 'from_date'] ?? null,
                    $filters[$rule['to'] ?? 'to_date'] ?? null,
                    $key,
                );

                continue;
            }

            if (! array_key_exists($key, $filters)) {
                continue;
            }

            $value = $filters[$key];

            switch ($rule['type'] ?? 'where') {
                case 'where':
                    if ($value !== '' && $value !== null) {
                        $this->assertScalar($value, $key);
                        $query->where($column, $value);
                    }
                    break;
                case 'like':
                    if ($value !== '' && $value !== null) {
                        $this->assertScalar($value, $key);
                        $query->where($column, 'like', '%'.mb_substr((string) $value, 0, 200).'%');
                    }
                    break;
                case 'in':
                    if (is_array($value) && $value !== []) {
                        $values = array_slice($value, 0, 100);
                        foreach ($values as $item) {
                            $this->assertScalar($item, $key);
                        }
                        $query->whereIn($column, $values);
                    } elseif ($value !== '' && $value !== null) {
                        throw ValidationException::withMessages([
                            "filters.{$key}" => 'This filter must be a list of values.',
                        ]);
                    }
                    break;
            }
        }
    }

    private function assertScalar(mixed $value, string $key): void
    {
        if (! is_scalar($value)) {
            throw ValidationException::withMessages([
                "filters.{$key}" => 'This filter must contain a single value.',
            ]);
        }
    }

    private function applyDateRange(Builder $query, string $column, mixed $from, mixed $to, string $key): void
    {
        foreach (['from' => $from, 'to' => $to] as $side => $date) {
            if ($date === null || $date === '') {
                continue;
            }

            if (! is_string($date)) {
                throw ValidationException::withMessages([
                    $key => 'Date filters must use the YYYY-MM-DD format.',
                ]);
            }

            $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

            if (! $parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                throw ValidationException::withMessages([
                    $key => 'Date filters must use the YYYY-MM-DD format.',
                ]);
            }

            $query->whereDate($column, $side === 'from' ? '>=' : '<=', $date);
        }
    }

    private function column(string $expression): string
    {
        return preg_split('/\s+as\s+/i', $expression)[0];
    }
}
