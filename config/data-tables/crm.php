<?php

return [
    'type' => 'table',
    'table' => 'customers',
    'columns' => [
        'id' => 'customers.id as id',
        'name' => 'customers.name as name',
        'phone' => 'customers.phone as phone',
        'email' => 'customers.email as email',
        'advance_balance' => 'customers.advance_balance as advance_balance',
        'opening_balance' => 'customers.opening_balance as opening_balance',
        'due_limit' => 'customers.due_limit as due_limit',
        'is_active' => 'customers.is_active as is_active',
    ],
    'searchable' => ['name', 'phone', 'email'],
    'orderable' => ['id', 'name', 'phone', 'advance_balance', 'is_active'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'customers.company_id',
    'soft_delete_column' => 'customers.deleted_at',
    'permissions' => [
        'view' => 'customers.view',
        'export' => 'customers.view',
    ],
    'export_columns' => ['id', 'name', 'phone', 'email', 'advance_balance', 'opening_balance', 'is_active'],
    'column_titles' => [
        'id' => 'ID',
        'name' => 'Customer Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'advance_balance' => 'Advance (৳)',
        'opening_balance' => 'Opening Due (৳)',
        'is_active' => 'Status',
    ],
    'filters' => [
        'is_active' => ['type' => 'where', 'column' => 'customers.is_active'],
        'branch_id' => ['type' => 'where', 'column' => 'customers.branch_id'],
        'created_between' => [
            'type' => 'date_between',
            'column' => 'customers.created_at',
            'from' => 'from_date',
            'to' => 'to_date',
        ],
    ],
    'formatters' => [
        'is_active' => [
            'type' => 'badge',
            'values' => [
                '1' => ['label' => 'Active', 'class' => 'bg-success'],
                '0' => ['label' => 'Inactive', 'class' => 'bg-danger'],
            ],
        ],
    ],
    'exports' => [],
    'max_export_rows' => 5000,
];
