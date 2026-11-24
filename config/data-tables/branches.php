<?php

return [
    'type' => 'table',
    'table' => 'branches',
    'columns' => [
        'id' => 'branches.id as id',
        'name' => 'branches.name as name',
        'code' => 'branches.code as code',
        'phone' => 'branches.phone as phone',
        'email' => 'branches.email as email',
        'address' => 'branches.address as address',
        'is_head_office' => 'branches.is_head_office as is_head_office',
        'is_active' => 'branches.is_active as is_active',
    ],
    'searchable' => ['name', 'code', 'phone', 'address'],
    'orderable' => ['id', 'name', 'code', 'is_head_office', 'is_active'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'branches.company_id',
    'soft_delete_column' => 'branches.deleted_at',
    'permissions' => [
        'view' => 'branches.view',
        'export' => 'branches.view',
    ],
    'column_titles' => [
        'id' => 'SI',
        'name' => 'Branch',
        'address' => 'Address',
        'is_active' => 'Status',
    ],
    'filters' => [
        'is_active' => ['type' => 'where', 'column' => 'branches.is_active'],
        'is_head_office' => ['type' => 'where', 'column' => 'branches.is_head_office'],
    ],
    'formatters' => [
        'is_active' => [
            'type' => 'badge',
            'values' => [
                '1' => ['label' => 'Active', 'class' => 'bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill'],
                '0' => ['label' => 'Deactive', 'class' => 'bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill'],
            ],
        ],
    ],
    'formatter' => function ($row) {
        $name = e($row->name ?? '');
        $phone = ! empty($row->phone) ? '<div class="text-body-secondary small">'.e($row->phone).'</div>' : '';
        $row->branch_display = '<div class="fw-bold text-dark">'.$name.'</div>'.$phone;
        $row->address_display = ! empty($row->address) ? '<span class="text-body-secondary">'.e($row->address).'</span>' : '<span class="text-muted">-</span>';
        $row->actions = '<button type="button" class="btn btn-sm btn-teal px-3" onclick="openEditBranchModal('.$row->id.')" style="border-radius: 6px;">Edit</button>';

        return $row;
    },
];
