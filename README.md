# M POS

A Laravel 13 point-of-sale foundation using PHP 8.3+ and MySQL.

## Local setup

1. Start Apache and MySQL in Laragon.
2. Copy `.env.example` to `.env` if the project does not already have a local `.env`, then set the MySQL host, port, database, username, and password.
3. Run:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   ```
4. Open the Laragon project URL and sign in:

   - Email: `admin@mpos.local`
   - Password: `12345678`

Demo accounts are created only when `APP_ENV=local`.

## Roles and permissions

Role grants, permission headings, and sidebar menu order are maintained in `config/access.php`. Each user's existing `type` is their role key. The same permission names are enforced by Laravel Gates on routes, DataTables, and exports, and control which menu items are shown. Unknown permissions are not granted.

The POS role keys are `super_admin`, `store_admin`, `manager`, `cashier`, `inventory_manager`, and `auditor`. The role migration converts legacy `company_admin` and `staff` values to `store_admin` and `cashier`.

To add a permission, add it under the appropriate `permission_groups` heading and assign it to the relevant roles. Add its menu entry to the desired ordered `menu` section. When a module route is added, protect it with `can:permission.name`; DataTable definitions use `permissions.view` and `permissions.export`.

## POS terminal

The authenticated terminal is available at `/dashboard/pos`. Products, customers, stock, sales, and invoice payments are scoped to a company; only `super_admin` can switch tenants. Branch stock is checked and locked during checkout. Product search supports barcode, SKU, product name, brand, and category; the catalog also has quick category filters.

Line and order discounts are separate and require `sales.discount`. A customer can pay part of a sale and leave the remainder due on that invoice; no customer balance is automatically applied. Walk-in sales must be paid in full. Cash, card, and mobile banking can be combined on one sale, with each amount and optional reference recorded separately. Later due collection is recorded against a selected invoice and cannot exceed its remaining balance.

Local demo products and customers are created by `PosDemoSeeder` when running `php artisan db:seed` in the local environment.

## DataTable engine

The engine handles server-side search, column search, configured filters, safe ordering, pagination, row formatting, actions, selection, and CSV/XLSX/PDF exports. It lives in small, separate files:

- Add one definition file per table in `config/data-tables/`.
- `DataTableRegistry` finds a configured table by key.
- `DataTablePermissionMap` checks who can view/export it.
- `DefinitionResolver` builds a configured table/join query or calls a custom `DataTableQuery`.
- `DataTableFilter` and `DataTableOrder` apply the allow-listed request options.
- `DataTableService` returns the DataTables JSON and formats rows.
- `ActionBuilder` adds configured row actions.
- `DataTableExportService` and the separate exporters handle CSV, XLSX, and PDF.
- `yajra.dt.factory.js` initializes tables, filters, exports, row selection, and configured bulk-action requests.

To add another listing:

1. Create `config/data-tables/products.php` and define the allowed table, columns, search/order fields, filters, permissions, and export columns.
2. Add a Blade table with `data-dt-key="products"` and `data-dt-columns`.
3. Add a `GET /dashboard/dt/{key}` page route inside the authenticated dashboard routes.
4. If the query needs joins or business-specific logic, add a small class implementing `App\Services\DataTable\DataTableQuery` and set `type`/`query_class` in that table definition.
5. Add explicit routes and permissions before enabling edit/delete/bulk actions.

Each `config/data-tables/{key}.php` file can declare:

```php
return [
    'type' => 'table',
    'table' => 'products',
    'columns' => ['id' => 'products.id as id', 'name' => 'products.name as name'],
    'searchable' => ['name'],
    'orderable' => ['id', 'name'],
    'default_order' => ['name', 'asc'],
    'company_scope_column' => 'products.company_id',
    'soft_delete_column' => 'products.deleted_at',
    'filters' => ['is_active' => ['type' => 'where', 'column' => 'products.is_active']],
    'permissions' => ['view' => 'products.view', 'export' => 'products.export'],
    'exports' => ['csv', 'xlsx', 'pdf'],
    'export_columns' => ['id', 'name'],
];
```

Filter types are `where`, `like`, `in`, and `date_between`. Row actions use an explicit URL, HTTP method, permission, and optional confirmation text. Bulk actions are configured on the table element with `data-dt-bulk-actions`; their URLs must point to a separately authorized application route. Selection is also used to export only selected rows.

Company-scoped definitions should set `company_scope_column`; the engine applies the signed-in user's company before search, counts, pagination, and exports. Exports share the configured search and filters and stop at `max_export_rows` so a large report does not exhaust memory.

## Tests

```bash
php artisan test
```
