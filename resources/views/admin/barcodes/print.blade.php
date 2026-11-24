@extends('admin.layouts.dashboard')

@section('title', 'Print Product Barcode | ' . config('app.name', 'Micro POS'))

@section('content')
<div class="row">
    <div class="col-12 px-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Barcode</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0 text-body">Print product barcode</h4>
            </div>
            <div>
                <a href="{{ route('barcodes.report') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold shadow-sm">
                    <i class="bi bi-clock-history me-1"></i> Barcode Print Report
                </a>
            </div>
        </div>

        <div class="row g-4">
            {{-- Left Column: Search & Selected Products --}}
            <div class="col-12 col-xl-8">
                {{-- Search Products Card --}}
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <span class="fw-bold text-body">Search products</span>
                    </div>
                    <div class="card-body p-4">
                        {{-- Universal Barcode Search Component Integration --}}
                        <x-barcode-search
                            id="barcodePrintSearch"
                            placeholder="Name, SKU or barcode (scan, or type + Enter)"
                            onSelect="handleProductSelectedForPrint"
                        />
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i> Scan a barcode with your scanner or type to search products. Matching items will appear in the table below.
                        </small>
                    </div>
                </div>

                {{-- Selected Products Card --}}
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-body">Selected products</span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllSelectedBtn" style="display: none;">
                            <i class="bi bi-trash"></i> Clear All
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="noProductsAlert" class="py-5 text-center text-muted">
                            <i class="bi bi-upc-scan fs-1 d-block mb-2 text-secondary"></i>
                            <h6 class="fw-bold mb-1">No products selected</h6>
                            <small>Search on the top or scan a barcode to add products for printing.</small>
                        </div>

                        <div class="table-responsive" id="selectedProductsTableContainer" style="display: none;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small fw-semibold text-uppercase">
                                    <tr>
                                        <th class="ps-3">Product Name</th>
                                        <th>SKU / Barcode</th>
                                        <th>Price (৳)</th>
                                        <th style="width: 130px;">Stickers Qty</th>
                                        <th style="width: 120px;">Lot No.</th>
                                        <th class="text-end pe-3" style="width: 60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedProductsList">
                                    {{-- Dynamically populated rows --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Label Settings (Matching Screenshots 6, 7, 8, 9) --}}
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm sticky-top" style="border-radius: 12px; top: 80px;">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-body">Label settings</span>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-primary fw-semibold" id="resetSettingsBtn">
                            Reset
                        </button>
                    </div>
                    <div class="card-body p-3" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                        {{-- 1. Shop section --}}
                        <div class="mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="fw-bold small text-dark mb-0">Shop</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="toggleShopName" checked>
                                </div>
                            </div>
                            <input type="text" id="shopNameInput" class="form-control form-control-sm mb-2" value="{{ $company->trade_name ?: $company->name }}">
                            <div class="form-check small text-muted">
                                <input class="form-check-input" type="checkbox" id="hideNameKeepSpace">
                                <label class="form-check-label" for="hideNameKeepSpace">Hide the name but keep its space</label>
                            </div>
                        </div>

                        {{-- 2. Product Details --}}
                        <div class="mb-3 border-bottom pb-3">
                            <label class="fw-bold small text-dark mb-2">Product</label>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small">Product name</span>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" id="nameFontSize" class="form-control form-control-sm text-center" style="width: 55px;" value="12">
                                    <span class="small text-muted">px</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="toggleProductName" checked>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small">Variant</span>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="toggleVariant" checked>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small">Mfg and expiry date</span>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" id="dateFontSize" class="form-control form-control-sm text-center" style="width: 55px;" value="6">
                                    <span class="small text-muted">px</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="toggleMfgExpiry">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Price --}}
                        <div class="mb-3 border-bottom pb-3">
                            <label class="fw-bold small text-dark mb-2">Price</label>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small">Sales price</span>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" id="priceFontSize" class="form-control form-control-sm text-center" style="width: 55px;" value="10">
                                    <span class="small text-muted">px</span>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="toggleSalesPrice" checked>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="small text-muted d-block mb-1">VAT</label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check" name="vatOption" id="vatInc" value="included">
                                    <label class="btn btn-outline-secondary" for="vatInc">Included</label>
                                    <input type="radio" class="btn-check" name="vatOption" id="vatExc" value="excluded" checked>
                                    <label class="btn btn-outline-secondary" for="vatExc">Excluded</label>
                                    <input type="radio" class="btn-check" name="vatOption" id="vatHide" value="hide">
                                    <label class="btn btn-outline-secondary" for="vatHide">Hide</label>
                                </div>
                            </div>
                        </div>

                        {{-- 4. Barcode configuration --}}
                        <div class="mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="fw-bold small text-dark mb-0">Barcode</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="toggleBarcode" checked>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted">Barcode height</span>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="number" id="barcodeHeight" class="form-control form-control-sm text-center" style="width: 60px;" value="30">
                                    <span class="small text-muted">px</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted">Bar density</span>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="number" id="barcodeDensity" class="form-control form-control-sm text-center" style="width: 60px;" value="1" min="1" max="4">
                                    <span class="small text-muted">x</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Barcode number</span>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="number" id="barcodeNumSize" class="form-control form-control-sm text-center" style="width: 60px;" value="8">
                                    <span class="small text-muted">px</span>
                                </div>
                            </div>
                        </div>

                        {{-- 5. Paper selection matching Screenshot 8/9 --}}
                        <div class="mb-4">
                            <label class="fw-bold small text-dark mb-1">Paper</label>
                            <select id="paperTypeSelect" class="form-select form-select-sm mb-2">
                                <option value="single_roll" selected>Single Rolls Print - 31.75mm x 25.4mm</option>
                                <option value="double_roll">Double Rolls Print - 31.75mm x 25.4mm X 2 Column</option>
                                <option value="sheet_40">40 Labels per sheet (A4)</option>
                                <option value="sheet_50">50 Labels per sheet (A4)</option>
                            </select>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Top and bottom gap</span>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="number" id="paperGap" class="form-control form-control-sm text-center" style="width: 50px;" value="2">
                                    <span class="small text-muted">px</span>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-teal text-white fw-semibold" id="saveSettingsBtn">
                                <i class="bi bi-save me-1"></i> Save settings
                            </button>
                            <button type="button" class="btn btn-primary fw-bold py-2" id="triggerPrintBtn">
                                <i class="bi bi-printer-fill me-1"></i> Print Barcodes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden Printable Area for window.print() --}}
