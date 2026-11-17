<?php

return [
    'roles' => [
        'super_admin' => [
            'label' => 'Super admin',
            'permissions' => ['*'],
        ],
        'store_admin' => [
            'label' => 'Store admin',
            'permissions' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.update', 'users.delete', 'users.export',
                'customers.view', 'customers.create', 'customers.update', 'customers.delete',
                'products.view', 'products.create', 'products.update', 'products.delete', 'products.export',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'sales.view', 'sales.create', 'sales.refund', 'sales.discount', 'sales.collect_payment',
                'inventory.view', 'inventory.adjust',
                'reports.view', 'reports.export',
            ],
        ],
        'manager' => [
            'label' => 'Store manager',
            'permissions' => [
                'dashboard.view',
                'users.view',
                'customers.view', 'customers.create',
                'products.view', 'products.create', 'products.update', 'products.export',
                'categories.view', 'categories.create', 'categories.update',
                'sales.view', 'sales.create', 'sales.refund', 'sales.discount', 'sales.collect_payment',
                'inventory.view', 'inventory.adjust',
                'reports.view', 'reports.export',
            ],
        ],
        'cashier' => [
            'label' => 'Cashier',
            'permissions' => [
                'dashboard.view',
                'customers.view', 'customers.create',
                'products.view', 'categories.view',
                'sales.view', 'sales.create', 'sales.discount', 'sales.collect_payment',
            ],
        ],
        'inventory_manager' => [
            'label' => 'Inventory manager',
            'permissions' => [
                'dashboard.view',
                'products.view', 'products.create', 'products.update', 'products.export',
                'categories.view', 'categories.create', 'categories.update',
                'inventory.view', 'inventory.adjust',
                'reports.view', 'reports.export',
            ],
        ],
        'auditor' => [
            'label' => 'Auditor',
            'permissions' => [
                'dashboard.view',
                'users.view', 'users.export',
                'products.view', 'categories.view',
                'sales.view',
                'inventory.view',
                'reports.view', 'reports.export',
            ],
        ],
    ],

    'permission_groups' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => ['dashboard.view' => 'View dashboard'],
        ],
        'users' => [
            'label' => 'Users',
            'permissions' => [
                'users.view' => 'View users',
                'users.create' => 'Create users',
                'users.update' => 'Update users',
                'users.delete' => 'Delete users',
                'users.export' => 'Export users',
            ],
        ],
        'customers' => [
            'label' => 'Customers',
            'permissions' => [
                'customers.view' => 'View customers',
                'customers.create' => 'Create customers',
                'customers.update' => 'Update customers',
                'customers.delete' => 'Delete customers',
            ],
        ],
        'products' => [
            'label' => 'Products',
            'permissions' => [
                'products.view' => 'View products',
                'products.create' => 'Create products',
                'products.update' => 'Update products',
                'products.delete' => 'Delete products',
                'products.export' => 'Export products',
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'permissions' => [
                'categories.view' => 'View categories',
                'categories.create' => 'Create categories',
                'categories.update' => 'Update categories',
                'categories.delete' => 'Delete categories',
            ],
        ],
        'sales' => [
            'label' => 'Sales',
            'permissions' => [
                'sales.view' => 'View sales',
                'sales.create' => 'Create sales',
                'sales.collect_payment' => 'Collect invoice payment',
                'sales.refund' => 'Refund sales',
                'sales.discount' => 'Apply discounts',
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'permissions' => [
                'inventory.view' => 'View inventory',
                'inventory.adjust' => 'Adjust stock',
            ],
        ],
        'reports' => [
            'label' => 'Reports',
            'permissions' => [
                'reports.view' => 'View reports',
                'reports.export' => 'Export reports',
            ],
        ],
    ],

    'menu' => [
        [
            'heading' => 'Overview',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'cil-speedometer',
                    'permission' => 'dashboard.view',
                    'route' => 'dashboard.index',
                ],
            ],
        ],
        [
            'heading' => 'Store management',
            'items' => [
                [
                    'label' => 'Users',
                    'icon' => 'cil-people',
                    'permission' => 'users.view',
                    'active_routes' => ['users.*'],
                    'children' => [
                        ['label' => 'List', 'permission' => 'users.view', 'route' => 'users.index'],
                        ['label' => 'Create', 'permission' => 'users.create', 'soon' => true],
                    ],
                    [
                        'label' => 'Customers',
                        'icon' => 'cil-user',
                        'permission' => 'customers.view',
                        'active_routes' => ['pos.*'],
                        'children' => [
                            ['label' => 'Customer search', 'permission' => 'customers.view', 'soon' => true],
                            ['label' => 'Add customer', 'permission' => 'customers.create', 'soon' => true],
                        ],
                    ],
                ],
                [
                    'label' => 'Products',
                    'icon' => 'cil-basket',
                    'permission' => 'products.view',
                    'active_routes' => ['products.*'],
                    'children' => [
                        ['label' => 'List', 'permission' => 'products.view', 'soon' => true],
                        ['label' => 'Create', 'permission' => 'products.create', 'soon' => true],
                        ['label' => 'Categories', 'permission' => 'categories.view', 'soon' => true],
                    ],
                ],
                [
                    'label' => 'Sales',
                    'icon' => 'cil-cart',
                    'permission' => 'sales.view',
                    'active_routes' => ['sales.*'],
                    'children' => [
                        ['label' => 'List', 'permission' => 'sales.view', 'soon' => true],
                        ['label' => 'POS terminal', 'permission' => 'sales.create', 'route' => 'pos.terminal'],
                    ],
                ],
                [
                    'label' => 'Inventory',
                    'icon' => 'cil-storage',
                    'permission' => 'inventory.view',
                    'active_routes' => ['inventory.*'],
                    'children' => [
                        ['label' => 'Stock list', 'permission' => 'inventory.view', 'soon' => true],
                        ['label' => 'Stock adjustment', 'permission' => 'inventory.adjust', 'soon' => true],
                    ],
                ],
            ],
        ],
        [
            'heading' => 'Insights',
            'items' => [
                [
                    'label' => 'Reports',
                    'icon' => 'cil-chart',
                    'permission' => 'reports.view',
                    'active_routes' => ['reports.*'],
                    'children' => [
                        ['label' => 'Sales reports', 'permission' => 'reports.view', 'soon' => true],
                        ['label' => 'Inventory reports', 'permission' => 'reports.view', 'soon' => true],
                    ],
                ],
            ],
        ],
    ],
];
