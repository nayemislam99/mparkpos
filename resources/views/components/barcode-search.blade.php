@props([
    'id' => 'barcodeSearchInput',
    'placeholder' => 'Name, SKU or barcode (scan, or type + Enter)',
    'branchSelect' => null,
    'onSelect' => null,
    'autofocus' => false,
    'clearOnSelect' => true,
    'wrapperClass' => '',
])

<div class="universal-barcode-search-wrapper position-relative {{ $wrapperClass }}" data-search-id="{{ $id }}">
    <div class="input-group shadow-sm">
        <span class="input-group-text bg-white border-end-0 text-primary">
            <i class="bi bi-upc-scan fs-5"></i>
        </span>
        <input
            type="text"
            id="{{ $id }}"
            name="barcode_query"
            class="form-control form-control-lg border-start-0 border-end-0 py-2 universal-barcode-input"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            spellcheck="false"
            {{ $autofocus ? 'autofocus' : '' }}
        >
        <button class="btn btn-outline-secondary border-start-0 border-end-0 d-none universal-barcode-clear" type="button" title="Clear">
            <i class="bi bi-x-circle"></i>
        </button>
        <span class="input-group-text bg-white universal-barcode-spinner d-none">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </span>
        <span class="input-group-text bg-light text-muted small d-none d-sm-flex align-items-center gap-1">
            <i class="bi bi-lightning-charge text-warning"></i> Scanner Ready
        </span>
    </div>

    {{-- Dropdown Results Menu --}}
    <div class="universal-barcode-results position-absolute w-100 bg-white border shadow-lg rounded-3 mt-1 overflow-hidden d-none"
         style="z-index: 1060; max-height: 380px; overflow-y: auto;">
        <div class="list-group list-group-flush universal-barcode-list">
            {{-- Results injected via JavaScript --}}
        </div>
    </div>
</div>

@pushOnce('css')
<style>
    .universal-barcode-search-wrapper .list-group-item.active-item {
        background-color: rgba(210, 1, 1, 0.08) !important;
        border-color: rgba(210, 1, 1, 0.2) !important;
    }
    .universal-barcode-search-wrapper .list-group-item:hover {
        background-color: #f8fafc;
        cursor: pointer;
    }
    .universal-barcode-input:focus {
        border-color: #d20101 !important;
        box-shadow: none !important;
    }
</style>
@endPushOnce

