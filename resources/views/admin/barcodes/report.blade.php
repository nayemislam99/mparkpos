@extends('admin.layouts.dashboard')

@section('title', 'Barcode Print Report | ' . config('app.name', 'M POS'))

@section('content')
<div class="row">
    <div class="col-12 px-4">
        {{-- Header matching Screenshot 10 --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-upc-scan fs-3 text-primary"></i>
                <h4 class="mb-0 fw-bold text-dark">Barcode Print Report</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('barcodes.print') }}" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm">
                    <i class="bi bi-upc me-1"></i> Print Barcode
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary px-3 fw-semibold shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </div>

        {{-- Filters Card matching Screenshot 10 --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body p-4 bg-light rounded-3">
                <form action="{{ route('barcodes.report') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small fw-semibold" for="fromDate">From Date</label>
                        <input type="date" id="fromDate" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small fw-semibold" for="toDate">To Date</label>
                        <input type="date" id="toDate" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold" for="searchQ">Product / SKU / Barcode</label>
                        <input type="text" id="searchQ" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search product">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small fw-semibold" for="branchFilter">Branch</label>
                        <select id="branchFilter" name="branch_id" class="form-select form-select-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label small fw-semibold" for="userFilter">Printed By</label>
                        <select id="userFilter" name="printed_by" class="form-select form-select-sm">
                            <option value="">All Users</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ (string)$printedById === (string)$u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between pt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="variantOnly" name="variant_only" value="1" {{ $variantOnly ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold text-dark" for="variantOnly">
                                Only variant prints
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                            <a href="{{ route('barcodes.report') }}" class="btn btn-sm btn-outline-secondary px-3">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stat Summary Cards matching Screenshot 10 --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius: 10px;">
                    <h2 class="fw-bold mb-0 text-primary">{{ number_format($stats['total_stickers']) }}</h2>
                    <small class="text-muted fw-semibold text-uppercase">Total Stickers Printed</small>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius: 10px;">
                    <h2 class="fw-bold mb-0 text-dark">{{ number_format($stats['total_products']) }}</h2>
                    <small class="text-muted fw-semibold text-uppercase">Products</small>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius: 10px;">
                    <h2 class="fw-bold mb-0 text-dark">{{ number_format($stats['print_jobs']) }}</h2>
                    <small class="text-muted fw-semibold text-uppercase">Print Jobs</small>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius: 10px;">
                    <h2 class="fw-bold mb-0 text-dark">{{ number_format($stats['print_lines']) }}</h2>
                    <small class="text-muted fw-semibold text-uppercase">Print Lines</small>
                </div>
            </div>
        </div>

        {{-- Log Table matching Screenshot 10 --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small fw-semibold text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Lot</th>
                            <th>Variant Name</th>
                            <th>Variation</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th>Branch</th>
                            <th class="pe-3">Printed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $index => $log)
                            <tr>
                                <td class="ps-3 text-muted fw-semibold">
                                    {{ $logs->firstItem() ? ($logs->firstItem() + $index) : ($index + 1) }}
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $log->created_at?->format('d-m-Y') }}</div>
                                    <small class="text-muted">{{ $log->created_at?->format('h:i A') }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $log->product?->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $log->product?->barcode }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $log->product?->sku ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $log->lot ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $log->variant_name ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $log->variation ?: '—' }}</span>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ $log->quantity }}
                                </td>
                                <td class="text-end fw-semibold text-primary">
                                    ৳ {{ number_format($log->price, 2) }}
                                </td>
                                <td>
                                    <span class="small">{{ $log->branch?->name ?? 'Head Office' }}</span>
                                </td>
                                <td class="pe-3">
                                    <span class="small text-muted">{{ $log->printedBy?->name ?? 'System' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary"></i>
                                    No barcode print logs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
