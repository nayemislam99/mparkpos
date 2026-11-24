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
                'branches.view', 'branches.create', 'branches.update', 'branches.delete',
                'roles.view', 'roles.update',
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
                'branches.view',
                'roles.view',
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
        'branches' => [
            'label' => 'Shop Branch',
            'permissions' => [
                'branches.view' => 'View branches',
                'branches.create' => 'Create branches',
                'branches.update' => 'Update branches',
                'branches.delete' => 'Delete branches',
            ],
        ],
        'roles' => [
            'label' => 'Roles & permissions',
            'permissions' => [
                'roles.view' => 'View branch roles and permissions',
                'roles.update' => 'Update branch roles and permissions',
            ],
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
            'label' => 'Customers / CRM',
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
            'heading' => 'Branch & CRM',
            'items' => [
                [
                    'label' => 'Shop Branch',
                    'icon' => 'cil-sitemap',
                    'permission' => 'branches.view',
                    'active_routes' => ['branches.*', 'branch-roles.*', 'crm.*'],
                    'children' => [
                        ['label' => 'Branch', 'permission' => 'branches.view', 'route' => 'branches.index'],
                        ['label' => 'Branch Role Permission', 'permission' => 'roles.view', 'route' => 'branch-roles.index'],
                        ['label' => 'CRM', 'permission' => 'users.view', 'route' => 'crm.index'],
                    ],
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
                        ['label' => 'List', 'permission' => 'users.view', 'route' => 'users.index', 'active_routes' => ['users.index', 'users.edit']],
                        ['label' => 'Create', 'permission' => 'users.create', 'route' => 'users.create'],
                    ],
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
                [
                    'label' => 'Products',
                    'icon' => 'cil-basket',
                    'permission' => 'products.view',
                    'active_routes' => ['products.*', 'brands.*', 'categories.*', 'unit-types.*', 'variations.*', 'barcodes.*'],
                    'children' => [
                        ['label' => 'Add New Product', 'permission' => 'products.create', 'route' => 'products.create'],
                        ['label' => 'All Products', 'permission' => 'products.view', 'route' => 'products.index'],
                        ['label' => 'Brands', 'permission' => 'products.view', 'route' => 'brands.index'],
                        ['label' => 'Categories', 'permission' => 'categories.view', 'route' => 'categories.index'],
                        ['label' => 'Unit Types', 'permission' => 'products.view', 'route' => 'unit-types.index'],
                        ['label' => 'Upload Product By CSV', 'permission' => 'products.create', 'soon' => true],
                        ['label' => 'Print Barcode / Labels', 'permission' => 'products.view', 'route' => 'barcodes.print'],
                        ['label' => 'Barcode Print Report', 'permission' => 'products.view', 'route' => 'barcodes.report'],
                        ['label' => 'Variations', 'permission' => 'products.view', 'route' => 'variations.index'],
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
