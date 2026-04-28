=== Carspace User Dashboard ===
Contributors: carspace
Tags: dashboard, crm, woocommerce, react, spa, car-dealer
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 5.7.1
License: Proprietary
License URI: https://artcase.ge

Car dealer CRM dashboard — React SPA with WordPress REST API backend.

== Description ==

Replaces the WooCommerce My Account dashboard with a self-contained React single-page app. Lets dealers see their assigned cars, invoices, port-images, support tickets, and notifications. Admins manage transport prices, auction fees, title codes, and user tiers from the same UI.

* React SPA mounted via the `[carspace_app]` shortcode
* REST API namespace `carspace/v1` powers every screen
* Auto-updates from GitHub Releases (no .org plugin directory needed)

== Changelog ==

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
