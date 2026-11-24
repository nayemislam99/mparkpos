<?php

use Illuminate\Support\Facades\DB;

return [
    'type' => 'table',
    'table' => 'unit_types',
    'columns' => [
        'id' => 'unit_types.id as id',
        'name' => 'unit_types.name as name',
        'code' => 'unit_types.code as code',
        'is_active' => 'unit_types.is_active as is_active',
        'created_at' => 'unit_types.created_at as created_at',
    ],
    'searchable' => ['name', 'code'],
    'orderable' => ['id', 'name', 'code', 'is_active', 'created_at'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'unit_types.company_id',
    'soft_delete_column' => 'unit_types.deleted_at',
    'permissions' => [
        'view' => 'products.view',
        'export' => 'products.view',
    ],
    'column_titles' => [
        'id' => '#',
        'name' => 'Unit Type',
        'code' => 'Code',
        'is_active' => 'Status',
        'created_at' => 'Added on',
    ],
    'filters' => [
        'is_active' => ['type' => 'where', 'column' => 'unit_types.is_active'],
    ],
    'exports' => [], // Hidden per client requirement
    'formatter' => function ($row) {
        $row->unit_display = '<span class="fw-bold text-dark fs-6">' . e($row->name) . '</span>';
        $row->code_display = '<span class="badge bg-light text-dark border px-2 py-1">' . e($row->code ?? '-') . '</span>';

        $activeLabel = __('messages.active');
        $inactiveLabel = __('messages.inactive');
        if ($row->is_active) {
            $row->status_display = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">' . $activeLabel . '</span>';
        } else {
            $row->status_display = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">' . $inactiveLabel . '</span>';
        }

        $row->date_display = $row->created_at ? date('d-m-Y', strtotime($row->created_at)) : '-';

        $editLabel = __('messages.edit');
        $toggleLabel = $row->is_active ? __('messages.deactivate') : __('messages.activate');
        $toggleClass = $row->is_active ? 'btn-danger' : 'btn-success';
        $csrf = csrf_field();
        $toggleUrl = route('unit-types.toggle', $row->id);
        $confirmText = addslashes(__('messages.confirm_toggle_status', ['name' => $row->name]));

        $row->actions = '<div class="d-inline-flex gap-1">' .
            '<button type="button" class="btn btn-sm btn-teal text-white edit-unit-btn" ' .
            'data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-code="' . e($row->code) . '" data-active="' . ($row->is_active ? 1 : 0) . '" ' .
            'style="border-radius: 6px;">' . $editLabel . '</button>' .
            '<form action="' . $toggleUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'' . $confirmText . '\');">' .
            $csrf .
            '<button type="submit" class="btn btn-sm ' . $toggleClass . ' text-white" style="border-radius: 6px;">' . $toggleLabel . '</button>' .
            '</form>' .
            '</div>';

        return $row;
    },
];
