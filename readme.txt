=== Carspace User Dashboard ===
Contributors: carspace
Tags: dashboard, crm, woocommerce, react, spa, car-dealer
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 5.9.1
License: Proprietary
License URI: https://artcase.ge

Car dealer CRM dashboard — React SPA with WordPress REST API backend.

== Description ==

Replaces the WooCommerce My Account dashboard with a self-contained React single-page app. Lets dealers see their assigned cars, invoices, port-images, support tickets, and notifications. Admins manage transport prices, auction fees, title codes, and user tiers from the same UI.

* React SPA mounted via the `[carspace_app]` shortcode
* REST API namespace `carspace/v1` powers every screen
* Auto-updates from GitHub Releases (no .org plugin directory needed)

== Changelog ==

= 5.9.1 =
* Fix blank dashboard on v5.9.0 caused by an `Uncaught TypeError: O is not a function` in the recharts chunk. The previous Vite manualChunks config split `recharts` and `d3-*` into one bucket while their bundled-d3 helper `victory-vendor` fell into the catch-all `vendor` chunk — that chunk boundary in the middle of recharts' cyclical d3 imports broke runtime evaluation. Drops the manualChunks catch-all and lets Rollup attach lazy-route-only deps (recharts → DashboardPage, react-day-picker → date filters, @tanstack/react-table → grids, react-hook-form/zod → forms) to whichever lazy chunk imports them. First paint goes from ~265 KB gz to ~225 KB gz on non-dashboard routes.
* No PHP / DB changes.

= 5.9.0 =
* React SPA bundle split. The single 1.6 MB main bundle is gone — first paint now downloads ~265 KB gzipped (main + react-vendor + vendor) instead of ~468 KB. Each route is its own chunk loaded on demand:
  * `recharts` (~75 KB gz) only downloads when the dashboard opens
  * `react-day-picker` + `date-fns` (~19 KB gz) only when a date filter mounts
  * `@tanstack/react-table` (~14 KB gz) only on Cars / Invoices grids
  * `react-hook-form` + `zod` (~28 KB gz) only on form pages
* Vendor chunks are content-hashed and stable between releases — a code-only update means the browser re-downloads `main` (~38 KB gz) and the changed page chunks, not the entire app.
* React source is now version-controlled at github.com/Samsiani/carspace-dashboard-source.

= 5.8.0 =
* DB v1.9: add `vin_lower` STORED generated column on `wp_carspace_invoice_items` plus a new compound index `(vin_lower, invoice_id)`. Every VIN lookup (in `get_by_vin`, `get_buyer_by_vin`, `batch_vin_lookup`, and `get_dashboard_stats`) was rewritten to compare against `vin_lower` directly, removing the `LOWER(it.vin)` function call that previously defeated the index. The old `idx_vin_invoice (vin, invoice_id)` is dropped — it's no longer referenced by any query and was costing write overhead on every invoice item insert.
* No data change: the column is computed from the existing `vin` column at write time by MariaDB itself, so existing rows are populated automatically.

= 5.7.1 =
* Add this readme.txt so WordPress shows the plugin description and changelog on the Plugins page and in update notifications.

= 5.7.0 =
* Auto-updates: ship plugin-update-checker so future releases on GitHub appear automatically on update-core.php.
* DB v1.8: drop redundant indexes (`idx_vin` on items, `idx_user_status` on notifications) — both were strict prefixes of compound indexes added later, so they cost write overhead with zero read benefit.
* `next_invoice_number()` now flushes WP's option cache after the raw atomic UPDATE so callers don't get the pre-increment value.
* SPA shell emits `<link rel="modulepreload">` for every dynamically-imported chunk in the Vite manifest.
* Removed duplicate `global $wpdb;` in `get_dashboard_stats()`.

= 5.6.0 =
* Vite manifest cached via `wp_cache` with a version-stamped group; saves one `file_get_contents` + `json_decode` per SPA page hit.
* `Cache-Control: private, max-age=300` on `/transport-prices/locations`, `/auction-fees`, `/auction-fees/fixed`, `/title-codes` — admin-managed reference data that rarely changes.
* `Carspace_Notification::count_unread()` cached per-user (5 min TTL) with precise bust on `create` / `mark_read` / `mark_all_read` / `delete` / `bulk_delete`. Both the admin-bar badge and `/notifications/unread-count` REST endpoint benefit.
* `get_cars` `WP_Query` switched to `fields=ids` and skips implicit meta/term cache priming, since the explicit batch primes that follow already do that work.

= 5.5.0 =
* Versioned-key transient cache for `/dashboard/stats` and `/users` with `carspace_bust_data_cache()` busting on invoice mutations, `assigned_user` / pricing meta changes, and user create/update/delete.
* Removed three `wp_ajax_*` handlers in `helpers/notifications.php` that duplicated REST endpoints.
* Pruned ~340 KB of legacy code: `deact-assets/`, unused `endpoints/*`, `endpoints/car-invoices/`, `helpers/render-table.php` + `table-parts/`, `helpers/dashboard-cards.php`, `helpers/ajax-handlers.php`.

= 5.4.0 =
* Initial public release of the React SPA rewrite (production version on artcase.ge).
