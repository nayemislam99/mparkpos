<?php

use Illuminate\Support\Facades\DB;

return [
    'type' => 'table',
    'table' => 'users',
    'columns' => [
        'id' => 'users.id as id',
        'name' => 'users.name as name',
        'email' => 'users.email as email',
        'phone' => 'users.phone as phone',
        'type' => 'users.type as type',
        'branch_id' => 'users.branch_id as branch_id',
        'is_active' => 'users.is_active as is_active',
        'created_at' => 'users.created_at as created_at',
    ],
    'searchable' => ['name', 'email', 'phone', 'type'],
    'orderable' => ['id', 'name', 'email', 'type', 'is_active', 'created_at'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'users.company_id',
    'soft_delete_column' => 'users.deleted_at',
    'permissions' => [
        'view' => 'users.view',
        'export' => 'users.view',
    ],
    'column_titles' => [
        'id' => '#',
        'name' => 'Name',
        'email' => 'Email',
        'type' => 'Role',
        'branch_id' => 'Branch',
        'is_active' => 'Status',
    ],
    'filters' => [
        'type' => ['type' => 'where', 'column' => 'users.type'],
        'is_active' => ['type' => 'where', 'column' => 'users.is_active'],
        'branch_id' => ['type' => 'where', 'column' => 'users.branch_id'],
        'created_between' => [
            'type' => 'date_between',
            'column' => 'users.created_at',
            'from' => 'from_date',
            'to' => 'to_date',
        ],
    ],
    'export_columns' => ['id', 'name', 'email', 'type', 'is_active'],
    'exports' => ['csv', 'xlsx', 'pdf'],
    'max_export_rows' => 10000,
    'formatter' => function ($row) {
        $phoneHtml = ! empty($row->phone)
            ? '<div class="text-body-secondary small"><i class="bi bi-telephone me-1"></i>' . e($row->phone) . '</div>'
            : '';
        $row->name_display = '<div class="fw-bold text-dark">' . e($row->name) . '</div>' . $phoneHtml;

        $roleColors = [
            'super_admin' => 'bg-danger text-white',
            'store_admin' => 'bg-danger-subtle text-danger border border-danger-subtle',
            'manager' => 'bg-primary-subtle text-primary border border-primary-subtle',
            'cashier' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'inventory_manager' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'auditor' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        ];
        $roleClass = $roleColors[$row->type] ?? 'bg-light text-dark border';
        $roleLabel = config("access.roles.{$row->type}.label", ucfirst(str_replace('_', ' ', $row->type)));
        $row->role_display = '<span class="badge ' . $roleClass . ' px-2 py-1 rounded-pill">' . e($roleLabel) . '</span>';

        if (! empty($row->branch_id)) {
            $branchName = DB::table('branches')->where('id', $row->branch_id)->value('name');
            $row->branch_display = '<span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-shop me-1"></i>' . e($branchName ?: 'Branch #' . $row->branch_id) . '</span>';
        } else {
            $row->branch_display = '<span class="badge bg-light text-muted border px-2 py-1">' . __('messages.all_branches_default') . '</span>';
        }

        $activeLabel = __('messages.active');
        $inactiveLabel = __('messages.inactive');
        if ($row->is_active) {
            $row->status_display = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">' . $activeLabel . '</span>';
        } else {
            $row->status_display = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">' . $inactiveLabel . '</span>';
        }

        $currentUserId = auth()->id();
        $isSelf = (int) $row->id === (int) $currentUserId;
        $isSuper = $row->type === 'super_admin';

        $editUrl = route('users.edit', $row->id);
        $toggleUrl = route('users.toggle', $row->id);
        $deleteUrl = route('users.destroy', $row->id);

        $actions = '<div class="d-inline-flex gap-1">';
        $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-teal text-white px-2 py-1" style="border-radius: 6px;" title="' . __('messages.edit') . '"><i class="bi bi-pencil-square me-1"></i>' . __('messages.edit') . '</a>';

        if (! $isSelf && ! $isSuper) {
            $toggleClass = $row->is_active ? 'btn-danger' : 'btn-success';
            $toggleLabel = $row->is_active ? __('messages.deactivate') : __('messages.activate');
            $confirmToggle = addslashes(__('messages.confirm_toggle_user_status', ['name' => $row->name]));
            $actions .= '<form action="' . $toggleUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'' . $confirmToggle . '\');">';
            $actions .= csrf_field();
            $actions .= '<button type="submit" class="btn btn-sm ' . $toggleClass . ' text-white px-2 py-1" style="border-radius: 6px;">' . $toggleLabel . '</button>';
            $actions .= '</form>';

            $confirmDelete = addslashes(__('messages.confirm_delete_user', ['name' => $row->name]));
            $actions .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'' . $confirmDelete . '\');">';
            $actions .= csrf_field();
            $actions .= method_field('DELETE');
            $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" style="border-radius: 6px;" title="' . __('messages.delete') . '"><i class="bi bi-trash"></i></button>';
            $actions .= '</form>';
        }

        $actions .= '</div>';
        $row->actions = $actions;

        return $row;
    },
];
