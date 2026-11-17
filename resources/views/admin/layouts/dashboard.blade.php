@extends('admin.layouts.admin')

@section('title', 'Dashboard | M POS')

@push('css')
  <link rel="stylesheet" href="{{ asset('admin/custom/pos-dashboard.css') }}">
@endpush

@section('content')
  @php
    $recentSales = [
        ['number' => 'SALE-1048', 'customer' => 'Walk-in customer', 'time' => '10:42 AM', 'items' => 3, 'amount' => 2450, 'status' => 'Paid'],
        ['number' => 'SALE-1047', 'customer' => 'Nusrat Jahan', 'time' => '10:18 AM', 'items' => 2, 'amount' => 1680, 'status' => 'Paid'],
        ['number' => 'SALE-1046', 'customer' => 'Walk-in customer', 'time' => '09:54 AM', 'items' => 5, 'amount' => 3920, 'status' => 'Pending'],
        ['number' => 'SALE-1045', 'customer' => 'Rahim Uddin', 'time' => '09:31 AM', 'items' => 1, 'amount' => 850, 'status' => 'Paid'],
    ];

    $lowStock = [
        ['name' => 'Arabica Coffee 250g', 'sku' => 'GRC-1004', 'stock' => 4, 'minimum' => 10],
        ['name' => 'Whole Milk 1L', 'sku' => 'DRY-2012', 'stock' => 6, 'minimum' => 12],
        ['name' => 'Brown Bread', 'sku' => 'BKY-3018', 'stock' => 3, 'minimum' => 8],
    ];
  @endphp

  <main class="container-fluid px-4 pb-4 pos-dashboard">
    <div class="pos-page-heading">
      <div>
        <p class="pos-eyebrow mb-1">Store overview</p>
        <h1 class="h3 mb-1 fw-bold">Good morning, {{ explode(' ', auth()->user()->name)[0] }}</h1>
        <p class="text-body-secondary mb-0">Here is what is happening in your store today.</p>
      </div>
      <span class="pos-demo-label"><i class="bi bi-info-circle"></i> UI preview · sample data</span>
    </div>

    <section class="row g-3 mb-4" aria-label="Today's summary">
      @foreach ([
          ['label' => "Today's sales", 'value' => '৳ 24,580', 'detail' => '12 completed orders', 'icon' => 'bi-currency-exchange', 'tone' => 'green', 'change' => '+8.2%'],
          ['label' => 'Orders', 'value' => '18', 'detail' => '6 still in progress', 'icon' => 'bi-receipt', 'tone' => 'blue', 'change' => '+3 today'],
          ['label' => 'Products', 'value' => '248', 'detail' => 'Across all categories', 'icon' => 'bi-box-seam', 'tone' => 'violet', 'change' => 'Catalog'],
          ['label' => 'Low stock', 'value' => '7 items', 'detail' => 'Need a stock check', 'icon' => 'bi-exclamation-triangle', 'tone' => 'amber', 'change' => 'Review'],
      ] as $stat)
        <div class="col-12 col-sm-6 col-xxl-3">
          <article class="card pos-stat-card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <p class="text-body-secondary small fw-semibold mb-2">{{ $stat['label'] }}</p>
                  <div class="pos-stat-value">{{ $stat['value'] }}</div>
                </div>
                <span class="pos-stat-icon pos-tone-{{ $stat['tone'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
              </div>
              <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                <span class="small text-body-secondary">{{ $stat['detail'] }}</span>
                <span class="pos-stat-change pos-tone-text-{{ $stat['tone'] }}">{{ $stat['change'] }}</span>
              </div>
            </div>
          </article>
        </div>
      @endforeach
    </section>

    <section class="row g-3">
      <div class="col-12 col-xxl-8">
        <article class="card pos-table-card h-100">
          <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h2 class="h5 fw-bold mb-1">Recent sales</h2>
              <p class="small text-body-secondary mb-0">A quick look at today's latest orders.</p>
            </div>
            <span class="pos-demo-label">Sample data</span>
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
        <article class="card pos-table-card h-100">
          <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2 d-flex justify-content-between align-items-start gap-2">
            <div>
              <h2 class="h5 fw-bold mb-1">Low stock</h2>
              <p class="small text-body-secondary mb-0">Items below their reorder level.</p>
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

    <p class="pos-preview-note mt-3 mb-0">
      <i class="bi bi-info-circle me-1"></i>
      These sales and stock rows are sample UI data only; live POS transactions and inventory are not connected yet.
    </p>
  </main>
@endsection
