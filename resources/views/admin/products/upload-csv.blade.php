@extends('admin.layouts.dashboard')

@section('title', __('messages.csv_bulk_product_upload') . ' | ' . config('app.name', 'Micro POS'))

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

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            {{-- Header --}}
            <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-body">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>{{ __('messages.csv_bulk_product_upload') }}
                    </h4>
                    <small class="text-body-secondary fw-semibold">
                        {{ __('messages.csv_import_desc') }}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('products.sample.csv') }}" class="btn btn-sm btn-outline-success px-3 fw-semibold shadow-sm">
                        <i class="bi bi-download me-1"></i> {{ __('messages.download_sample_template') }}
                    </a>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-semibold shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('messages.all_products') }}
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-4">
                    {{-- Upload Section --}}
                    <div class="col-12 col-lg-7">
                        <div class="border rounded-3 p-4 bg-body-tertiary">
                            <h5 class="fw-bold text-body mb-3">
                                <i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>{{ __('messages.csv_import_title') }}
                            </h5>
                            <p class="text-body-secondary small mb-4">
                                {{ __('messages.csv_upload_guide_step2') }}
                            </p>

                            <form action="{{ route('products.import.csv') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-body" for="productCsvInput">
                                        {{ __('messages.choose_csv_file') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" name="csv_file" id="productCsvInput" accept=".csv" required class="form-control form-control-lg">
                                    <div class="form-text text-body-secondary small mt-2">
                                        {{ __('messages.csv_instructions') }}
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                                        <i class="bi bi-upload me-1"></i> {{ __('messages.upload_and_import') }}
                                    </button>
                                    <a href="{{ route('products.sample.csv') }}" class="btn btn-outline-secondary px-3 py-2 fw-semibold">
                                        <i class="bi bi-file-earmark-arrow-down me-1"></i> {{ __('messages.download_sample_template') }}
                                    </a>
                                </div>
                            </form>
                        </div>

                        {{-- Instructions --}}
                        <div class="border rounded-3 p-4 bg-body-tertiary mt-4">
                            <h6 class="fw-bold text-body mb-3">
                                <i class="bi bi-info-circle-fill text-info me-2"></i>{{ __('messages.csv_upload_guide_title') }}
                            </h6>
                            <ol class="text-body-secondary small mb-0 ps-3" style="line-height: 1.8;">
                                <li>{{ __('messages.csv_upload_guide_step1') }}</li>
                                <li>{{ __('messages.csv_upload_guide_step2') }}</li>
                                <li>{{ __('messages.csv_upload_guide_step3') }}</li>
                                <li>If category or brand doesn't exist, it will be automatically created.</li>
                                <li>Duplicate barcodes or SKUs will be automatically assigned unique identifiers to avoid conflicts.</li>
                            </ol>
                        </div>
                    </div>

                    {{-- Quick References Column --}}
                    <div class="col-12 col-lg-5">
                        {{-- Categories Reference --}}
                        <div class="border rounded-3 p-3 bg-body-tertiary mb-3">
                            <h6 class="fw-bold text-body mb-2">
                                <i class="bi bi-tags-fill text-primary me-1"></i> {{ __('messages.available_categories') }}
                            </h6>
                            <div class="d-flex flex-wrap gap-1" style="max-height: 140px; overflow-y: auto;">
                                @forelse($categories as $cat)
                                    <span class="badge bg-secondary-subtle text-body border px-2 py-1">{{ $cat->name }}</span>
                                @empty
                                    <span class="text-body-secondary small">No categories registered yet.</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- Brands Reference --}}
                        <div class="border rounded-3 p-3 bg-body-tertiary mb-3">
                            <h6 class="fw-bold text-body mb-2">
                                <i class="bi bi-award-fill text-warning me-1"></i> {{ __('messages.available_brands') }}
                            </h6>
                            <div class="d-flex flex-wrap gap-1" style="max-height: 140px; overflow-y: auto;">
                                @forelse($brands as $b)
                                    <span class="badge bg-secondary-subtle text-body border px-2 py-1">{{ $b->name }}</span>
                                @empty
                                    <span class="text-body-secondary small">No brands registered yet.</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- Units Reference --}}
                        <div class="border rounded-3 p-3 bg-body-tertiary">
                            <h6 class="fw-bold text-body mb-2">
                                <i class="bi bi-rulers text-teal me-1"></i> {{ __('messages.available_units') }}
                            </h6>
                            <div class="d-flex flex-wrap gap-1" style="max-height: 140px; overflow-y: auto;">
                                @forelse($unitTypes as $u)
                                    <span class="badge bg-secondary-subtle text-body border px-2 py-1">{{ $u->name }}</span>
                                @empty
                                    <span class="text-body-secondary small">No units registered yet.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
