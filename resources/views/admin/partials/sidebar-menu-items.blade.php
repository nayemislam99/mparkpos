@foreach ($items as $item)
    @if (is_array($item) && isset($item['permission']))
        @php
            $hasChildren = !empty($item['children']);
            if ($hasChildren) {
                $routePrefixes = isset($item['route']) ? [$item['route'], explode('.', $item['route'])[0] . '.*'] : [];
                $activeRoutes = $item['active_routes'] ?? $routePrefixes;
                $isActive = collect($activeRoutes)->contains(fn ($route) => request()->routeIs($route));
                
                $hasActiveChild = collect($item['children'])->contains(function ($child) {
                    if (empty($child['route'])) return false;
                    $childRoutes = $child['active_routes'] ?? [$child['route']];
                    return collect($childRoutes)->contains(fn ($r) => request()->routeIs($r));
                });
                $isActive = $isActive || $hasActiveChild;
            } else {
                $activeRoutes = $item['active_routes'] ?? (isset($item['route']) ? [$item['route']] : []);
                $isActive = collect($activeRoutes)->contains(fn ($route) => request()->routeIs($route));
            }

            $labelMap = [
                'Dashboard' => 'messages.dashboard',
                'Shop Branch' => 'messages.shop_branch',
                'Branch' => 'messages.branch',
                'Branch Role Permission' => 'messages.branch_role_permission',
                'CRM' => 'messages.crm',
                'Users' => 'messages.users',
                'Customers' => 'messages.customers',
                'Products' => 'messages.products',
                'Add New Product' => 'messages.add_new_product',
                'All Products' => 'messages.all_products',
                'Brands' => 'messages.brands',
                'Categories' => 'messages.categories',
                'Unit Types' => 'messages.unit_types',
                'Upload Product By CSV' => 'messages.upload_csv',
                'Print Barcode / Labels' => 'messages.print_barcode',
                'Barcode Print Report' => 'messages.barcode_report',
                'Variations' => 'messages.variations',
                'Sales' => 'messages.sales',
                'Inventory' => 'messages.inventory',
                'Reports' => 'messages.reports',
                'List' => 'messages.list',
                'Create' => 'messages.create',
                'Customer search' => 'messages.customer_search',
                'Add customer' => 'messages.add_customer',
                'POS terminal' => 'messages.pos_terminal',
                'Stock list' => 'messages.stock_list',
                'Stock adjustment' => 'messages.stock_adjustment',
                'Sales reports' => 'messages.sales_reports',
                'Inventory reports' => 'messages.inventory_reports',
            ];
            $translatedLabel = isset($labelMap[$item['label'] ?? '']) ? __($labelMap[$item['label']]) : ($item['label'] ?? '');
        @endphp
        @can($item['permission'])
            @if (!empty($item['children']))
                <li class="nav-group {{ $isActive ? 'show' : '' }}">
                    <a class="nav-link nav-group-toggle {{ $isActive ? 'active' : '' }}" href="#"
                        aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                        @if (!empty($item['icon']))
                            <svg class="nav-icon">
                                <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#{{ $item['icon'] }}"></use>
                            </svg>
                        @else
                            <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                        @endif
                        {{ $translatedLabel }}
                    </a>
                    <ul class="nav-group-items compact">
                        @include('admin.partials.sidebar-menu-items', ['items' => $item['children']])
                    </ul>
                </li>
            @elseif (!empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']))
                <li class="nav-item">
                    <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                        {{ $translatedLabel }}
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <span class="nav-link disabled" aria-disabled="true">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                        {{ $translatedLabel }}
                        @if (!empty($item['soon']))
                            <span class="badge badge-sm bg-secondary ms-auto">Soon</span>
                        @endif
                    </span>
                </li>
            @endif
        @endcan
    @endif
@endforeach