<div id="barcodePrintArea" class="d-none"></div>
@endsection

@push('vendor-js')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
@endpush

@push('js')
<script>
let selectedProducts = [];

window.handleProductSelectedForPrint = function(product) {
    if (!product) return;

    const existing = selectedProducts.find(p => p.id === product.id && p.variation_id === product.variation_id);
    if (existing) {
        existing.qty += 1;
    } else {
        selectedProducts.push({
            id: product.id,
            variation_id: product.variation_id || null,
            name: product.name,
            sku: product.sku || '',
            barcode: product.barcode || product.sku || '10000' + product.id,
            price: product.sale_price,
            qty: 1,
            lot: ''
        });
    }

    renderSelectedTable();
};

function renderSelectedTable() {
    const list = document.getElementById('selectedProductsList');
    const container = document.getElementById('selectedProductsTableContainer');
    const emptyAlert = document.getElementById('noProductsAlert');
    const clearBtn = document.getElementById('clearAllSelectedBtn');

    if (selectedProducts.length === 0) {
        container.style.display = 'none';
        emptyAlert.style.display = 'block';
        clearBtn.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    emptyAlert.style.display = 'none';
    clearBtn.style.display = 'inline-block';
    list.innerHTML = '';

    selectedProducts.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3">
                <span class="fw-bold text-dark">${item.name}</span>
            </td>
            <td>
                <span class="badge bg-light text-dark border font-monospace">${item.barcode}</span>
            </td>
            <td>
                <span class="fw-bold text-primary">৳ ${parseFloat(item.price).toFixed(2)}</span>
            </td>
            <td>
                <input type="number" min="1" value="${item.qty}" class="form-control form-control-sm text-center"
                       onchange="updateProductQty(${index}, this.value)">
            </td>
            <td>
                <input type="text" value="${item.lot}" placeholder="Lot" class="form-control form-control-sm"
                       onchange="updateProductLot(${index}, this.value)">
            </td>
            <td class="text-end pe-3">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProduct(${index})" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        list.appendChild(tr);
    });
}

