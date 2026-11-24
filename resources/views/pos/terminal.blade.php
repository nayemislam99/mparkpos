@extends('pos.layouts.terminal')

@section('title', 'POS terminal | Micro POS')

@push('css')
    <link rel="stylesheet" href="{{ asset('admin/custom/pos-terminal.css') }}">
@endpush

@section('content')
    <main class="container-fluid pos-terminal" id="posTerminal"
        data-context-url="{{ route('pos.context') }}"
        data-products-url="{{ route('pos.products') }}"
        data-customers-url="{{ route('pos.customers') }}"
        data-customer-create-url="{{ route('pos.customers.store') }}"
        data-checkout-url="{{ route('pos.checkout') }}"
        data-due-invoices-url="{{ route('pos.due-invoices') }}"
        data-invoice-payment-url="{{ url('/dashboard/pos/invoices/__SALE__/payments') }}">
        <header class="pos-toolbar">
            <div class="pos-toolbar-brand">
                <strong>Micro POS</strong>
                <span class="pos-toolbar-divider"></span>
                <span id="posCurrentDate"></span>
                <time id="posCurrentTime"></time>
            </div>
            <div class="pos-tenant-selectors">
                <label id="posCompanyField" hidden>
                    <span class="visually-hidden">Store</span>
                    <select id="posCompany" class="form-select"></select>
                </label>
                <label>
                    <span class="visually-hidden">Branch</span>
                    <select id="posBranch" class="form-select"></select>
                </label>
            </div>
            <div class="pos-toolbar-actions">
                <button class="btn pos-toolbar-button pos-walkin-button" id="posWalkInCustomer" type="button">
                    <i class="bi bi-person-walking" aria-hidden="true"></i><span>Walk-in</span>
                </button>
                <button class="btn pos-toolbar-button pos-add-customer-button" id="posNewCustomer" type="button" hidden>
                    <i class="bi bi-person-plus" aria-hidden="true"></i><span>Add customer</span>
                </button>
                <button class="btn pos-toolbar-button pos-clear-button" id="posClearCart" type="button" disabled>
                    <i class="bi bi-trash3" aria-hidden="true"></i><span>Clear</span>
                </button>
                <button class="btn pos-toolbar-icon-button" id="posRefreshCatalog" type="button" aria-label="Refresh products" title="Refresh products">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                </button>
                <div class="dropdown">
                    <button class="btn pos-toolbar-icon-button" type="button"
                        aria-label="Choose color theme" title="Choose color theme"
                        aria-expanded="false" data-coreui-toggle="dropdown">
                        <svg class="icon theme-icon-active" aria-hidden="true">
                            <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-contrast"></use>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach (['light' => 'sun', 'dark' => 'moon', 'auto' => 'contrast'] as $theme => $icon)
                            <li>
                                <button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="{{ $theme }}">
                                    <svg class="icon me-2" aria-hidden="true">
                                        <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-{{ $icon }}"></use>
                                    </svg>
                                    {{ ucfirst($theme) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <a class="btn pos-toolbar-button pos-dashboard-button" href="{{ route('dashboard.index') }}">
                    <i class="bi bi-speedometer2" aria-hidden="true"></i><span>Dashboard</span>
                </a>
            </div>
        </header>

        <div class="pos-notice" id="posNotice" role="status" hidden></div>
        <div class="pos-terminal-grid">
            <section class="pos-catalog-panel">
                <div class="pos-catalog-filters">
                    <label>
                        <span class="visually-hidden">Filter by category</span>
                        <select id="posCategoryFilter" class="form-select">
                            <option value="">All categories</option>
                        </select>
                    </label>
                    <label>
                        <span class="visually-hidden">Filter by brand</span>
                        <select id="posBrandFilter" class="form-select">
                            <option value="">All brands</option>
                        </select>
                    </label>
                </div>
                <div class="pos-search-wrap">
                    <i class="bi bi-search"></i>
                    <input id="posProductSearch" type="search" class="form-control"
                        placeholder="Product name, brand, category, SKU"
                        autocomplete="off" aria-label="Search products by name, brand, category, or SKU">
                    <button class="pos-search-clear" id="posClearProductSearch" type="button" aria-label="Clear product search" hidden>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pos-catalog-meta">
                    <span id="posResultCount">Loading products…</span>
                    <div class="pos-view-toggle" role="group" aria-label="Product display mode">
                        <button class="active" id="posGridView" type="button" aria-pressed="true" title="Grid view"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></button>
                        <button id="posListView" type="button" aria-pressed="false" title="List view"><i class="bi bi-list-ul" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="pos-product-grid" id="posProductGrid" aria-live="polite"></div>
                <div class="pos-empty-state" id="posProductsEmpty" hidden>
                    <i class="bi bi-box-seam"></i>
                    <strong>No matching products</strong>
                    <span>Try another product, brand, category, SKU, or barcode.</span>
                </div>
            </section>

            <section class="pos-cart-panel" aria-label="Current sale">
                <div class="pos-checkout-top">
                    <div>
                        <span class="pos-section-kicker">Selected customer</span>
                        <h2 id="posSelectedCustomerLabel"><i class="bi bi-person-walking" aria-hidden="true"></i> Walk-in customer</h2>
                    </div>
                    <span class="pos-cart-count" id="posCartCount">0 items</span>
                </div>
                <div class="pos-customer-card">
                    <div class="pos-customer-controls">
                        <div class="pos-customer-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="posCustomerSearch" type="search" class="form-control" placeholder="Search registered customers by name or phone" autocomplete="off" aria-label="Search registered customers by name or phone">
                            <div id="posCustomerResults" class="pos-customer-results" hidden></div>
                        </div>
                        <button class="btn btn-outline-secondary" id="posDueButton" type="button" disabled hidden>
                            <i class="bi bi-wallet2 me-1"></i> Collect due
                        </button>
                    </div>
                    <div class="pos-customer-due" id="posCustomerDue" hidden></div>
                </div>
                <div class="pos-barcode-wrap">
                    <i class="bi bi-upc-scan" aria-hidden="true"></i>
                    <input id="posBarcodeSearch" type="search" class="form-control" placeholder="Scan or enter barcode" autocomplete="off" aria-label="Scan or enter product barcode">
                    <span class="pos-barcode-count"><i class="bi bi-bag" aria-hidden="true"></i><b id="posCartCountBadge">0</b></span>
                </div>
                <div class="pos-cart-head">
                    <span>Product</span><span>Qty</span><span>Discount</span><span class="text-end">Total</span><span></span>
                </div>
                <div class="pos-cart-items" id="posCartItems">
                    <div class="pos-cart-empty">
                        <i class="bi bi-basket2"></i>
                        <strong>Your cart is empty</strong>
                        <span>Select a product or scan its barcode.</span>
                    </div>
                </div>
            </section>

            <aside class="pos-summary-panel" aria-label="Bill summary and payment">
                <div class="pos-summary-heading">
                    <h2><i class="bi bi-receipt" aria-hidden="true"></i> Bill Summary</h2>
                    <span id="posSummaryItemCount">0 items</span>
                </div>
                <div class="pos-totals">
                    <div><span>Total gross</span><strong id="posSubtotal">৳ 0.00</strong></div>
                    <div><span>Item discount</span><strong class="pos-discount-text" id="posLineDiscount">− ৳ 0.00</strong></div>
                    <div class="pos-order-discount">
                        <label for="posOrderDiscount">Order discount</label>
                        <div class="pos-discount-input">
                            <select id="posOrderDiscountType" class="form-select form-select-sm" aria-label="Order discount type">
                                <option value="flat">৳</option>
                                <option value="percent">%</option>
                            </select>
                            <input id="posOrderDiscount" class="form-control form-control-sm" type="number" min="0" step="0.01" value="0" aria-label="Order discount amount">
                        </div>
                    </div>
                    <div class="pos-customer-balance-row">
                        <span>Previous customer due</span>
                        <strong id="posCustomerDueSummary">৳ 0.00</strong>
                    </div>
                    <div class="pos-grand-total"><span>Total payable</span><strong id="posGrandTotal">৳ 0.00</strong></div>
                </div>

                <div class="pos-payment">
                    <div class="pos-payment-heading">
                        <span>Payment method</span>
                        <span class="pos-due-hint" id="posDueHint">Walk-in · full payment</span>
                    </div>
                    <div class="pos-payment-methods" role="group" aria-label="Payment method">
                        <button type="button" data-payment-method="cash"><i class="bi bi-cash-stack" aria-hidden="true"></i>Cash</button>
                        <button type="button" data-payment-method="card"><i class="bi bi-credit-card" aria-hidden="true"></i>Card</button>
                        <button type="button" data-payment-method="mobile_banking"><i class="bi bi-phone" aria-hidden="true"></i>Mobile</button>
                    </div>
                    <div class="pos-payment-list" id="posPaymentList" aria-live="polite">
                        <span class="pos-payment-empty">Choose a method above to add a payment.</span>
                    </div>
                    <div class="pos-payment-controls">
                        <span>Received now</span>
                        <strong id="posPaymentTotal">৳ 0.00</strong>
                    </div>
                    <div class="pos-payment-summary">
                        <span>Due on this invoice <strong id="posRemainingDue">৳ 0.00</strong></span>
                        <span id="posChangeWrap" hidden>Change <strong id="posChange">৳ 0.00</strong></span>
                    </div>
                    <button class="btn btn-primary pos-checkout-button" id="posCheckout" type="button" disabled>
                        <i class="bi bi-check2-circle me-2"></i> Complete sale
                    </button>
                    <p class="pos-payment-note"><i class="bi bi-info-circle me-1"></i>Previous customer dues are not applied to this sale.</p>
                </div>
            </aside>
        </div>
    </main>

    <div class="pos-modal-backdrop" id="posCustomerModal" hidden>
        <section class="pos-modal" role="dialog" aria-modal="true" aria-labelledby="posCustomerModalTitle">
            <div class="pos-modal-heading">
                <div><span class="pos-section-kicker">Customer</span><h2 id="posCustomerModalTitle">Add customer</h2></div>
                <button class="btn-close" type="button" data-pos-close="customer" aria-label="Close"></button>
            </div>
            <form id="posCustomerForm">
                <label>Name <input class="form-control" name="name" required maxlength="160" autocomplete="name"></label>
                <label>Phone <input class="form-control" name="phone" maxlength="40" autocomplete="tel"></label>
                <label>Email <input class="form-control" name="email" type="email" maxlength="255" autocomplete="email"></label>
                <div class="pos-modal-actions">
                    <button class="btn btn-outline-secondary" type="button" data-pos-close="customer">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save customer</button>
                </div>
            </form>
        </section>
    </div>

    <div class="pos-modal-backdrop" id="posDueModal" hidden>
        <section class="pos-modal pos-due-modal" role="dialog" aria-modal="true" aria-labelledby="posDueModalTitle">
            <div class="pos-modal-heading">
                <div><span class="pos-section-kicker">Invoice-specific payment</span><h2 id="posDueModalTitle">Collect due</h2></div>
                <button class="btn-close" type="button" data-pos-close="due" aria-label="Close"></button>
            </div>
            <div class="pos-due-invoice-list" id="posDueInvoices"></div>
            <form id="posDuePaymentForm" hidden>
                <input type="hidden" name="sale_id">
                <label>Payment amount <input class="form-control" name="amount" type="number" min="0.01" step="0.01" required></label>
                <label>Method
                    <select class="form-select" name="method">
                        <option value="cash">Cash</option><option value="card">Card</option><option value="mobile_banking">Mobile banking</option>
                    </select>
                </label>
                <label>Reference (optional) <input class="form-control" name="reference" maxlength="100"></label>
                <button class="btn btn-primary w-100 mt-3" type="submit">Record payment on invoice</button>
            </form>
        </section>
    </div>

    <div class="pos-modal-backdrop" id="posReceiptModal" hidden>
        <section class="pos-modal pos-receipt-modal" role="dialog" aria-modal="true" aria-labelledby="posReceiptTitle">
            <div class="pos-receipt-success"><i class="bi bi-check-lg"></i></div>
            <h2 id="posReceiptTitle">Sale completed</h2>
            <p id="posReceiptInvoice"></p>
            <div class="pos-receipt-totals" id="posReceiptTotals"></div>
            <div class="pos-modal-actions">
                <button class="btn btn-outline-secondary" type="button" id="posNewSale">New sale</button>
                <button class="btn btn-primary" type="button" id="posPrintReceipt"><i class="bi bi-printer me-1"></i> Print</button>
            </div>
        </section>
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('common/js/pos-terminal.js') }}"></script>
@endpush
