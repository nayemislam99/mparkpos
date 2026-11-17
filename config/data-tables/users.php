<?php

return [
    'type' => 'table',
    'table' => 'users',
    'columns' => [
        'id' => 'users.id as id',
        'name' => 'users.name as name',
        'email' => 'users.email as email',
        'type' => 'users.type as type',
        'is_active' => 'users.is_active as is_active',
    ],
    'searchable' => ['name', 'email', 'type'],
    'orderable' => ['id', 'name', 'email', 'type', 'is_active'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'users.company_id',
    'soft_delete_column' => 'users.deleted_at',
    'permissions' => [
        'view' => 'users.view',
        'export' => 'users.export',
    ],
    'export_columns' => ['id', 'name', 'email', 'type', 'is_active'],
    'column_titles' => [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'type' => 'Role',
        'is_active' => 'Active',
    ],
    'filters' => [
        'type' => ['type' => 'where', 'column' => 'users.type'],
        'is_active' => ['type' => 'where', 'column' => 'users.is_active'],
        'created_between' => [
            'type' => 'date_between',
            'column' => 'users.created_at',
            'from' => 'from_date',
            'to' => 'to_date',
        ],
    ],
    'formatters' => [
        'type' => [
            'type' => 'badge',
            'values' => [
                'super_admin' => ['label' => 'Super admin', 'class' => 'bg-danger'],
                'store_admin' => ['label' => 'Store admin', 'class' => 'bg-primary'],
                'manager' => ['label' => 'Store manager', 'class' => 'bg-success'],
                'cashier' => ['label' => 'Cashier', 'class' => 'bg-info'],
                'inventory_manager' => ['label' => 'Inventory manager', 'class' => 'bg-warning'],
                'auditor' => ['label' => 'Auditor', 'class' => 'bg-secondary'],
            ],
        ],
        'is_active' => [
            'type' => 'badge',
            'values' => [
                '1' => ['label' => 'Active', 'class' => 'bg-success'],
                '0' => ['label' => 'Inactive', 'class' => 'bg-secondary'],
            ],
        ],
    ],
    'exports' => ['csv', 'xlsx', 'pdf'],
    'max_export_rows' => 10000,
];