function updateProductQty(index, qty) {
    if (selectedProducts[index]) {
        selectedProducts[index].qty = Math.max(1, parseInt(qty) || 1);
    }
}

function updateProductLot(index, lot) {
    if (selectedProducts[index]) {
        selectedProducts[index].lot = lot;
    }
}

function removeProduct(index) {
    selectedProducts.splice(index, 1);
    renderSelectedTable();
}

document.getElementById('clearAllSelectedBtn').addEventListener('click', function() {
    selectedProducts = [];
    renderSelectedTable();
});

// Print execution
document.getElementById('triggerPrintBtn').addEventListener('click', function() {
    if (selectedProducts.length === 0) {
        alert('Please select at least one product to print barcodes.');
        return;
    }

    const shopName = document.getElementById('shopNameInput').value;
    const showShopName = document.getElementById('toggleShopName').checked;
    const showProductName = document.getElementById('toggleProductName').checked;
    const showPrice = document.getElementById('toggleSalesPrice').checked;
    const nameSize = document.getElementById('nameFontSize').value || '12';
    const priceSize = document.getElementById('priceFontSize').value || '10';
    const barcodeHeight = document.getElementById('barcodeHeight').value || '30';
    const barcodeWidth = document.getElementById('barcodeDensity').value || '1';
    const paperType = document.getElementById('paperTypeSelect').value;

    // Log the print request to the backend database
    const payload = {
        items: selectedProducts.map(p => ({
            product_id: p.id,
            product_variation_id: p.variation_id,
            variant_name: null,
            variation: null,
            quantity: p.qty,
            price: p.price,
            lot: p.lot || null
        }))
    };

    fetch('/dashboard/barcodes/log-print', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrf_token,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    }).catch(err => console.error('Failed to log print', err));

    // Open clean print window
    const printWin = window.open('', '_blank', 'width=800,height=600');
    let stickersHtml = '';

    selectedProducts.forEach(prod => {
        for (let i = 0; i < prod.qty; i++) {
            stickersHtml += `
                <div class="barcode-sticker">
                    ${showShopName ? `<div class="sticker-shop">${shopName}</div>` : ''}
                    ${showProductName ? `<div class="sticker-name" style="font-size: ${nameSize}px;">${prod.name}</div>` : ''}
                    <svg class="barcode-svg" id="bc_${prod.id}_${i}"></svg>
                    ${showPrice ? `<div class="sticker-price" style="font-size: ${priceSize}px;">Price: ৳ ${parseFloat(prod.price).toFixed(2)}</div>` : ''}
                </div>
            `;
        }
    });

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Barcodes</title>
            <style>
                @page { size: auto; margin: 4mm; }
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; }
                .stickers-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 3mm;
                    justify-content: flex-start;
                }
                .barcode-sticker {
                    width: 38mm;
                    box-sizing: border-box;
                    padding: 2mm;
                    text-align: center;
                    border: 1px dashed #ccc;
                    page-break-inside: avoid;
                }
                .sticker-shop { font-weight: bold; font-size: 9px; text-transform: uppercase; margin-bottom: 1px; }
                .sticker-name { font-weight: 600; line-height: 1.1; margin-bottom: 2px; }
                .sticker-price { font-weight: bold; margin-top: 2px; }
                .barcode-svg { max-width: 100%; height: ${barcodeHeight}px; }
                @media print {
                    .barcode-sticker { border: none !important; }
                }
            </style>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
        </head>
        <body>
            <div class="stickers-container">
                ${stickersHtml}
            </div>
            <script>
                window.onload = function() {
                    ${selectedProducts.map(prod => `
                        for(var j=0; j<${prod.qty}; j++) {
                            var el = document.getElementById("bc_${prod.id}_" + j);
                            if(el) {
                                JsBarcode(el, "${prod.barcode}", {
                                    format: "CODE128",
                                    width: ${barcodeWidth},
                                    height: ${barcodeHeight},
                                    displayValue: true,
                                    fontSize: 8,
                                    margin: 1
                                });
                            }
                        }
                    `).join('\n')}
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWin.document.close();
});
</script>
@endpush
