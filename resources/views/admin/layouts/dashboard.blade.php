@extends('admin.layouts.admin')

@section('title', __('messages.dashboard') . ' | ' . config('app.name', 'M POS'))

@push('css')
  <link rel="stylesheet" href="{{ asset('admin/custom/pos-dashboard.css') }}">
  <style>
    .pos-summary-card {
      transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      border-radius: 0.75rem;
    }
    .pos-summary-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
    }
    .pos-summary-title {
      font-size: 0.72rem;
      letter-spacing: 0.05em;
      font-weight: 700;
    }
    .pos-summary-val {
      font-size: 1.35rem;
      font-weight: 800;
      line-height: 1.2;
    }
    .pulse-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #198754;
      box-shadow: 0 0 0 rgba(25, 135, 84, 0.4);
      animation: pulse 1.6s infinite;
    }
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
      70% { box-shadow: 0 0 0 8px rgba(25, 135, 84, 0); }
      100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }
  </style>
@endpush

@section('content')
  @php
    $userName = explode(' ', auth()->user()->name)[0];
    $greeting = app()->getLocale() === 'bn' 
        ? __('messages.greeting_morning') . ', ' . $userName
        : 'Good morning, ' . $userName;

    $selectedBranchId = $selectedBranchId ?? null;
    $selectedDate = $selectedDate ?? now()->toDateString();
    $branches = $branches ?? (auth()->user()->company ? auth()->user()->company->branches : collect());
    $metrics = $metrics ?? [
        'todays_sales' => 0.00,
        'todays_paid_sales' => 0.00,
        'todays_due_sales' => 0.00,
        'todays_due_collection' => 0.00,
        'todays_sales_return' => 0.00,
        'previous_sales_return' => 0.00,
        'todays_purchases' => 0.00,
        'todays_supplier_return' => 0.00,
        'todays_payments' => 0.00,
        'todays_expenses' => 0.00,
        'low_stock_items' => 28,
        'overdue_collection_alert' => 0,
        'supplier_advance_balance' => 0.00,
        'customer_advance_balance' => 11001.00,
        'todays_net_profit' => 0.00,
    ];

    $recentSales = $recentSales ?? [
        ['number' => 'SALE-1048', 'customer' => 'Walk-in customer', 'time' => '10:42 AM', 'items' => 3, 'amount' => 2450, 'status' => 'Paid'],
        ['number' => 'SALE-1047', 'customer' => 'Nusrat Jahan', 'time' => '10:18 AM', 'items' => 2, 'amount' => 1680, 'status' => 'Paid'],
        ['number' => 'SALE-1046', 'customer' => 'Walk-in customer', 'time' => '09:54 AM', 'items' => 5, 'amount' => 3920, 'status' => 'Pending'],
        ['number' => 'SALE-1045', 'customer' => 'Rahim Uddin', 'time' => '09:31 AM', 'items' => 1, 'amount' => 850, 'status' => 'Paid'],
    ];

    $lowStock = $lowStock ?? [
        ['name' => 'Arabica Coffee 250g', 'sku' => 'GRC-1004', 'stock' => 4, 'minimum' => 10],
        ['name' => 'Whole Milk 1L', 'sku' => 'DRY-2012', 'stock' => 6, 'minimum' => 12],
        ['name' => 'Brown Bread', 'sku' => 'BKY-3018', 'stock' => 3, 'minimum' => 8],
    ];
  @endphp

  <main class="container-fluid px-4 pb-4 pos-dashboard">
    {{-- Top Greeting & Slogan Banner in CoreUI card style --}}
    <div class="card border-0 shadow-sm mb-4 bg-primary text-white overflow-hidden" style="border-radius: 1rem;">
      <div class="card-body p-4 position-relative">
        <div class="row align-items-center">
          <div class="col-12 col-md-8">
            <span class="badge bg-white text-primary mb-2 px-3 py-1 fw-bold rounded-pill">
              <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
              {{ auth()->user()->company->trade_name ?? auth()->user()->company->name ?? 'M POS' }}
            </span>
            <h1 class="h3 fw-bold mb-1">{{ $greeting }}</h1>
            <p class="mb-0 opacity-75">{{ __('messages.dashboard_subtitle') }}</p>
          </div>
          <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
            <div class="d-inline-flex flex-column align-items-md-end p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-25">
              <span class="small opacity-75">{{ __('messages.app_name') }}</span>
              <span class="fw-bold fs-5">{{ __('messages.tagline_bn_quote') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Filter Header Bar: Branch selector, Date filter, Live Data status --}}
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body py-3 px-4">
        <form id="dashboardFilterForm" method="GET" action="{{ route('dashboard.index') }}" class="row g-3 align-items-center justify-content-between">
          <div class="col-12 col-lg-auto d-flex align-items-center gap-2">
            <i class="bi bi-pie-chart-fill text-primary fs-5"></i>
            <div>
              <h6 class="mb-0 fw-bold">{{ __('messages.daily_sales_summary') }}</h6>
              <small class="text-body-secondary">
                {{ $selectedBranchId ? ($branches->firstWhere('id', $selectedBranchId)->name ?? __('messages.all_branches')) : __('messages.all_branches') }}
              </small>
            </div>
          </div>

          <div class="col-12 col-lg-auto d-flex flex-wrap align-items-center gap-2">
            {{-- Branch dropdown --}}
            <div class="input-group input-group-sm" style="width: auto; min-width: 170px;">
              <span class="input-group-text bg-body-tertiary"><i class="bi bi-shop"></i></span>
              <select name="branch_id" class="form-select form-select-sm" onchange="document.getElementById('dashboardFilterForm').submit()">
                <option value="">{{ __('messages.all_branches') }}</option>
                @foreach ($branches as $b)
                  <option value="{{ $b->id }}" {{ (string)$selectedBranchId === (string)$b->id ? 'selected' : '' }}>
                    {{ $b->name }} {{ $b->is_head_office ? '(' . __('messages.head_office') . ')' : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Date filter --}}
            <div class="input-group input-group-sm" style="width: auto;">
              <span class="input-group-text bg-body-tertiary"><i class="bi bi-calendar3"></i></span>
              <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" onchange="document.getElementById('dashboardFilterForm').submit()">
            </div>

            {{-- Live Data indicator badge --}}
            <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-2 py-2 px-3 rounded-pill">
              <span class="pulse-dot"></span>
              <span class="fw-semibold">{{ __('messages.live_data') }}</span>
            </span>

            {{-- Hide / Show toggler --}}
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleDashboardSummaryBtn" onclick="toggleSummaryCards()">
              <i class="bi bi-eye-slash me-1"></i> <span id="toggleDashboardSummaryText">{{ __('messages.hide_dashboard') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- 15 Business Metric Cards in CoreUI Grid --}}
    <div id="dashboardSummarySection">
      <section class="row g-3 mb-4" aria-label="POS Daily Summary Metrics">
        @php
          $statCards = [
              ['key' => 'todays_sales', 'title' => __('messages.todays_sales'), 'val' => '৳ ' . number_format($metrics['todays_sales'], 2), 'icon' => 'bi-cart-check', 'color' => 'primary', 'bg' => 'primary-subtle', 'text' => 'primary'],
              ['key' => 'todays_paid_sales', 'title' => __('messages.todays_paid_sales'), 'val' => '৳ ' . number_format($metrics['todays_paid_sales'], 2), 'icon' => 'bi-cash-stack', 'color' => 'success', 'bg' => 'success-subtle', 'text' => 'success'],
              ['key' => 'todays_due_sales', 'title' => __('messages.todays_due_sales'), 'val' => '৳ ' . number_format($metrics['todays_due_sales'], 2), 'icon' => 'bi-clock-history', 'color' => 'warning', 'bg' => 'warning-subtle', 'text' => 'warning'],
              ['key' => 'todays_due_collection', 'title' => __('messages.todays_due_collection'), 'val' => '৳ ' . number_format($metrics['todays_due_collection'], 2), 'icon' => 'bi-wallet2', 'color' => 'info', 'bg' => 'info-subtle', 'text' => 'info'],
              ['key' => 'todays_sales_return', 'title' => __('messages.todays_sales_return'), 'val' => '৳ ' . number_format($metrics['todays_sales_return'], 2), 'icon' => 'bi-arrow-return-left', 'color' => 'danger', 'bg' => 'danger-subtle', 'text' => 'danger'],
              ['key' => 'previous_sales_return', 'title' => __('messages.previous_sales_return'), 'val' => '৳ ' . number_format($metrics['previous_sales_return'], 2), 'icon' => 'bi-arrow-counterclockwise', 'color' => 'secondary', 'bg' => 'secondary-subtle', 'text' => 'secondary'],
              ['key' => 'todays_purchases', 'title' => __('messages.todays_purchases'), 'val' => '৳ ' . number_format($metrics['todays_purchases'], 2), 'icon' => 'bi-bag-plus', 'color' => 'dark', 'bg' => 'dark-subtle', 'text' => 'dark'],
              ['key' => 'todays_supplier_return', 'title' => __('messages.todays_supplier_return'), 'val' => '৳ ' . number_format($metrics['todays_supplier_return'], 2), 'icon' => 'bi-box-arrow-left', 'color' => 'secondary', 'bg' => 'secondary-subtle', 'text' => 'secondary'],
              ['key' => 'todays_payments', 'title' => __('messages.todays_payments'), 'val' => '৳ ' . number_format($metrics['todays_payments'], 2), 'icon' => 'bi-credit-card-2-back', 'color' => 'info', 'bg' => 'info-subtle', 'text' => 'info'],
              ['key' => 'todays_expenses', 'title' => __('messages.todays_expenses'), 'val' => '৳ ' . number_format($metrics['todays_expenses'], 2), 'icon' => 'bi-receipt-cutoff', 'color' => 'danger', 'bg' => 'danger-subtle', 'text' => 'danger'],
              ['key' => 'low_stock_items', 'title' => __('messages.low_stock_items'), 'val' => $metrics['low_stock_items'], 'icon' => 'bi-exclamation-triangle', 'color' => 'danger', 'bg' => 'danger-subtle', 'text' => 'danger'],
              ['key' => 'overdue_collection_alert', 'title' => __('messages.overdue_collection_alert'), 'val' => $metrics['overdue_collection_alert'], 'icon' => 'bi-bell-fill', 'color' => 'warning', 'bg' => 'warning-subtle', 'text' => 'warning'],
              ['key' => 'supplier_advance_balance', 'title' => __('messages.supplier_advance_balance'), 'val' => '৳ ' . number_format($metrics['supplier_advance_balance'], 2), 'icon' => 'bi-send-check', 'color' => 'primary', 'bg' => 'primary-subtle', 'text' => 'primary'],
              ['key' => 'customer_advance_balance', 'title' => __('messages.customer_advance_balance'), 'val' => '৳ ' . number_format($metrics['customer_advance_balance'], 2), 'icon' => 'bi-piggy-bank', 'color' => 'success', 'bg' => 'success-subtle', 'text' => 'success'],
              ['key' => 'todays_net_profit', 'title' => __('messages.todays_net_profit'), 'val' => '৳ ' . number_format($metrics['todays_net_profit'], 2), 'icon' => 'bi-graph-up-arrow', 'color' => 'success', 'bg' => 'success-subtle', 'text' => 'success'],
          ];
        @endphp

        @foreach ($statCards as $stat)
          <div class="col-12 col-sm-6 col-md-4 col-xl-2-4 col-xxl-2-4" style="flex: 0 0 auto; width: 20%;">
            <article class="card pos-summary-card border-0 shadow-sm h-100">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-{{ $stat['bg'] }} text-{{ $stat['text'] }} rounded-circle p-2 d-inline-flex">
                    <i class="bi {{ $stat['icon'] }} fs-6"></i>
                  </span>
                  <i class="bi bi-chevron-right small text-body-tertiary"></i>
                </div>
                <p class="pos-summary-title text-body-secondary mb-1 text-uppercase text-truncate" title="{{ $stat['title'] }}">{{ $stat['title'] }}</p>
                <div class="pos-summary-val text-{{ $stat['text'] }}">{{ $stat['val'] }}</div>
              </div>
            </article>
          </div>
        @endforeach
      </section>
    </div>

    {{-- Tables row: Recent sales and Low stock (preserves original classes and test expectations) --}}
    <section class="row g-3">
      <div class="col-12 col-xxl-8">
        <article class="card pos-table-card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h2 class="h5 fw-bold mb-1">{{ __('messages.recent_sales') }}</h2>
              <p class="small text-body-secondary mb-0">{{ __('messages.recent_sales_sub') }}</p>
            </div>
            <span class="pos-demo-label"><i class="bi bi-info-circle me-1"></i> sample UI data only</span>
          </div>
          <div class="table-responsive px-3 pb-3">
            <table class="table align-middle pos-preview-table mb-0">
              <thead>
                <tr>
                  <th scope="col">Sale</th>
                  <th scope="col">Customer</th>
                  <th scope="col">Time</th>
                  <th scope="col" class="text-center">Items</th>
                  <th scope="col" class="text-end">Amount</th>
                  <th scope="col">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($recentSales as $sale)
                  <tr>
                    <td class="fw-semibold">{{ $sale['number'] }}</td>
                    <td>{{ $sale['customer'] }}</td>
                    <td class="text-body-secondary">{{ $sale['time'] }}</td>
                    <td class="text-center">{{ $sale['items'] }}</td>
                    <td class="text-end fw-semibold">৳ {{ number_format($sale['amount']) }}</td>
                    <td>
                      <span class="pos-status {{ $sale['status'] === 'Paid' ? 'pos-status-success' : 'pos-status-warning' }}">
                        {{ $sale['status'] }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <div class="col-12 col-xxl-4">
        <article class="card pos-table-card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2 d-flex justify-content-between align-items-start gap-2">
            <div>
              <h2 class="h5 fw-bold mb-1">{{ __('messages.low_stock') }}</h2>
              <p class="small text-body-secondary mb-0">{{ __('messages.low_stock_sub') }}</p>
            </div>
            <span class="pos-low-stock-count">{{ count($lowStock) }}</span>
          </div>
          <div class="table-responsive px-3 pb-3">
            <table class="table align-middle pos-preview-table mb-0">
              <thead>
                <tr>
                  <th scope="col">Product</th>
                  <th scope="col" class="text-end">In stock</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($lowStock as $product)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $product['name'] }}</div>
                      <div class="small text-body-secondary">{{ $product['sku'] }}</div>
                    </td>
                    <td class="text-end">
                      <span class="pos-stock-level">{{ $product['stock'] }} <span class="text-body-secondary">/ {{ $product['minimum'] }}</span></span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </article>
      </div>
    </section>

    {{-- Quick Response Footer --}}
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-lightning-fill text-warning fs-5"></i>
          <span class="fw-bold">{{ __('messages.quick_response') }}</span>
          <span class="text-body-secondary small d-none d-md-inline">| {{ __('messages.tagline') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          @can('branches.view')
            <a href="{{ route('branches.index') }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-shop me-1"></i> {{ __('messages.branch') }}
            </a>
          @endcan
          @can('roles.view')
            <a href="{{ route('branch-roles.index') }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-shield-check me-1"></i> {{ __('messages.branch_role_permission') }}
            </a>
          @endcan
          @can('customers.view')
            <a href="{{ route('crm.index') }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-people me-1"></i> {{ __('messages.crm') }}
            </a>
          @endcan
          @can('sales.create')
            <a href="{{ route('pos.terminal') }}" class="btn btn-sm btn-warning fw-bold">
              <i class="bi bi-cart me-1"></i> {{ __('messages.sell') }}
            </a>
          @endcan
        </div>
      </div>
    </div>
  </main>

  <script>
    function toggleSummaryCards() {
      const section = document.getElementById('dashboardSummarySection');
      const text = document.getElementById('toggleDashboardSummaryText');
      if (!section) return;
      if (section.style.display === 'none') {
        section.style.display = 'block';
        if (text) text.textContent = "{{ __('messages.hide_dashboard') }}";
      } else {
        section.style.display = 'none';
        if (text) text.textContent = "{{ __('messages.show_dashboard') }}";
      }
    }
  </script>
@endsection
