<?php

namespace App\Services\DataTable;

use Illuminate\Http\Request;

class DataTableRequestValidator
{
    public function validate(Request $request): void
    {
        $request->validate([
            'draw' => ['sometimes', 'integer', 'min:0'],
            'start' => ['sometimes', 'integer', 'min:0'],
            'length' => ['sometimes', 'integer', 'min:-1', 'max:100'],
            'search.value' => ['sometimes', 'nullable', 'string', 'max:200'],
            'order' => ['sometimes', 'array', 'max:3'],
            'order.*.column' => ['required_with:order', 'integer', 'min:0', 'max:100'],
            'order.*.dir' => ['required_with:order', 'in:asc,desc'],
            'columns' => ['sometimes', 'array', 'max:100'],
            'columns.*.data' => ['sometimes', 'nullable', 'string', 'max:100'],
            'columns.*.search.value' => ['sometimes', 'nullable', 'string', 'max:200'],
            'filters' => ['sometimes', 'array', 'max:30'],
            'ids' => ['sometimes', 'array', 'max:5000'],
            'ids.*' => ['integer', 'min:1'],
            'filters.*.*' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
