@extends('admin.layouts.dashboard')

@section('title', __('messages.all_products') . ' | ' . config('app.name', 'Micro POS'))

@section('content')
<div class="row">
    <div class="col-12 px-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            {{-- Header --}}
            <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-body">{{ __('messages.all_products') }}</h4>
                    <small class="text-body-secondary fw-semibold">
                        {{ __('messages.products_count_summary', ['total' => $stats['total'], 'active' => $stats['active']]) }}
                        @if($stats['low_stock'] > 0)
                            &bull; <span class="text-danger">{{ __('messages.low_stock_count', ['count' => $stats['low_stock']]) }}</span>
                        @endif
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('products.csv-upload') }}" class="btn btn-sm btn-teal text-white px-3 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('messages.csv_import_export') }}
                    </a>
                    <a href="{{ route('products.export.csv') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold shadow-sm" title="{{ __('messages.download_csv_file') }}">
                        <i class="bi bi-download me-1"></i> CSV
                    </a>
                    <a href="{{ route('barcodes.print') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold shadow-sm">
                        <i class="bi bi-upc-scan me-1"></i> {{ __('messages.print_barcode') }}
                    </a>
                    @can('products.create')
                        <a href="{{ route('products.create') }}" class="btn btn-sm btn-success-pos text-white px-3 fw-semibold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> {{ __('messages.add_new_product') }}
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Filters Bar --}}
            <div class="card-body pb-0 pt-3 border-bottom bg-body-tertiary">
                <form action="{{ route('products.index') }}" method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-3 col-lg-2">
                        <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('messages.all_categories') }}</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ (string)$categoryId === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('messages.all_brands') }}</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (string)$brandId === (string)$b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('messages.status') }}: {{ __('messages.all') }}</option>
                            <option value="1" {{ $status === '1' ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                            <option value="0" {{ $status === '0' ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-4 ms-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="{{ __('messages.search_product_placeholder') }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            @if($search !== '' || $categoryId || $brandId || $status !== null)
                                <a href="{{ route('products.index') }}" class="btn btn-outline-danger" title="{{ __('messages.reset') }}">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small fw-semibold text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 50px;">{{ __('messages.si') }}</th>
                            <th>{{ __('messages.table_product') }}</th>
                            <th>{{ __('messages.table_brand_category') }}</th>
                            <th>{{ __('messages.table_cost_price') }}</th>
                            <th>{{ __('messages.table_sale_price') }}</th>
                            <th>{{ __('messages.table_stock') }}</th>
                            <th>{{ __('messages.table_status') }}</th>
                            <th class="text-end pe-3" style="width: 140px;">{{ __('messages.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $prod)
                            @php
                                $currentStock = $prod->branchStocks->sum('quantity');
                                $isLowStock = $prod->track_stock && $currentStock <= $prod->alert_quantity;
                            @endphp
                            <tr>
                                <td class="ps-3 fw-semibold text-muted">
                                    {{ $products->firstItem() ? ($products->firstItem() + $index) : ($index + 1) }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded bg-light border p-1 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            @if($prod->image)
                                                <img src="{{ asset('storage/' . $prod->image) }}" alt="" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                            @else
                                                <i class="bi bi-box-seam text-secondary"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body">{{ $prod->name }}</div>
                                            <small class="text-body-secondary d-block font-monospace">
                                                SKU: {{ $prod->sku }} | Barcode: {{ $prod->barcode }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-body">{{ $prod->brandModel?->name ?: ($prod->brand ?: '—') }}</div>
                                    <small class="text-body-secondary">{{ $prod->category?->name ?: __('messages.uncategorized') }}</small>
                                </td>
                                <td class="text-secondary">
                                    ৳ {{ number_format($prod->cost_price, 2) }}
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">৳ {{ number_format($prod->sale_price, 2) }}</span>
                                </td>
                                <td>
                                    @if($prod->track_stock)
                                        <span class="badge {{ $isLowStock ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} px-2 py-1 rounded-pill">
                                            {{ $currentStock }} {{ $prod->unitType?->name ?: $prod->unit }}
                                        </span>
                                    @else
                                        <span class="badge bg-body-tertiary text-body border">{{ __('messages.no_limit') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($prod->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">{{ __('messages.active') }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">{{ __('messages.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        <form action="{{ route('products.toggle', $prod->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-teal text-white" title="{{ $prod->is_active ? __('messages.deactivate') : __('messages.activate') }}">
                                                {{ $prod->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-basket fs-1 d-block mb-2 text-secondary"></i>
                                    {{ __('messages.no_products_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
