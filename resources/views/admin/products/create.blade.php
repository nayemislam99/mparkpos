@extends('admin.layouts.dashboard')

@section('title', 'Add Product | ' . config('app.name', 'M POS'))

@section('content')
<div class="row">
    <div class="col-12 px-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please check errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add product</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0 text-dark">Add product</h4>
            </div>
            <div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> All Products
                </a>
            </div>
        </div>

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="addProductForm">
            @csrf
            <div class="row g-4">
                {{-- Left Main Column --}}
                <div class="col-12 col-xl-8">
                    {{-- Basic details Card matching Screenshot 2 --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Basic details</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="prodTitle">Product Title <span class="text-danger">*</span></label>
                                <input type="text" id="prodTitle" name="name" value="{{ old('name') }}" class="form-control" required placeholder="e.g. Yamaha FZ Engine Oil 1L">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label fw-semibold small mb-0" for="prodBrand">Product Brand</label>
                                        <button type="button" class="btn btn-sm btn-teal text-white py-0 px-2 small fw-semibold" data-bs-toggle="modal" data-bs-target="#quickBrandModal">
                                            + New
                                        </button>
                                    </div>
                                    <select id="prodBrand" name="brand_id" class="form-select form-select-sm">
                                        <option value="">-- Select Brand --</option>
                                        @foreach($brands as $b)
                                            <option value="{{ $b->id }}" {{ old('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label fw-semibold small mb-0" for="prodCat">Product Category</label>
                                        <button type="button" class="btn btn-sm btn-teal text-white py-0 px-2 small fw-semibold" data-bs-toggle="modal" data-bs-target="#quickCatModal">
                                            + New
                                        </button>
                                    </div>
                                    <select id="prodCat" name="category_id" class="form-select form-select-sm">
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $c)
                                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label class="form-label fw-semibold small mb-0" for="prodUnit">Unit Type <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-sm btn-teal text-white py-0 px-2 small fw-semibold" data-bs-toggle="modal" data-bs-target="#quickUnitModal">
                                            + New
                                        </button>
                                    </div>
                                    <select id="prodUnit" name="unit_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select Unit Type --</option>
                                        @foreach($unitTypes as $u)
                                            <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="purchasePrice">Purchase Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Tk</span>
                                        <input type="number" step="0.01" min="0" id="purchasePrice" name="cost_price" value="{{ old('cost_price', '0.00') }}" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="sellingPrice">Selling Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Tk</span>
                                        <input type="number" step="0.01" min="0" id="sellingPrice" name="sale_price" value="{{ old('sale_price', '0.00') }}" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- More pricing Card (Alternative selling prices) --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fw-bold text-dark">More pricing</span>
                                <small class="text-muted d-block">Alternative selling prices</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-teal text-white fw-semibold" id="addPriceRowBtn">
                                + Add price
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0" id="altPriceTable">
                                    <thead>
                                        <tr class="text-muted small border-bottom">
                                            <th>Label (e.g. Wholesale, Special)</th>
                                            <th style="width: 180px;">Price (Tk)</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="altPriceRows">
                                        <tr>
                                            <td>
                                                <input type="text" name="alternative_labels[]" class="form-control form-control-sm" placeholder="e.g. Wholesale">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="alternative_prices[]" class="form-control form-control-sm" placeholder="0.00">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 remove-price-row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Saved together with the product.</small>
                        </div>
                    </div>

                    {{-- Discount, VAT and income tax Card --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Discount, VAT and income tax</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold text-muted d-block">Discount Status</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="discount_status" id="discFlat" value="flat">
                                        <label class="btn btn-outline-secondary" for="discFlat">Flat</label>
                                        <input type="radio" class="btn-check" name="discount_status" id="discPct" value="percent">
                                        <label class="btn btn-outline-secondary" for="discPct">Percent</label>
                                        <input type="radio" class="btn-check" name="discount_status" id="discNo" value="no" checked>
                                        <label class="btn btn-outline-secondary" for="discNo">No</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold text-muted d-block">Vat Status</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="vat_status" id="vatYes" value="1">
                                        <label class="btn btn-outline-secondary" for="vatYes">Yes</label>
                                        <input type="radio" class="btn-check" name="vat_status" id="vatNo" value="0" checked>
                                        <label class="btn btn-outline-secondary" for="vatNo">No</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-semibold text-muted d-block">IT Status</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <input type="radio" class="btn-check" name="it_status" id="itYes" value="1">
                                        <label class="btn btn-outline-secondary" for="itYes">Yes</label>
                                        <input type="radio" class="btn-check" name="it_status" id="itNo" value="0" checked>
                                        <label class="btn btn-outline-secondary" for="itNo">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Product Type Card (Simple, Variable, Multiple Variation) --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Product Type</span>
                        </div>
                        <div class="card-body p-4">
                            <ul class="nav nav-pills mb-4 nav-justified" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active fw-semibold" id="tab-simple" data-bs-toggle="pill" data-bs-target="#content-simple" type="button" role="tab">
                                        Simple <br><small class="fw-normal opacity-75">One price, one barcode</small>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold" id="tab-variable" data-bs-toggle="pill" data-bs-target="#content-variable" type="button" role="tab">
                                        Variable <br><small class="fw-normal opacity-75">Pick items from one variation</small>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fw-semibold" id="tab-multi" data-bs-toggle="pill" data-bs-target="#content-multi" type="button" role="tab">
                                        Multiple Variation <br><small class="fw-normal opacity-75">Every mix of two or more</small>
                                    </button>
                                </li>
                            </ul>
                            <input type="hidden" name="product_type" id="productTypeInput" value="simple">

                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="content-simple" role="tabpanel">
                                    <div class="p-3 bg-light rounded text-muted small">
                                        <i class="bi bi-check-circle me-1 text-success"></i> Standard single product with individual pricing, SKU, and barcode.
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="content-variable" role="tabpanel">
                                    {{-- Variations builder --}}
                                    <div class="row g-3">
                                        <div class="col-12 col-md-5">
                                            <label class="fw-semibold small text-muted mb-2">Available Variations</label>
                                            <div class="border rounded p-2 bg-light" style="max-height: 250px; overflow-y: auto;">
                                                @foreach($variations as $var)
                                                    <div class="mb-3">
                                                        <span class="fw-bold small text-dark d-block mb-1">{{ $var->name }}:</span>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($var->items ?? [] as $it)
                                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 small var-pill-btn"
                                                                        data-var-name="{{ $var->name }}" data-item="{{ $it }}">
                                                                    {{ $it }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-7">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="fw-semibold small text-muted">Generated Variations</span>
                                                <button type="button" class="btn btn-sm btn-teal text-white" id="generateVarsBtn">
                                                    Generate Variations
                                                </button>
                                            </div>
                                            <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                                                <table class="table table-sm align-middle mb-0" id="varTable">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th>Variation</th>
                                                            <th style="width: 80px;">P Price</th>
                                                            <th style="width: 80px;">S Price</th>
                                                            <th style="width: 90px;">Barcode</th>
                                                            <th style="width: 40px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="varRows">
                                                        <tr>
                                                            <td colspan="5" class="text-center py-3 text-muted small">Click variation items on the left to add here.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="content-multi" role="tabpanel">
                                    <div class="p-3 bg-light rounded text-muted small">
                                        <i class="bi bi-shuffle me-1 text-primary"></i> Combine variations (e.g. Size + Color) dynamically with independent barcode and SKU per combo.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Description Card --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Description</span>
                        </div>
                        <div class="card-body p-4">
                            <textarea name="description" class="form-control" rows="4" placeholder="Product description, technical specifications, notes...">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="col-12 col-xl-4">
                    {{-- Image Card matching Screenshot 2 --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Image (80 X 80)</span>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="border rounded p-4 bg-light mb-2 position-relative" style="min-height: 120px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                <i class="bi bi-image fs-1 text-secondary mb-1"></i>
                                <span class="fw-semibold text-primary">Choose an image</span>
                                <small class="text-muted">JPG, PNG or WEBP</small>
                                <input type="file" name="image" class="form-control position-absolute w-100 h-100 opacity-0 top-0 start-0" style="cursor: pointer;" accept="image/*">
                            </div>
                        </div>
                    </div>

                    {{-- Barcode and SKU Card --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Barcode and SKU</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="barcodeInput">Barcode</label>
                                <input type="text" id="barcodeInput" name="barcode" value="{{ old('barcode') }}" class="form-control form-control-sm mb-2" placeholder="Scan or enter barcode">
                                <button type="button" class="btn btn-sm btn-teal text-white w-100 fw-semibold" id="genBarcodeBtn">
                                    Generate Barcode
                                </button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="skuInput">SKU</label>
                                <input type="text" id="skuInput" name="sku" value="{{ old('sku') }}" class="form-control form-control-sm mb-2" placeholder="e.g. SKU-1001">
                                <button type="button" class="btn btn-sm btn-teal text-white w-100 fw-semibold" id="genSkuBtn">
                                    Generate SKU
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Stock alert and warranty Card --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-header bg-white border-bottom py-3">
                            <span class="fw-bold text-dark">Stock alert and warranty</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="alertQtyInput">Alert Quantity</label>
                                <input type="number" id="alertQtyInput" name="alert_quantity" value="{{ old('alert_quantity', '5') }}" class="form-control form-control-sm">
                                <small class="text-muted">When stock falls to or below this, alert will trigger.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold d-block">Warranty available</label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check" name="has_warranty" id="warrYes" value="1">
                                    <label class="btn btn-outline-secondary" for="warrYes">Yes</label>
                                    <input type="radio" class="btn-check" name="has_warranty" id="warrNo" value="0" checked>
                                    <label class="btn btn-outline-secondary" for="warrNo">No</label>
                                </div>
                            </div>

                            <div class="mb-3 pt-2 border-top">
                                <label class="form-label small fw-semibold" for="initialStockInput">Initial Opening Stock</label>
                                <input type="number" step="0.01" min="0" id="initialStockInput" name="initial_stock" value="0" class="form-control form-control-sm">
                            </div>
                            <div>
                                <label class="form-label small fw-semibold" for="initialBranchSelect">Branch for Opening Stock</label>
                                <select id="initialBranchSelect" name="initial_branch_id" class="form-select form-select-sm">
                                    @foreach($branches as $br)
                                        <option value="{{ $br->id }}">{{ $br->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="d-grid gap-2">
                        <button type="submit" name="action" value="save_and_add" class="btn btn-primary fw-semibold py-2">
                            Save & Add New
                        </button>
                        <button type="submit" name="action" value="save" class="btn btn-success-pos text-white fw-bold py-2">
                            Save Product
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Quick Modals for Brand, Category, Unit --}}
<div class="modal fade" id="quickBrandModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form class="modal-content" id="quickBrandForm">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">New Brand</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="quickBrandName" class="form-control form-control-sm" required placeholder="Brand Name">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal text-white btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="quickCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form class="modal-content" id="quickCatForm">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="quickCatName" class="form-control form-control-sm" required placeholder="Category Name">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal text-white btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="quickUnitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form class="modal-content" id="quickUnitForm">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">New Unit Type</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="quickUnitName" class="form-control form-control-sm mb-2" required placeholder="Unit Name (e.g. Pcs, KG)">
                <input type="text" id="quickUnitCode" class="form-control form-control-sm" placeholder="Short Code (e.g. pcs)">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-teal text-white btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. More pricing rows
    const addPriceBtn = document.getElementById('addPriceRowBtn');
    const priceRows = document.getElementById('altPriceRows');

    if (addPriceBtn) {
        addPriceBtn.addEventListener('click', function() {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" name="alternative_labels[]" class="form-control form-control-sm" placeholder="e.g. Wholesale"></td>
                <td><input type="number" step="0.01" min="0" name="alternative_prices[]" class="form-control form-control-sm" placeholder="0.00"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 remove-price-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            priceRows.appendChild(tr);
        });
    }

    priceRows.addEventListener('click', function(e) {
        if (e.target.closest('.remove-price-row')) {
            e.target.closest('tr').remove();
        }
    });

    // 2. Barcode & SKU Generator
    document.getElementById('genBarcodeBtn').addEventListener('click', function() {
        fetch('{{ route('products.generate-barcode') }}')
            .then(res => res.json())
            .then(data => {
                document.getElementById('barcodeInput').value = data.barcode;
            });
    });

    document.getElementById('genSkuBtn').addEventListener('click', function() {
        fetch('{{ route('products.generate-sku') }}')
            .then(res => res.json())
            .then(data => {
                document.getElementById('skuInput').value = data.sku;
            });
    });

    // 3. Tab Product Type switch
    document.getElementById('tab-simple').addEventListener('click', () => document.getElementById('productTypeInput').value = 'simple');
    document.getElementById('tab-variable').addEventListener('click', () => document.getElementById('productTypeInput').value = 'variable');
    document.getElementById('tab-multi').addEventListener('click', () => document.getElementById('productTypeInput').value = 'multiple_variation');

    // 4. Quick Ajax Modals
    document.getElementById('quickBrandForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('quickBrandName').value;
        fetch('{{ route('brands.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token, 'Accept': 'application/json' },
            body: JSON.stringify({ name })
        }).then(res => res.json()).then(res => {
            if (res.success) {
                const sel = document.getElementById('prodBrand');
                const opt = new Option(res.data.name, res.data.id, true, true);
                sel.add(opt);
                bootstrap.Modal.getInstance(document.getElementById('quickBrandModal')).hide();
            }
        });
    });

    document.getElementById('quickCatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('quickCatName').value;
        fetch('{{ route('categories.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token, 'Accept': 'application/json' },
            body: JSON.stringify({ name })
        }).then(res => res.json()).then(res => {
            if (res.success) {
                const sel = document.getElementById('prodCat');
                const opt = new Option(res.data.name, res.data.id, true, true);
                sel.add(opt);
                bootstrap.Modal.getInstance(document.getElementById('quickCatModal')).hide();
            }
        });
    });

    document.getElementById('quickUnitForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('quickUnitName').value;
        const code = document.getElementById('quickUnitCode').value;
        fetch('{{ route('unit-types.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf_token, 'Accept': 'application/json' },
            body: JSON.stringify({ name, code })
        }).then(res => res.json()).then(res => {
            if (res.success) {
                const sel = document.getElementById('prodUnit');
                const opt = new Option(res.data.name, res.data.id, true, true);
                sel.add(opt);
                bootstrap.Modal.getInstance(document.getElementById('quickUnitModal')).hide();
            }
        });
    });

    // 5. Variation Tag Clicking
    const selectedVarItems = [];
    const varRows = document.getElementById('varRows');

    document.querySelectorAll('.var-pill-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.toggle('btn-primary');
            this.classList.toggle('btn-outline-secondary');
            const item = this.getAttribute('data-item');

            const idx = selectedVarItems.indexOf(item);
            if (idx >= 0) selectedVarItems.splice(idx, 1);
            else selectedVarItems.push(item);
        });
    });

    document.getElementById('generateVarsBtn').addEventListener('click', function() {
        if (selectedVarItems.length === 0) {
            alert('Please select at least one variation tag on the left.');
            return;
        }

        const pPrice = document.getElementById('purchasePrice').value || '0.00';
        const sPrice = document.getElementById('sellingPrice').value || '0.00';

        varRows.innerHTML = '';
        selectedVarItems.forEach((it, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" name="variations[${idx}][name]" value="${it}" class="form-control form-control-sm fw-semibold">
                </td>
                <td>
                    <input type="number" step="0.01" name="variations[${idx}][cost_price]" value="${pPrice}" class="form-control form-control-sm">
                </td>
                <td>
                    <input type="number" step="0.01" name="variations[${idx}][sale_price]" value="${sPrice}" class="form-control form-control-sm">
                </td>
                <td>
                    <input type="text" name="variations[${idx}][barcode]" placeholder="Auto" class="form-control form-control-sm">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button>
                </td>
            `;
            varRows.appendChild(tr);
        });
    });
});
</script>
@endpush