@pushOnce('js')
<script>
(function() {
    function initBarcodeSearch(wrapper) {
        const input = wrapper.querySelector('.universal-barcode-input');
        const clearBtn = wrapper.querySelector('.universal-barcode-clear');
        const spinner = wrapper.querySelector('.universal-barcode-spinner');
        const resultsBox = wrapper.querySelector('.universal-barcode-results');
        const resultsList = wrapper.querySelector('.universal-barcode-list');
        const onSelectCallback = "{{ $onSelect }}";
        const clearOnSelect = {{ $clearOnSelect ? 'true' : 'false' }};
        const branchSelectSelector = "{{ $branchSelect }}";

        let debounceTimer = null;
        let lastKeyTime = Date.now();
        let barcodeChars = [];
        let activeIndex = -1;
        let currentResults = [];

        function getBranchId() {
            if (branchSelectSelector) {
                const el = document.querySelector(branchSelectSelector);
                if (el) return el.value || '';
            }
            return '';
        }

        function showSpinner(show) {
            if (spinner) spinner.classList.toggle('d-none', !show);
        }

        function showResults(show) {
            if (resultsBox) resultsBox.classList.toggle('d-none', !show);
        }

        function selectProduct(product) {
            if (!product) return;

            // 1. Dispatch custom DOM event
            document.dispatchEvent(new CustomEvent('barcode-product-selected', {
                detail: product,
                bubbles: true
            }));

            // 2. Dispatch jQuery event if present
            if (window.$) {
                $(document).trigger('barcode:selected', [product]);
            }

            // 3. Call named callback if configured
            if (onSelectCallback && typeof window[onSelectCallback] === 'function') {
                window[onSelectCallback](product);
            }

            // 4. Reset input
            if (clearOnSelect) {
                input.value = '';
                clearBtn.classList.add('d-none');
            }
            showResults(false);
            currentResults = [];
            activeIndex = -1;

            // Visual feedback
            input.classList.add('is-valid');
            setTimeout(() => input.classList.remove('is-valid'), 600);
        }

        function renderResults(items) {
            currentResults = items;
            activeIndex = -1;
            resultsList.innerHTML = '';

            if (!items || items.length === 0) {
                resultsList.innerHTML = `
                    <div class="p-3 text-center text-muted">
                        <i class="bi bi-search fs-4 d-block mb-1 text-secondary"></i>
                        No matching product found.
                    </div>
                `;
                showResults(true);
                return;
            }

            items.forEach((item, index) => {
                const row = document.createElement('a');
                row.href = 'javascript:void(0)';
                row.className = 'list-group-item list-group-item-action py-2 px-3 border-bottom';
                row.dataset.index = index;

                const stockBadge = item.track_stock
                    ? `<span class="badge ${item.stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} rounded-pill">Stock: ${item.stock} ${item.unit}</span>`
                    : '<span class="badge bg-secondary-subtle text-secondary rounded-pill">No Stock Limit</span>';

                row.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-bold text-dark fs-6">${item.name}</span>
                            <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                <span><i class="bi bi-upc"></i> ${item.barcode || item.sku}</span>
                                <span>&bull;</span>
                                <span>Brand: ${item.brand}</span>
                                <span>&bull;</span>
                                <span>Cat: ${item.category}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-primary fs-6">৳ ${parseFloat(item.sale_price).toFixed(2)}</span>
                            <div class="mt-1">${stockBadge}</div>
                        </div>
                    </div>
                `;

                row.addEventListener('click', (e) => {
                    e.preventDefault();
                    selectProduct(item);
                });

                resultsList.appendChild(row);
            });

            showResults(true);
        }

        function fetchProducts(query, exact = false) {
            if (!query && !exact) {
                showResults(false);
                return;
            }

            showSpinner(true);
            const branchId = getBranchId();
            const url = new URL('/dashboard/api/barcode-search', window.location.origin);
            url.searchParams.set('q', query);
            if (branchId) url.searchParams.set('branch_id', branchId);
            if (exact) url.searchParams.set('exact', '1');

            fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                showSpinner(false);
                const items = res.data || [];

                // If exact barcode search and matches 1 product, select immediately
                if (exact && items.length === 1) {
                    selectProduct(items[0]);
                } else {
                    renderResults(items);
                }
            })
            .catch(err => {
                showSpinner(false);
                console.error('Barcode search error:', err);
            });
        }

        // Hardware scanner & manual keyboard handling
        input.addEventListener('keydown', function(e) {
            const now = Date.now();
            const diff = now - lastKeyTime;
            lastKeyTime = now;

            // Scanner burst detection (< 40ms per keystroke)
            if (diff < 40) {
                barcodeChars.push(e.key);
            } else {
                barcodeChars = [e.key];
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();

                // If navigating dropdown with arrow keys
                if (activeIndex >= 0 && currentResults[activeIndex]) {
                    selectProduct(currentResults[activeIndex]);
                    return;
                }

                // Barcode scanner or explicit Enter search
                if (query) {
                    fetchProducts(query, true);
                }
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (currentResults.length > 0) {
                    activeIndex = (activeIndex + 1) % currentResults.length;
                    updateActiveItem();
                }
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (currentResults.length > 0) {
                    activeIndex = (activeIndex - 1 + currentResults.length) % currentResults.length;
                    updateActiveItem();
                }
                return;
            }

            if (e.key === 'Escape') {
                showResults(false);
                return;
            }
        });

        function updateActiveItem() {
            const rows = resultsList.querySelectorAll('.list-group-item');
            rows.forEach((r, idx) => {
                r.classList.toggle('active-item', idx === activeIndex);
                if (idx === activeIndex) {
                    r.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        // Debounced manual typing
        input.addEventListener('input', function() {
            const val = this.value.trim();
            clearBtn.classList.toggle('d-none', !val);

            clearTimeout(debounceTimer);
            if (val.length < 1) {
                showResults(false);
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchProducts(val, false);
            }, 250);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                input.value = '';
                this.classList.add('d-none');
                showResults(false);
                input.focus();
            });
        }

        // Close on clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                showResults(false);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.universal-barcode-search-wrapper').forEach(initBarcodeSearch);
    });
})();
</script>
@endPushOnce
