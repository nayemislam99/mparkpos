<?php

use Illuminate\Support\Facades\DB;

return [
    'type' => 'table',
    'table' => 'customers',
    'columns' => [
        'id' => 'customers.id as id',
        'name' => 'customers.name as name',
        'phone' => 'customers.phone as phone',
        'email' => 'customers.email as email',
        'nid' => 'customers.nid as nid',
        'city' => 'customers.city as city',
        'customer_group' => 'customers.customer_group as customer_group',
        'customer_type' => 'customers.customer_type as customer_type',
        'address' => 'customers.address as address',
        'opening_balance' => 'customers.opening_balance as opening_balance',
        'advance_balance' => 'customers.advance_balance as advance_balance',
        'due_limit' => 'customers.due_limit as due_limit',
        'branch_id' => 'customers.branch_id as branch_id',
        'is_active' => 'customers.is_active as is_active',
        'created_at' => 'customers.created_at as created_at',
    ],
    'searchable' => ['name', 'phone', 'email', 'nid', 'city', 'customer_type', 'customer_group'],
    'orderable' => ['id', 'name', 'phone', 'customer_type', 'opening_balance', 'advance_balance', 'is_active', 'created_at'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'customers.company_id',
    'soft_delete_column' => 'customers.deleted_at',
    'permissions' => [
        'view' => 'customers.view',
        'export' => 'customers.view',
    ],
    'filters' => [
        'is_active' => ['type' => 'where', 'column' => 'customers.is_active'],
        'branch_id' => ['type' => 'where', 'column' => 'customers.branch_id'],
        'customer_type' => ['type' => 'where', 'column' => 'customers.customer_type'],
        'created_between' => [
            'type' => 'date_between',
            'column' => 'customers.created_at',
            'from' => 'from_date',
            'to' => 'to_date',
        ],
    ],
    'export_columns' => ['id', 'name', 'phone', 'email', 'customer_type', 'nid', 'city', 'opening_balance', 'advance_balance', 'due_limit', 'is_active'],
    'exports' => ['csv', 'xlsx', 'pdf'],
    'max_export_rows' => 10000,
    'formatter' => function ($row) {
        $typeVal = $row->customer_type ?: 'regular';
        $typeLabel = ucfirst(str_replace('_', ' ', $typeVal));
        $typeColors = [
            'regular' => 'bg-secondary text-white',
            'retail' => 'bg-primary text-white',
            'wholesale' => 'bg-info text-dark',
            'dealer' => 'bg-warning text-dark',
            'corporate' => 'bg-dark text-white border border-secondary',
            'walk_in' => 'bg-secondary text-white',
            'vip' => 'bg-danger text-white',
        ];
        $typeClass = $typeColors[$typeVal] ?? 'bg-secondary text-white';
        $typeBadge = '<span class="badge ' . $typeClass . ' ms-1 px-2 py-0.5" style="font-size: 0.68rem; font-weight: 600;">' . e($typeLabel) . '</span>';

        $phoneHtml = ! empty($row->phone) ? '<div class="text-body-secondary small mt-0.5"><i class="bi bi-telephone-fill text-primary me-1"></i>' . e($row->phone) . '</div>' : '';
        $emailHtml = ! empty($row->email) ? '<div class="text-body-secondary small"><i class="bi bi-envelope me-1"></i>' . e($row->email) . '</div>' : '';
        $row->customer_display = '<div class="d-flex align-items-center gap-1 flex-wrap"><span class="fw-bold text-body fs-6">' . e($row->name) . '</span>' . $typeBadge . '</div>' . $phoneHtml . $emailHtml;

        $nidHtml = ! empty($row->nid) ? '<span class="badge bg-secondary bg-opacity-25 text-body border border-secondary border-opacity-25 px-2 py-1 me-1"><i class="bi bi-card-text me-1"></i>' . e($row->nid) . '</span>' : '';
        $cityHtml = ! empty($row->city) ? '<span class="text-body-secondary small"><i class="bi bi-geo-alt text-danger me-1"></i>' . e($row->city) . '</span>' : '';
        $row->nid_city_display = ($nidHtml || $cityHtml) ? ($nidHtml . $cityHtml) : '<span class="text-body-secondary">—</span>';

        // Outstanding due calculation: opening_balance + unpaid sales due
        $salesDue = (float) DB::table('sales')
            ->where('customer_id', $row->id)
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'completed')
            ->sum('due_total');
        $totalDue = (float) $row->opening_balance + $salesDue;
        $row->raw_due = $totalDue;

        if ($totalDue > 0) {
            $row->due_display = '<span class="badge bg-danger text-white fs-6 px-2.5 py-1">৳ ' . number_format($totalDue, 2) . '</span>';
        } else {
            $row->due_display = '<span class="text-body-secondary fw-semibold">৳ 0.00</span>';
        }

        $advance = (float) $row->advance_balance;
        if ($advance > 0) {
            $row->advance_display = '<span class="badge bg-success text-white fs-6 px-2.5 py-1">৳ ' . number_format($advance, 2) . '</span>';
        } else {
            $row->advance_display = '<span class="text-body-secondary fw-semibold">৳ 0.00</span>';
        }

        $dueLimit = (float) $row->due_limit;
        $row->due_limit_display = $dueLimit > 0 ? '<span class="text-body fw-semibold">৳ ' . number_format($dueLimit, 2) . '</span>' : '<span class="text-body-secondary">—</span>';

        if (! empty($row->branch_id)) {
            $branchName = DB::table('branches')->where('id', $row->branch_id)->value('name');
            $row->branch_display = '<span class="text-body small fw-semibold"><i class="bi bi-shop text-primary me-1"></i>' . e($branchName ?: 'Branch #' . $row->branch_id) . '</span>';
        } else {
            $row->branch_display = '<span class="text-body-secondary small"><i class="bi bi-buildings me-1"></i>' . __('messages.all_branches_default') . '</span>';
        }

        $activeLabel = __('messages.active');
        $inactiveLabel = __('messages.inactive');
        if ($row->is_active) {
            $row->status_display = '<span class="badge bg-success text-white px-2.5 py-1 rounded-pill">' . $activeLabel . '</span>';
        } else {
            $row->status_display = '<span class="badge bg-danger text-white px-2.5 py-1 rounded-pill">' . $inactiveLabel . '</span>';
        }

        $editUrl = route('customers.edit', $row->id);
        $toggleUrl = route('customers.toggle', $row->id);
        $deleteUrl = route('customers.destroy', $row->id);

        $actions = '<div class="d-inline-flex align-items-center gap-1 text-nowrap" style="white-space: nowrap;">';

        // Collect Due button (opens modal with customer data)
        $collectDueLabel = __('messages.collect_due');
        $actions .= '<button type="button" class="btn btn-sm btn-success text-white px-2 py-1 text-nowrap js-collect-due-btn" ' .
            'data-id="' . $row->id . '" ' .
            'data-name="' . e($row->name) . '" ' .
            'data-phone="' . e($row->phone) . '" ' .
            'data-due="' . $totalDue . '" ' .
            'style="border-radius: 6px;" title="' . $collectDueLabel . '">' .
            '<i class="bi bi-cash-stack me-1"></i>' . $collectDueLabel . '</button>';

        // Edit button
        $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary px-2 py-1 text-nowrap" style="border-radius: 6px;" title="' . __('messages.edit') . '"><i class="bi bi-pencil-square me-1"></i>' . __('messages.edit') . '</a>';

        // Toggle Status
        $toggleBtnClass = $row->is_active ? 'btn-outline-warning' : 'btn-outline-success';
        $toggleLabel = $row->is_active ? __('messages.deactivate') : __('messages.activate');
        $toggleIcon = $row->is_active ? '<i class="bi bi-person-slash"></i>' : '<i class="bi bi-person-check"></i>';
        $confirmToggle = addslashes(__('messages.confirm_toggle_customer_status', ['name' => $row->name]));
        $actions .= '<form action="' . $toggleUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'' . $confirmToggle . '\');">';
        $actions .= csrf_field();
        $actions .= '<button type="submit" class="btn btn-sm ' . $toggleBtnClass . ' px-2 py-1 text-nowrap" style="border-radius: 6px;" title="' . $toggleLabel . '">' . $toggleIcon . '</button>';
        $actions .= '</form>';

        // Delete button
        $confirmDelete = addslashes(__('messages.confirm_delete_customer', ['name' => $row->name]));
        $actions .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'' . $confirmDelete . '\');">';
        $actions .= csrf_field();
        $actions .= method_field('DELETE');
        $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 text-nowrap" style="border-radius: 6px;" title="' . __('messages.delete') . '"><i class="bi bi-trash"></i></button>';
        $actions .= '</form>';

        $actions .= '</div>';
        $row->actions = $actions;

        return $row;
    },
];
