<?php

namespace App\Services\DataTable;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DataTableService
{
    public function __construct(
        private DefinitionResolver $resolver,
        private DataTableFilter $filter,
        private DataTableOrder $order,
        private ActionBuilder $actions,
        private DataTableRowFormatter $formatter,
    ) {}

    public function handle(Request $request, array $definition): JsonResponse
    {
        $query = $this->resolver->resolve($definition, $request);
        $recordsTotal = (clone $query)->count();
        $this->filter->apply($query, $request, $definition);
        $recordsFiltered = (clone $query)->count();
        $this->order->apply($query, $request, $definition);

        $start = max(0, (int) $request->input('start', 0));
        $requestedLength = (int) $request->input('length', 25);
        $length = $requestedLength === -1 ? 100 : min(100, max(1, $requestedLength));
        $rows = $query->offset($start)->limit($length)->get();
        $data = $this->formatRows($rows, $definition, $request, $start);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function rowsForExport(Request $request, array $definition): Collection
    {
        $query = $this->resolver->resolve($definition, $request);
        $this->filter->apply($query, $request, $definition);
        $this->order->apply($query, $request, $definition);

        if ($ids = $request->input('ids')) {
            $primaryKey = $definition['primary_key'] ?? 'id';
            $ids = is_array($ids) ? array_slice($ids, 0, 5000) : [];

            if ($ids !== [] && isset($definition['columns'][$primaryKey])) {
                $query->whereIn($this->column($definition['columns'][$primaryKey]), $ids);
            }
        }

        $limit = max(1, (int) ($definition['max_export_rows'] ?? 10000));

        if ((clone $query)->count() > $limit) {
            abort(422, "This export exceeds {$limit} rows. Narrow the search or filters.");
        }

        return $this->formatRows($query->limit($limit)->get(), $definition, $request);
    }

    private function formatRows(
        Collection $rows,
        array $definition,
        Request $request,
        int $offset = 0,
    ): Collection {
        $formatter = $definition['formatter'] ?? null;
        $user = $request->user();

        return $rows->values()->map(function (object $row, int $index) use ($definition, $formatter, $user, $offset): object {
            $row->DT_RowIndex = $offset + $index + 1;
            $row->sn = $offset + $index + 1;

            if (is_callable($formatter)) {
                $row = $formatter($row);
            }

            $row = $this->formatter->format($row, $definition['formatters'] ?? []);

            if (($definition['actions'] ?? []) !== [] && $user) {
                $row->actions = $this->actions->build($row, $definition['actions'], $user);
            }

            return $row;
        });
    }

    private function column(string $expression): string
    {
        return preg_split('/\s+as\s+/i', $expression)[0];
    }
}
