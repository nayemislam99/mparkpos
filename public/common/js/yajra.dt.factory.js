(function () {
  "use strict";

  function parseAttribute($table, attribute, fallback) {
    try {
      return JSON.parse($table.attr(attribute) || JSON.stringify(fallback));
    } catch (error) {
      console.error(`Invalid ${attribute} on DataTable`, error);
      return null;
    }
  }

  function collectFilters(scope) {
    const $scope = scope ? $(scope) : $(document);
    const filters = {};

    $scope.find(".dt-filter").each(function () {
      const $input = $(this);
      const key = $input.data("filter-key") || $input.attr("name");
      if (key) filters[key] = $input.val();
    });

    return filters;
  }

  function selectedIds($table) {
    return $table.data("dt-selected-ids") || [];
  }

  function saveSelectedIds($table, ids) {
    $table.data("dt-selected-ids", [...new Set(ids.map(String))]);
    $table.closest(".dataTables_wrapper").find(".js-dt-bulk-action").prop("disabled", ids.length === 0);
  }

  function exportUrl(key, option, filters, search, ids) {
    const base = (window.DT_BASE || "/dashboard/dt").replace(/\/$/, "");
    const language = option.lang ? `/${encodeURIComponent(option.lang)}` : "";
    const url = `${base}/${encodeURIComponent(key)}/export/${encodeURIComponent(option.format)}${language}`;
    const query = $.param({ filters, search: { value: search || "" }, ids });

    return query ? `${url}?${query}` : url;
  }

  function renderToolbar($table, dataTable) {
    const $toolbar = $table.closest(".dataTables_wrapper").find(".dt-toolbar-exports");
    if (!$toolbar.length) return;

    // Client request: hide/disable default exports across all tables (custom PDF/Excel will be implemented if needed)
    /*
    const key = $table.data("dt-key");
    const exports = parseAttribute($table, "data-dt-exports", []);
    if (key && Array.isArray(exports) && exports.length && !$toolbar.find(".dt-export-group").length) {
      const $group = $('<span class="dt-export-group d-inline-flex flex-wrap gap-1"></span>');

      exports.forEach((option) => {
        const $button = $('<button type="button" class="btn btn-sm btn-outline-secondary"></button>')
          .text(option.label || String(option.format).toUpperCase())
          .on("click", function () {
            const scope = $table.data("dt-filter-scope") || null;
            window.location.assign(exportUrl(
              key,
              option,
              collectFilters(scope),
              dataTable.search(),
              selectedIds($table),
            ));
          });
        $group.append($button);
      });

      $toolbar.append($group);
    }
    */

    const bulkActions = parseAttribute($table, "data-dt-bulk-actions", []);
    if (Array.isArray(bulkActions) && bulkActions.length && !$toolbar.find(".dt-bulk-group").length) {
      const $group = $('<span class="dt-bulk-group d-inline-flex flex-wrap gap-1"></span>');

      bulkActions.forEach((action) => {
        const $button = $('<button type="button" class="btn btn-sm btn-outline-danger js-dt-bulk-action" disabled></button>')
          .text(action.label || "Apply")
          .data("action", action);
        $group.append($button);
      });

      $toolbar.append($group);
    }
  }

  function initTable($table) {
    const key = $table.data("dt-key");
    const columns = parseAttribute($table, "data-dt-columns", []);
    const order = parseAttribute($table, "data-dt-order", [[0, "desc"]]);

    if (!key || !Array.isArray(columns) || columns.length === 0 || !Array.isArray(order)) {
      console.error("A DataTable needs a key and valid columns/order configuration.", $table[0]);
      return;
    }

    if (!$.fn.DataTable) {
      console.error("DataTables is not loaded.");
      return;
    }

    const selectable = $table.attr("data-dt-selectable") === "true";
    const responsiveControl = $table.attr("data-dt-responsive-control") === "true";
    const idField = $table.attr("data-dt-id-field") || "id";
    const htmlColumns = parseAttribute($table, "data-dt-html-columns", []);
    const escapeHtml = (value) => $("<div>").text(String(value)).html();
    const tableColumns = [
      ...(responsiveControl ? [{
          data: null,
          title: "",
          className: "dtr-control",
          orderable: false,
          searchable: false,
          responsivePriority: 1,
          defaultContent: "",
        }] : []),
      ...(selectable ? [{
          data: null,
          title: "",
          orderable: false,
          searchable: false,
          responsivePriority: 1,
          render: function (_data, _type, row) {
            const id = escapeHtml(row[idField] ?? "");
            const checked = selectedIds($table).includes(String(row[idField])) ? " checked" : "";
            return `<input type="checkbox" class="form-check-input dt-select-row" value="${id}"${checked} aria-label="Select row">`;
          },
        }] : []),
      ...columns,
    ];

    if (!Array.isArray(htmlColumns)) {
      console.error("data-dt-html-columns must contain a JSON array.", $table[0]);
      return;
    }

    tableColumns.forEach((column) => {
      if (typeof column.render === "string") {
        if (typeof window[column.render] === "function") {
          column.render = window[column.render];
        } else {
          delete column.render;
        }
      }
      if (column.data !== null && !htmlColumns.includes(column.data) && typeof column.render !== "function") {
        column.render = $.fn.dataTable.render.text();
      }
    });

    const $head = $("<thead><tr></tr></thead>");
    if (responsiveControl) $head.find("tr").append("<th></th>");
    if (selectable) $head.find("tr").append('<th><input type="checkbox" class="form-check-input dt-select-all" aria-label="Select visible rows"></th>');
    columns.forEach((column) => {
      $("<th>").text(column.title || column.data).appendTo($head.find("tr"));
    });
    $table.find("thead").remove();
    $table.prepend($head);

    const filterScope = $table.data("dt-filter-scope") || null;
    const base = (window.DT_BASE || "/dashboard/dt").replace(/\/$/, "");
    const isBn = ($("html").attr("lang") || "").toLowerCase().startsWith("bn");
    const dtLanguage = isBn ? {
      search: "খুঁজুন:",
      searchPlaceholder: "অনুসন্ধান করুন...",
      lengthMenu: "প্রদর্শন _MENU_ টি",
      info: "_TOTAL_ টির মধ্যে _START_ থেকে _END_ দেখানো হচ্ছে",
      infoEmpty: "০ টির মধ্যে ০ দেখানো হচ্ছে",
      infoFiltered: "(মোট _MAX_ টি থেকে ফিল্টার করা)",
      zeroRecords: "কোনো তথ্য পাওয়া যায়নি",
      emptyTable: "টেবিলে কোনো তথ্য নেই",
      paginate: {
        first: "প্রথম",
        previous: "পূর্ববর্তী",
        next: "পরবর্তী",
        last: "শেষ"
      }
    } : {
      search: "Search:",
      searchPlaceholder: "Search...",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_ entries",
      infoEmpty: "Showing 0 to 0 of 0 entries",
      infoFiltered: "(filtered from _MAX_ total entries)",
      zeroRecords: "No matching records found",
      emptyTable: "No data available in table",
      paginate: {
        first: "First",
        previous: "Previous",
        next: "Next",
        last: "Last"
      }
    };

    const dataTable = $table.DataTable({
      processing: true,
      serverSide: true,
      responsive: responsiveControl
        ? { details: { type: "column", target: 0 } }
        : true,
      language: dtLanguage,
      dom: '<"dt-table-toolbar"<"dt-table-length"l><"dt-toolbar-exports"><"dt-table-search"f>>rt<"dt-table-footer"<"dt-table-info"i><"dt-table-pagination"p>>',
      pageLength: Number($table.attr("data-page-length")) || 25,
      lengthMenu: [10, 25, 50, 100],
      order,
      autoWidth: false,
      ajax: {
        url: `${base}/${encodeURIComponent(key)}`,
        type: "GET",
        data: function (request) {
          request.filters = collectFilters(filterScope);
        },
        error: function (xhr) {
          console.error("DataTable request failed:", xhr.responseText || xhr.statusText);
        },
      },
      columns: tableColumns,
      initComplete: function () {
        renderToolbar($table, this.api());
      },
      drawCallback: function () {
        if (!selectable) return;

        const ids = new Set(selectedIds($table).map(String));
        let visibleCount = 0;
        let selectedCount = 0;
        this.api().rows({ page: "current" }).every(function () {
          const id = String(this.data()[idField] ?? "");
          visibleCount++;
          if (ids.has(id)) selectedCount++;
          $(this.node()).find(".dt-select-row").prop("checked", ids.has(id));
        });

        const selectAll = this.api().table().header().querySelector(".dt-select-all");
        if (selectAll) {
          selectAll.checked = visibleCount > 0 && selectedCount === visibleCount;
          selectAll.indeterminate = selectedCount > 0 && selectedCount < visibleCount;
        }
      },
    });

    const $filterRoot = filterScope ? $(filterScope) : $table.closest(".dt-scope");
    $filterRoot
      .off("click.dtTable")
      .on("click.dtTable", ".js-dt-apply", function () {
        dataTable.ajax.reload();
      })
      .on("click.dtTable", ".js-dt-reset", function () {
        $filterRoot.find(".dt-filter").val("").trigger("change");
        dataTable.ajax.reload();
      });

    $table.data("dt-instance", dataTable);

    if (selectable) {
      $table
        .off("change.dtSelect")
        .on("change.dtSelect", ".dt-select-row", function () {
          const ids = new Set(selectedIds($table).map(String));
          if (this.checked) ids.add(String(this.value));
          else ids.delete(String(this.value));
          saveSelectedIds($table, [...ids]);
        })
        .on("change.dtSelect", ".dt-select-all", function () {
          const ids = new Set(selectedIds($table).map(String));
          const checked = this.checked;
          dataTable.rows({ page: "current" }).every(function () {
            const id = String(this.data()[idField] ?? "");
            if (checked) ids.add(id);
            else ids.delete(id);
          });
          saveSelectedIds($table, [...ids]);
          dataTable.draw(false);
        });
    }
  }

  function confirmAction(title, callback) {
    if (window.Swal) {
      window.Swal.fire({
        title,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Continue",
      }).then((result) => {
        if (result.isConfirmed) callback();
      });
    } else if (window.confirm(title)) {
      callback();
    }
  }

  function sendAction(url, method, data, onSuccess) {
    $.ajax({
      url,
      type: method,
      data: { ...data, _token: window.csrf_token },
    }).done(onSuccess).fail(function (xhr) {
      const message = xhr.responseJSON?.message || "The action could not be completed.";
      if (window.Swal) window.Swal.fire({ icon: "error", title: "Action failed", text: message });
      else console.error(message);
    });
  }

  function reloadTable($table) {
    const instance = $table.data("dt-instance");
    if (instance) instance.ajax.reload(null, false);
  }

  $(function () {
    $(".js-yajra-dt").each(function () {
      initTable($(this));
    });

    $(document).on("click", ".js-dt-action", function () {
      const $button = $(this);
      confirmAction($button.data("confirm") || "Are you sure?", function () {
        sendAction($button.data("url"), $button.data("method") || "POST", {}, function () {
          reloadTable($button.closest(".dataTables_wrapper").find(".js-yajra-dt"));
        });
      });
    });

    $(document).on("click", ".js-dt-bulk-action", function () {
      const $button = $(this);
      const $table = $button.closest(".dataTables_wrapper").find(".js-yajra-dt");
      const action = $button.data("action");
      const ids = selectedIds($table);

      if (!action || !action.url || ids.length === 0) return;

      confirmAction(action.confirm || "Apply this action to the selected rows?", function () {
        sendAction(action.url, action.method || "POST", { ids }, function () {
          saveSelectedIds($table, []);
          reloadTable($table);
        });
      });
    });
  });

  window.YAJRA_DT = { collectFilters, exportUrl, initTable };
})();
