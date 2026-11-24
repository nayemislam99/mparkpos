<?php

use Illuminate\Support\Facades\DB;

return [
    'type' => 'table',
    'table' => 'brands',
    'columns' => [
        'id' => 'brands.id as id',
        'name' => 'brands.name as name',
        'is_active' => 'brands.is_active as is_active',
        'created_at' => 'brands.created_at as created_at',
    ],
    'searchable' => ['name'],
    'orderable' => ['id', 'name', 'is_active', 'created_at'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'brands.company_id',
    'soft_delete_column' => 'brands.deleted_at',
    'permissions' => [
        'view' => 'products.view',
        'export' => 'products.view',
    ],
    'column_titles' => [
        'id' => '#',
        'name' => 'Brand',
        'is_active' => 'Status',
        'created_at' => 'Added on',
    ],
    'filters' => [
        'is_active' => ['type' => 'where', 'column' => 'brands.is_active'],
    ],
    'exports' => [], // Hidden per client requirement
    'formatter' => function ($row) {
        $productsCount = DB::table('products')
            ->where('brand_id', $row->id)
            ->whereNull('deleted_at')
            ->count();

        $row->brand_display = '<span class="fw-bold text-dark fs-6">' . e($row->name) . '</span>';
        $row->products_display = '<span class="badge bg-light text-dark border px-2 py-1">' . $productsCount . '</span>';

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
        $toggleUrl = route('brands.toggle', $row->id);
        $confirmText = addslashes(__('messages.confirm_toggle_status', ['name' => $row->name]));

        $row->actions = '<div class="d-inline-flex gap-1">' .
            '<button type="button" class="btn btn-sm btn-teal text-white edit-brand-btn" ' .
            'data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-active="' . ($row->is_active ? 1 : 0) . '" ' .
            'style="border-radius: 6px;">' . $editLabel . '</button>' .
            '<form action="' . $toggleUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'' . $confirmText . '\');">' .
            $csrf .
            '<button type="submit" class="btn btn-sm ' . $toggleClass . ' text-white" style="border-radius: 6px;">' . $toggleLabel . '</button>' .
            '</form>' .
            '</div>';

        return $row;
    },
];
