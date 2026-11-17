(() => {
  "use strict";

  const root = document.getElementById("posTerminal");
  if (!root) return;

  const $ = (selector) => document.querySelector(selector);
  const cart = new Map();
  const money = new Intl.NumberFormat("en-BD", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const state = {
    companyId: null,
    branchId: null,
    customerId: null,
    customerName: "",
    customerPhone: "",
    customerDue: 0,
    products: [],
    activeCategory: "",
    activeBrand: "",
    productView: "grid",
    productSearchSequence: 0,
    canDiscount: false,
    canCreateCustomer: false,
    canCollectDue: false,
    busy: false,
    payments: [],
    paymentSequence: 0,
    searchTimer: null,
    customerTimer: null,
    customerSearchSequence: 0,
    lastReceipt: null,
  };
  const urls = {
    context: root.dataset.contextUrl,
    products: root.dataset.productsUrl,
    customers: root.dataset.customersUrl,
    createCustomer: root.dataset.customerCreateUrl,
    checkout: root.dataset.checkoutUrl,
    dueInvoices: root.dataset.dueInvoicesUrl,
    invoicePayment: root.dataset.invoicePaymentUrl,
  };

  function amount(value) {
    return `৳ ${money.format(Number(value) || 0)}`;
  }

  function updateClock() {
    const now = new Date();
    $("#posCurrentDate").textContent = new Intl.DateTimeFormat("en-GB", {
      weekday: "short", day: "2-digit", month: "short", year: "numeric",
    }).format(now);
    $("#posCurrentTime").textContent = new Intl.DateTimeFormat("en-US", {
      hour: "2-digit", minute: "2-digit", second: "2-digit",
    }).format(now);
  }

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;",
    })[char]);
  }

  function showNotice(message, type = "danger") {
    const notice = $("#posNotice");
    notice.className = `pos-notice pos-notice-${type}`;
    notice.textContent = message;
    notice.hidden = false;
    window.scrollTo({ top: 0, behavior: "smooth" });
    window.setTimeout(() => { notice.hidden = true; }, 6000);
  }

  async function request(url, options = {}) {
    const headers = {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(options.body ? { "Content-Type": "application/json" } : {}),
      ...(options.headers || {}),
    };
    const response = await fetch(url, {
      credentials: "same-origin",
      ...options,
      headers: { ...headers, ...(options.headers || {}) },
      body: options.body ? JSON.stringify(options.body) : undefined,
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      const errors = Object.values(result.errors || {}).flat();
      throw new Error(errors[0] || result.message || "The request could not be completed.");
    }
    return result;
  }

  function tenantQuery(extra = {}) {
    const params = new URLSearchParams();
    if (state.companyId) params.set("company_id", state.companyId);
    if (state.branchId) params.set("branch_id", state.branchId);
    Object.entries(extra).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") params.set(key, value);
    });
    return params.toString();
  }

  function selectedCustomer() {
    return state.customerId ? { id: state.customerId, name: state.customerName } : null;
  }

  async function loadContext() {
    const params = new URLSearchParams();
    const companySelection = $("#posCompany");
    const branchSelection = $("#posBranch");
    if (companySelection.value) params.set("company_id", companySelection.value);
    const context = await request(`${urls.context}${params.size ? `?${params}` : ""}`);
    state.companyId = Number(context.company.id);
    state.branchId = Number(context.branch_id);

    if (Array.isArray(context.companies) && context.companies.length) {
      $("#posCompanyField").hidden = false;
      companySelection.innerHTML = context.companies.map((company) =>
        `<option value="${company.id}">${escapeHtml(company.trade_name || company.name)}</option>`
      ).join("");
      companySelection.value = String(context.company.id);
    }
    branchSelection.innerHTML = context.branches.map((branch) =>
      `<option value="${branch.id}">${escapeHtml(branch.name)}${branch.is_head_office ? " · Head office" : ""}</option>`
    ).join("");
    branchSelection.value = String(context.branch_id);
    state.canDiscount = Boolean(context.permissions?.discount);
    state.canCreateCustomer = Boolean(context.permissions?.create_customer);
    state.canCollectDue = Boolean(context.permissions?.collect_due);
    $("#posNewCustomer").hidden = !state.canCreateCustomer;
    $("#posDueButton").hidden = !state.canCollectDue;
    if (!state.canDiscount) {
      $("#posOrderDiscountType").disabled = true;
      $("#posOrderDiscount").disabled = true;
    }
    await loadProducts();
  }

  async function loadProducts(term = "") {
    const sequence = ++state.productSearchSequence;
    const query = tenantQuery({ q: term });
    const result = await request(`${urls.products}?${query}`);
    if (sequence !== state.productSearchSequence || $("#posProductSearch").value.trim() !== term.trim()) return;
    state.products = result.data;
    renderProducts();
  }

  function renderProducts() {
    const grid = $("#posProductGrid");
    const categories = [...new Set(state.products.map((product) => product.category).filter(Boolean))].sort();
    const brands = [...new Set(state.products.map((product) => product.brand).filter(Boolean))].sort();
    const categoryFilter = $("#posCategoryFilter");
    const brandFilter = $("#posBrandFilter");
    const categoryValue = state.activeCategory;
    const brandValue = state.activeBrand;
    categoryFilter.innerHTML = `<option value="">All categories</option>${categories.map((category) =>
      `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`
    ).join("")}`;
    brandFilter.innerHTML = `<option value="">All brands</option>${brands.map((brand) =>
      `<option value="${escapeHtml(brand)}">${escapeHtml(brand)}</option>`
    ).join("")}`;
    if (!categories.includes(categoryValue)) state.activeCategory = "";
    if (!brands.includes(brandValue)) state.activeBrand = "";
    categoryFilter.value = state.activeCategory;
    brandFilter.value = state.activeBrand;
    const products = state.products.filter((product) =>
      (!state.activeCategory || product.category === state.activeCategory)
      && (!state.activeBrand || product.brand === state.activeBrand)
    );
    $("#posResultCount").textContent = `${products.length} ${products.length === 1 ? "product" : "products"}`;
    $("#posProductsEmpty").hidden = products.length > 0;
    $("#posClearProductSearch").hidden = !$("#posProductSearch").value;
    grid.classList.toggle("is-list", state.productView === "list");
    grid.innerHTML = products.map((product) => {
      const outOfStock = product.track_stock && Number(product.stock) <= 0;
      const stockText = product.track_stock ? `${Number(product.stock)} ${escapeHtml(product.unit)} in stock` : "Stock not tracked";
      return `<button type="button" class="pos-product-card${outOfStock ? " is-out-of-stock" : ""}" data-product-id="${product.id}" ${outOfStock ? "disabled" : ""}>
        <span class="pos-product-category">${escapeHtml(product.category || "Uncategorized")}</span>
        <strong>${escapeHtml(product.name)}</strong>
        ${product.brand ? `<span class="pos-product-brand">${escapeHtml(product.brand)}</span>` : ""}
        <span class="pos-product-sku">${escapeHtml(product.sku)}${product.barcode ? ` · ${escapeHtml(product.barcode)}` : ""}</span>
        <span class="pos-product-bottom"><b>${amount(product.sale_price)}</b><small>${stockText}</small></span>
      </button>`;
    }).join("");
  }

  function searchProducts(term) {
    $("#posClearProductSearch").hidden = !term;
    window.clearTimeout(state.searchTimer);
    state.searchTimer = window.setTimeout(async () => {
      try {
        await loadProducts(term.trim());
        if ($("#posProductSearch").value.trim() !== term.trim()) return;
        const exact = state.products.find((product) => product.barcode === term.trim() || product.sku === term.trim());
        if (exact && term.trim()) {
          addProduct(exact);
          $("#posProductSearch").value = "";
          await loadProducts();
        }
      } catch (error) {
        showNotice(error.message);
      }
    }, 180);
  }

  function addProduct(product) {
    const existing = cart.get(Number(product.id));
    const quantity = existing ? Number(existing.quantity) + 1 : 1;
    if (product.track_stock && quantity > Number(product.stock)) {
      showNotice(`Only ${product.stock} ${product.unit} of ${product.name} are available.`);
      return;
    }
    if (existing) existing.quantity = quantity;
    else cart.set(Number(product.id), { ...product, quantity: 1, discountType: "flat", discountValue: 0 });
    renderCart();
  }

  function lineAmounts(item) {
    const base = Math.round(Number(item.sale_price) * Number(item.quantity) * 100) / 100;
    const discount = item.discountType === "percent"
      ? Math.round(base * Number(item.discountValue) / 100 * 100) / 100
      : Math.min(base, Number(item.discountValue) || 0);
    return { base, discount, total: Math.max(0, Math.round((base - discount) * 100) / 100) };
  }

  function totals() {
    const values = [...cart.values()].map(lineAmounts);
    const subtotal = values.reduce((sum, line) => sum + line.base, 0);
    const lineDiscount = values.reduce((sum, line) => sum + line.discount, 0);
    const discountedSubtotal = Math.max(0, subtotal - lineDiscount);
    const discountValue = Number($("#posOrderDiscount").value) || 0;
    const orderDiscount = $("#posOrderDiscountType").value === "percent"
      ? Math.round(discountedSubtotal * discountValue / 100 * 100) / 100
      : Math.min(discountedSubtotal, discountValue);
    const grandTotal = Math.max(0, Math.round((discountedSubtotal - orderDiscount) * 100) / 100);
    const tendered = state.payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0);
    const paid = Math.min(tendered, grandTotal);
    return { subtotal, lineDiscount, orderDiscount, grandTotal, paid, due: Math.max(0, grandTotal - paid), change: Math.max(0, tendered - grandTotal) };
  }

  function renderCart() {
    const items = [...cart.values()];
    if (!items.length) {
      state.payments = [];
      renderPaymentList();
    }
    const target = $("#posCartItems");
    const quantity = items.reduce((count, item) => count + Number(item.quantity), 0);
    const itemCount = `${quantity} ${quantity === 1 ? "item" : "items"}`;
    $("#posCartCount").textContent = itemCount;
    $("#posCartCountBadge").textContent = String(quantity);
    $("#posSummaryItemCount").textContent = itemCount;
    $("#posSelectedCustomerLabel").textContent = state.customerId ? state.customerName : "Walk-in customer";
    $("#posCustomerDueSummary").textContent = amount(state.customerDue);
    $("#posClearCart").disabled = items.length === 0;
    $("#posCheckout").disabled = items.length === 0 || state.busy;
    if (!items.length) {
      target.classList.add("is-empty");
      target.innerHTML = `<div class="pos-cart-empty"><i class="bi bi-basket2"></i><strong>Your cart is empty</strong><span>Select a product or scan its barcode.</span></div>`;
    } else {
      target.classList.remove("is-empty");
      target.innerHTML = items.map((item) => {
        const line = lineAmounts(item);
        return `<div class="pos-cart-row" data-cart-id="${item.id}">
          <div class="pos-cart-product"><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.sku)} · ${amount(item.sale_price)} / ${escapeHtml(item.unit)}</small></div>
          <input class="form-control form-control-sm pos-cart-qty" type="number" min="0.001" step="0.001" value="${item.quantity}" aria-label="Quantity for ${escapeHtml(item.name)}">
          <div class="pos-line-discount">
            <select class="form-select form-select-sm pos-line-discount-type" aria-label="Discount type">
              <option value="flat" ${item.discountType === "flat" ? "selected" : ""}>৳</option>
              <option value="percent" ${item.discountType === "percent" ? "selected" : ""}>%</option>
            </select>
            <input class="form-control form-control-sm pos-line-discount-value" type="number" min="0" step="0.01" value="${item.discountValue}" aria-label="Line discount" ${state.canDiscount ? "" : "disabled"}>
          </div>
          <strong class="pos-line-total">${amount(line.total)}</strong>
          <button type="button" class="pos-remove-line" aria-label="Remove ${escapeHtml(item.name)}"><i class="bi bi-x-lg"></i></button>
        </div>`;
      }).join("");
    }
    updateTotals();
  }

  function updateTotals() {
    let remainingNonCash = totals().grandTotal;
    state.payments.forEach((payment) => {
      if (payment.method === "cash") return;
      const adjustedAmount = Math.min(Number(payment.amount) || 0, remainingNonCash);
      remainingNonCash = Math.max(0, remainingNonCash - adjustedAmount);
      if (adjustedAmount !== Number(payment.amount)) {
        payment.amount = adjustedAmount;
        const input = document.querySelector(`.pos-payment-amount[data-payment-id="${payment.id}"]`);
        if (input) input.value = adjustedAmount.toFixed(2);
      }
    });
    const summary = totals();
    const walkIn = !state.customerId;
    $("#posDueHint").textContent = walkIn ? "Walk-in · full payment" : "Partial payment available";
    $("#posDueHint").classList.toggle("is-partial", !walkIn);
    $("#posSubtotal").textContent = amount(summary.subtotal);
    $("#posLineDiscount").textContent = `− ${amount(summary.lineDiscount)}`;
    $("#posGrandTotal").textContent = amount(summary.grandTotal);
    $("#posPaymentTotal").textContent = amount(summary.paid);
    $("#posRemainingDue").textContent = amount(summary.due);
    $("#posChange").textContent = amount(summary.change);
    $("#posChangeWrap").hidden = summary.change <= 0;
    $("#posCheckout").disabled = cart.size === 0 || summary.grandTotal <= 0 || state.busy || (walkIn && summary.due > 0);
    document.querySelectorAll("[data-payment-method]").forEach((button) => {
      const alreadyAdded = state.payments.some((payment) => payment.method === button.dataset.paymentMethod);
      button.disabled = cart.size === 0 || alreadyAdded || summary.due <= 0;
    });
    document.querySelectorAll(".pos-payment-amount").forEach((input) => {
      const payment = state.payments.find((entry) => entry.id === Number(input.dataset.paymentId));
      const otherNonCashPayments = state.payments
        .filter((entry) => entry !== payment && entry.method !== "cash")
        .reduce((sum, entry) => sum + (Number(entry.amount) || 0), 0);
      input.max = payment?.method === "cash"
        ? "999999999"
        : Math.max(0, summary.grandTotal - otherNonCashPayments).toFixed(2);
      input.readOnly = walkIn;
    });
  }

  function renderPaymentList() {
    const list = $("#posPaymentList");
    if (!state.payments.length) {
      list.innerHTML = `<span class="pos-payment-empty">Choose a method above to add a payment.</span>`;
      return;
    }
    const labels = { cash: "Cash", card: "Card", mobile_banking: "Mobile" };
    list.innerHTML = state.payments.map((payment) => `
      <div class="pos-payment-line" data-payment-id="${payment.id}">
        <strong>${labels[payment.method]}</strong>
        <input class="form-control form-control-sm pos-payment-amount" type="number" min="0" step="0.01"
          value="${Number(payment.amount).toFixed(2)}" data-payment-id="${payment.id}" aria-label="${labels[payment.method]} payment amount">
        <input class="form-control form-control-sm pos-payment-reference" type="text" maxlength="100"
          value="${escapeHtml(payment.reference || "")}" data-payment-reference-id="${payment.id}"
          placeholder="Reference (optional)" aria-label="${labels[payment.method]} payment reference">
        <button class="pos-remove-payment" type="button" data-remove-payment="${payment.id}" aria-label="Remove ${labels[payment.method]} payment">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>`).join("");
    updateTotals();
  }

  function addPaymentMethod(method) {
    if (state.payments.some((payment) => payment.method === method)) return;
    const summary = totals();
    if (summary.due <= 0) return;
    state.payments.push({
      id: ++state.paymentSequence,
      method,
      amount: summary.due,
      reference: "",
    });
    renderPaymentList();
    $("#posPaymentList").querySelector(`.pos-payment-amount[data-payment-id="${state.paymentSequence}"]`).focus();
  }

  function resetCustomer() {
    state.customerSearchSequence += 1;
    state.customerId = null;
    state.customerName = "";
    state.customerPhone = "";
    state.customerDue = 0;
    state.payments = [];
    renderPaymentList();
    $("#posCustomerSearch").value = "";
    $("#posCustomerResults").hidden = true;
    updateCustomerSelection();
  }

  async function searchCustomers(term) {
    const sequence = ++state.customerSearchSequence;
    const searchTerm = term.trim();
    const result = await request(`${urls.customers}?${tenantQuery({ q: term })}`);
    if (sequence !== state.customerSearchSequence || $("#posCustomerSearch").value.trim() !== searchTerm) return;
    const results = $("#posCustomerResults");
    results.innerHTML = `<button type="button" class="pos-walk-in-option" data-walk-in="true">
        <strong><i class="bi bi-person-walking me-1"></i>Walk-in customer</strong>
        <span>No credit or invoice due</span>
      </button>
      ${result.data.map((customer) =>
        `<button type="button" data-customer-id="${customer.id}" data-name="${escapeHtml(customer.name)}" data-phone="${escapeHtml(customer.phone || "")}" data-due="${Number(customer.outstanding_due) || 0}">
        <strong>${escapeHtml(customer.name)}</strong><span>${escapeHtml(customer.phone || customer.email || "")}</span>
        ${Number(customer.outstanding_due) > 0 ? `<em>Invoice due ${amount(customer.outstanding_due)}</em>` : ""}
      </button>`
      ).join("") || `<div class="pos-customer-no-results">No registered customers found.</div>`}`;
    results.hidden = false;
  }

  function updateCustomerSelection(preserveSearch = false) {
    const searchTerm = $("#posCustomerSearch").value;
    if (!preserveSearch) {
      $("#posCustomerSearch").value = state.customerId
        ? `${state.customerName}${state.customerPhone ? ` · ${state.customerPhone}` : ""}`
        : "";
    } else {
      $("#posCustomerSearch").value = searchTerm;
    }
    $("#posCustomerResults").hidden = true;
    $("#posCustomerDue").hidden = !state.customerId;
    $("#posCustomerDue").textContent = state.customerId
      ? `Outstanding invoice balance: ${amount(state.customerDue)} · Not applied to this sale`
      : "";
    $("#posDueButton").disabled = !state.customerId || state.customerDue <= 0 || !state.canCollectDue;
    renderCart();
  }

  function chooseCustomer(customer) {
    const nextCustomerId = customer ? Number(customer.id) : null;
    if (state.customerId !== nextCustomerId) state.payments = [];
    state.customerId = nextCustomerId;
    state.customerName = customer?.name || "";
    state.customerPhone = customer?.phone || "";
    state.customerDue = Number(customer?.outstanding_due ?? customer?.due ?? 0);
    renderPaymentList();
    updateCustomerSelection();
  }

  async function addScannedProduct(code) {
    const barcode = code.trim();
    if (!barcode) return;
    try {
      const result = await request(`${urls.products}?${tenantQuery({ q: barcode })}`);
      const product = result.data.find((item) => item.barcode === barcode || item.sku === barcode);
      if (!product) {
        showNotice(`No product found for barcode or SKU "${barcode}".`);
        return;
      }
      addProduct(product);
      $("#posBarcodeSearch").value = "";
      $("#posBarcodeSearch").focus();
    } catch (error) {
      showNotice(error.message);
    }
  }

  function openModal(id) {
    $(id).hidden = false;
    document.body.classList.add("pos-modal-open");
  }

  function closeModal(id) {
    $(id).hidden = true;
    if (![...document.querySelectorAll(".pos-modal-backdrop")].some((modal) => !modal.hidden)) {
      document.body.classList.remove("pos-modal-open");
    }
  }

  async function openDueInvoices() {
    const list = $("#posDueInvoices");
    list.innerHTML = `<div class="pos-due-empty">Loading invoices…</div>`;
    $("#posDuePaymentForm").hidden = true;
    openModal("#posDueModal");
    try {
      const result = await request(`${urls.dueInvoices}?${tenantQuery({ customer_id: state.customerId })}`);
      if (!result.data.length) {
        list.innerHTML = `<div class="pos-due-empty">No unpaid invoices for this customer.</div>`;
        return;
      }
      list.innerHTML = result.data.map((invoice) =>
        `<button type="button" data-sale-id="${invoice.id}" data-due="${invoice.due_total}">
          <span><strong>${escapeHtml(invoice.invoice_number)}</strong><small>${new Date(invoice.sold_at).toLocaleDateString()}</small></span>
          <b>${amount(invoice.due_total)} due</b>
        </button>`
      ).join("");
    } catch (error) {
      list.innerHTML = `<div class="pos-due-empty">${escapeHtml(error.message)}</div>`;
    }
  }

  function renderReceipt(data) {
    const sale = data.sale;
    state.lastReceipt = { sale, items: data.items, payments: data.payments, change: data.change };
    const paymentLabels = { cash: "Cash", card: "Card", mobile_banking: "Mobile" };
    $("#posReceiptInvoice").textContent = `${sale.invoice_number} · ${sale.customer_name}`;
    $("#posReceiptTotals").innerHTML = `
      <div><span>Total</span><strong>${amount(sale.grand_total)}</strong></div>
      <div><span>Paid</span><strong>${amount(sale.paid_total)}</strong></div>
      <div><span>Due on this invoice</span><strong>${amount(sale.due_total)}</strong></div>
      ${Number(data.change) > 0 ? `<div><span>Change</span><strong>${amount(data.change)}</strong></div>` : ""}
      ${data.payments.map((payment) => `<div><span>${paymentLabels[payment.method]}</span><strong>${amount(payment.amount)}</strong></div>`).join("")}
      <div class="pos-receipt-items">${data.items.map((item) => `<span>${escapeHtml(item.product_name)} × ${item.quantity}<b>${amount(item.line_total)}</b></span>`).join("")}</div>`;
    openModal("#posReceiptModal");
  }

  async function submitCheckout() {
    if (state.busy || cart.size === 0) return;
    const summary = totals();
    if (!state.customerId && summary.due > 0) {
      showNotice("Walk-in sales must be paid in full.");
      return;
    }
    state.busy = true;
    renderCart();
    try {
      const result = await request(urls.checkout, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
        body: {
          company_id: state.companyId,
          branch_id: state.branchId,
          customer_id: state.customerId,
          items: [...cart.values()].map((item) => ({
            product_id: item.id,
            quantity: item.quantity,
            discount_type: item.discountType,
            discount_value: item.discountValue,
          })),
          order_discount_type: $("#posOrderDiscountType").value,
          order_discount_value: $("#posOrderDiscount").value || 0,
          payments: state.payments.map((payment) => ({
            amount: Number(payment.amount) || 0,
            method: payment.method,
            reference: payment.reference || null,
          })),
        },
      });
      cart.clear();
      state.payments = [];
      $("#posOrderDiscount").value = 0;
      renderCart();
      await loadProducts($("#posProductSearch").value);
      if (state.customerId) {
        const result = await request(`${urls.customers}?${tenantQuery({ q: state.customerName })}`);
        const updatedCustomer = result.data.find((customer) => Number(customer.id) === state.customerId);
        if (updatedCustomer) chooseCustomer(updatedCustomer);
      }
      renderReceipt(result.data);
    } catch (error) {
      showNotice(error.message);
    } finally {
      state.busy = false;
      renderCart();
    }
  }

  function bindEvents() {
    $("#posProductSearch").addEventListener("input", (event) => searchProducts(event.target.value));
    $("#posProductSearch").addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        window.clearTimeout(state.searchTimer);
        const term = event.target.value.trim();
        const exact = state.products.find((product) => product.barcode === term || product.sku === term);
        if (exact) {
          addProduct(exact);
          event.target.value = "";
          loadProducts().catch((error) => showNotice(error.message));
        } else {
          searchProducts(term);
        }
      }
    });
    $("#posClearProductSearch").addEventListener("click", () => {
      $("#posProductSearch").value = "";
      $("#posProductSearch").focus();
      loadProducts().catch((error) => showNotice(error.message));
    });
    $("#posCategoryFilter").addEventListener("change", (event) => {
      state.activeCategory = event.target.value;
      renderProducts();
    });
    $("#posBrandFilter").addEventListener("change", (event) => {
      state.activeBrand = event.target.value;
      renderProducts();
    });
    $("#posGridView").addEventListener("click", () => {
      state.productView = "grid";
      $("#posGridView").classList.add("active");
      $("#posGridView").setAttribute("aria-pressed", "true");
      $("#posListView").classList.remove("active");
      $("#posListView").setAttribute("aria-pressed", "false");
      $("#posProductGrid").classList.remove("is-list");
    });
    $("#posListView").addEventListener("click", () => {
      state.productView = "list";
      $("#posListView").classList.add("active");
      $("#posListView").setAttribute("aria-pressed", "true");
      $("#posGridView").classList.remove("active");
      $("#posGridView").setAttribute("aria-pressed", "false");
      $("#posProductGrid").classList.add("is-list");
    });
    $("#posBarcodeSearch").addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        addScannedProduct(event.currentTarget.value);
      }
    });

    $("#posProductGrid").addEventListener("click", (event) => {
      const button = event.target.closest("[data-product-id]");
      if (!button) return;
      const product = state.products.find((item) => Number(item.id) === Number(button.dataset.productId));
      if (product) addProduct(product);
    });
    $("#posCartItems").addEventListener("click", (event) => {
      const row = event.target.closest("[data-cart-id]");
      if (row && event.target.closest(".pos-remove-line")) {
        cart.delete(Number(row.dataset.cartId));
        renderCart();
      }
    });
    $("#posCartItems").addEventListener("input", (event) => {
      const row = event.target.closest("[data-cart-id]");
      if (!row) return;
      const item = cart.get(Number(row.dataset.cartId));
      if (event.target.classList.contains("pos-cart-qty")) {
        const quantity = Number(event.target.value);
        if (quantity > 0 && (!item.track_stock || quantity <= Number(item.stock))) {
          item.quantity = Math.round(quantity * 1000) / 1000;
        } else if (quantity > Number(item.stock) && item.track_stock) {
          event.target.setCustomValidity(`Only ${item.stock} in stock.`);
          event.target.reportValidity();
          return;
        }
        renderCart();
      } else if (event.target.classList.contains("pos-line-discount-value")) {
        item.discountValue = Number(event.target.value) || 0;
        const total = row.querySelector(".pos-line-total");
        total.textContent = amount(lineAmounts(item).total);
        updateTotals();
      }
    });
    $("#posCartItems").addEventListener("change", (event) => {
      const row = event.target.closest("[data-cart-id]");
      if (row && event.target.classList.contains("pos-line-discount-type")) {
        cart.get(Number(row.dataset.cartId)).discountType = event.target.value;
        renderCart();
      }
    });

    $("#posCustomerSearch").addEventListener("focus", (event) => {
      searchCustomers(event.target.value.trim()).catch((error) => showNotice(error.message));
    });
    $("#posCustomerSearch").addEventListener("input", (event) => {
      state.customerId = null;
      state.customerName = "";
      state.customerPhone = "";
      state.customerDue = 0;
      state.payments = [];
      renderPaymentList();
      const term = event.target.value;
      updateCustomerSelection(true);
      window.clearTimeout(state.customerTimer);
      state.customerTimer = window.setTimeout(() => {
        searchCustomers(term).catch((error) => showNotice(error.message));
      }, 250);
    });
    $("#posCustomerResults").addEventListener("click", (event) => {
      if (event.target.closest("[data-walk-in]")) {
        chooseCustomer(null);
        return;
      }
      const button = event.target.closest("[data-customer-id]");
      if (!button) return;
      chooseCustomer({
        id: button.dataset.customerId,
        name: button.dataset.name,
        phone: button.dataset.phone,
        due: button.dataset.due,
      });
    });
    document.addEventListener("click", (event) => {
      if (!event.target.closest(".pos-customer-search")) $("#posCustomerResults").hidden = true;
    });
    $("#posNewCustomer").addEventListener("click", () => openModal("#posCustomerModal"));
    $("#posCustomerForm").addEventListener("submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const values = Object.fromEntries(new FormData(form).entries());
      try {
        const result = await request(urls.createCustomer, {
          method: "POST",
          headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
          body: { ...values, company_id: state.companyId },
        });
        chooseCustomer(result.data);
        form.reset();
        closeModal("#posCustomerModal");
        showNotice("Customer added to this store.", "success");
      } catch (error) {
        showNotice(error.message);
      }
    });

    $("#posOrderDiscount").addEventListener("input", updateTotals);
    $("#posOrderDiscountType").addEventListener("change", updateTotals);
    document.querySelectorAll("[data-payment-method]").forEach((button) => {
      button.addEventListener("click", () => addPaymentMethod(button.dataset.paymentMethod));
    });
    $("#posPaymentList").addEventListener("input", (event) => {
      const paymentId = Number(event.target.dataset.paymentId || event.target.dataset.paymentReferenceId);
      const payment = state.payments.find((entry) => entry.id === paymentId);
      if (!payment) return;
      if (event.target.classList.contains("pos-payment-amount")) {
        payment.amount = Math.max(0, Number(event.target.value) || 0);
        updateTotals();
      } else if (event.target.classList.contains("pos-payment-reference")) {
        payment.reference = event.target.value;
      }
    });
    $("#posPaymentList").addEventListener("click", (event) => {
      const removeButton = event.target.closest("[data-remove-payment]");
      if (!removeButton) return;
      state.payments = state.payments.filter((payment) => payment.id !== Number(removeButton.dataset.removePayment));
      renderPaymentList();
    });
    $("#posCheckout").addEventListener("click", submitCheckout);
    $("#posClearCart").addEventListener("click", () => {
      cart.clear();
      state.payments = [];
      renderPaymentList();
      renderCart();
      $("#posProductSearch").focus();
    });
    $("#posWalkInCustomer").addEventListener("click", () => {
      chooseCustomer(null);
      $("#posBarcodeSearch").focus();
    });
    $("#posRefreshCatalog").addEventListener("click", async () => {
      try {
        await loadProducts($("#posProductSearch").value.trim());
        showNotice("Products refreshed.", "success");
      } catch (error) {
        showNotice(error.message);
      }
    });
    $("#posCompany").addEventListener("change", async (event) => {
      if (cart.size && !window.confirm("Changing store clears the current sale. Continue?")) {
        event.target.value = String(state.companyId);
        return;
      }
      cart.clear();
      state.productSearchSequence += 1;
      resetCustomer();
      try {
        await loadContext();
        renderCart();
      } catch (error) {
        event.target.value = String(state.companyId);
        showNotice(error.message);
      }
    });
    $("#posBranch").addEventListener("change", async (event) => {
      if (cart.size && !window.confirm("Changing branch clears the current sale. Continue?")) {
        event.target.value = String(state.branchId);
        return;
      }
      cart.clear();
      state.productSearchSequence += 1;
      state.branchId = Number(event.target.value);
      try { await loadProducts(); renderCart(); } catch (error) { showNotice(error.message); }
    });
    $("#posDueButton").addEventListener("click", openDueInvoices);
    $("#posDueInvoices").addEventListener("click", (event) => {
      const invoice = event.target.closest("[data-sale-id]");
      if (!invoice) return;
      const form = $("#posDuePaymentForm");
      form.hidden = false;
      form.elements.sale_id.value = invoice.dataset.saleId;
      form.elements.amount.max = invoice.dataset.due;
      form.elements.amount.value = Number(invoice.dataset.due).toFixed(2);
    });
    $("#posDuePaymentForm").addEventListener("submit", async (event) => {
      event.preventDefault();
      const form = event.currentTarget;
      const saleId = form.elements.sale_id.value;
      try {
        await request(urls.invoicePayment.replace("__SALE__", saleId), {
          method: "POST",
          headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
          body: {
            company_id: state.companyId,
            amount: form.elements.amount.value,
            method: form.elements.method.value,
            reference: form.elements.reference.value,
          },
        });
        closeModal("#posDueModal");
        const result = await request(`${urls.customers}?${tenantQuery({ q: state.customerName })}`);
        const updatedCustomer = result.data.find((customer) => Number(customer.id) === state.customerId);
        if (updatedCustomer) chooseCustomer(updatedCustomer);
        showNotice("Payment recorded on the selected invoice.", "success");
      } catch (error) {
        showNotice(error.message);
      }
    });

    document.querySelectorAll("[data-pos-close]").forEach((button) => {
      button.addEventListener("click", () => closeModal(`#pos${button.dataset.posClose[0].toUpperCase()}${button.dataset.posClose.slice(1)}Modal`));
    });
    document.querySelectorAll(".pos-modal-backdrop").forEach((backdrop) => {
      backdrop.addEventListener("click", (event) => {
        if (event.target === backdrop) closeModal(`#${backdrop.id}`);
      });
    });
    $("#posNewSale").addEventListener("click", () => {
      closeModal("#posReceiptModal");
      $("#posProductSearch").focus();
    });
    $("#posPrintReceipt").addEventListener("click", () => window.print());
  }

  bindEvents();
  updateClock();
  window.setInterval(updateClock, 1000);
  renderCart();
  loadContext().catch((error) => showNotice(error.message));
})();
