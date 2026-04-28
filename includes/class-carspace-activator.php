<?php
/**
 * Carspace Activator
 *
 * Creates custom database tables and handles schema upgrades.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Activator {

    const DB_VERSION = '2.0';

    /**
     * Run on activation and on plugins_loaded when DB version changes.
     */
    public static function activate() {
        self::create_tables();
        self::add_indexes();
        self::drop_redundant_indexes();
        self::add_vin_lower_column();
        self::add_invoice_form_columns();
        self::migrate_user_tiers();
        update_option('carspace_db_version', self::DB_VERSION);
    }

    /**
     * Check if DB upgrade is needed (called on plugins_loaded).
     */
    public static function maybe_upgrade() {
        if (get_option('carspace_db_version') !== self::DB_VERSION) {
            self::activate();
        }
    }

    /**
     * Create all custom tables via dbDelta.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Invoices metadata
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_invoices (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id         BIGINT UNSIGNED NOT NULL,
            invoice_type    VARCHAR(50),
            status          VARCHAR(20) DEFAULT 'unpaid',
            customer_type   VARCHAR(20),
            customer_name   VARCHAR(255),
            customer_email  VARCHAR(255),
            customer_company_name VARCHAR(255),
            customer_personal_id  VARCHAR(50),
            company_ident_number  VARCHAR(50),
            invoice_date    DATE NULL,
            dealer_fee      DECIMAL(12,2) DEFAULT 0,
            dealer_fee_note TEXT,
            commission      DECIMAL(12,2) DEFAULT 0,
            subtotal        DECIMAL(12,2) DEFAULT 0,
            amount_paid     DECIMAL(12,2) DEFAULT 0,
            receipt_image_id  BIGINT UNSIGNED NULL,
            receipt_image_url VARCHAR(500),
            owner_user_id   BIGINT UNSIGNED NULL,
            UNIQUE KEY idx_post_id (post_id),
            KEY idx_status (status),
            KEY idx_owner (owner_user_id)
        ) {$charset_collate};");

        // 2. Invoice line items
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_invoice_items (
            id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id  BIGINT UNSIGNED NOT NULL,
            sale_date   DATE NULL,
            make        VARCHAR(100),
            model       VARCHAR(100),
            year        SMALLINT UNSIGNED NULL,
            vin         VARCHAR(20),
            description VARCHAR(255),
            quantity    DECIMAL(10,2) DEFAULT 1,
            unit_price  DECIMAL(12,2) DEFAULT 0,
            amount      DECIMAL(12,2) DEFAULT 0,
            paid        DECIMAL(12,2) DEFAULT 0,
            sort_order  TINYINT UNSIGNED DEFAULT 0,
            KEY idx_invoice_id (invoice_id)
            /* idx_vin removed in DB v1.8 — superseded by idx_vin_invoice (vin, invoice_id). */
        ) {$charset_collate};");

        // 3. Notifications
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_notifications (
            id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id       BIGINT UNSIGNED NOT NULL,
            title         VARCHAR(255),
            message       TEXT,
            type          VARCHAR(20) DEFAULT 'info',
            status        VARCHAR(20) DEFAULT 'unread',
            link          VARCHAR(500),
            visible_until DATETIME NULL,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_created (created_at)
            /* idx_user_status removed in DB v1.8 — superseded by idx_user_status_visible (user_id, status, visible_until). */
        ) {$charset_collate};");

        // 4. Port images
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_port_images (
            id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id     BIGINT UNSIGNED NOT NULL,
            attachment_id  BIGINT UNSIGNED NOT NULL,
            sort_order     TINYINT UNSIGNED DEFAULT 0,
            KEY idx_product (product_id)
        ) {$charset_collate};");

        // 5. User price tiers (replaces wp_usermeta tpc_price_tier)
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_user_tiers (
            user_id     BIGINT UNSIGNED NOT NULL,
            tier        VARCHAR(20) NOT NULL DEFAULT 'base_price',
            updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) {$charset_collate};");

        // 6. Support tickets
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_tickets (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject         VARCHAR(255) NOT NULL,
            status          VARCHAR(20) DEFAULT 'opened',
            priority        VARCHAR(20) DEFAULT 'medium',
            category        VARCHAR(50) DEFAULT 'general',
            author_id       BIGINT UNSIGNED NOT NULL,
            assigned_to     BIGINT UNSIGNED NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_author (author_id),
            KEY idx_status (status),
            KEY idx_assigned (assigned_to)
        ) {$charset_collate};");

        // 7. Ticket messages
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_ticket_messages (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id       BIGINT UNSIGNED NOT NULL,
            author_id       BIGINT UNSIGNED NOT NULL,
            content         TEXT NOT NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ticket (ticket_id),
            KEY idx_author (author_id)
        ) {$charset_collate};");

        // 8. Auction fee ranges
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_auction_fees (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            auction         VARCHAR(20) NOT NULL,
            fee_category    VARCHAR(30) NOT NULL,
            price_from      DECIMAL(12,2) NOT NULL DEFAULT 0,
            price_to        DECIMAL(12,2) NOT NULL DEFAULT 0,
            fee             DECIMAL(12,2) NOT NULL DEFAULT 0,
            fee_type        VARCHAR(20) NOT NULL DEFAULT 'fixed',
            sort_order      SMALLINT UNSIGNED DEFAULT 0,
            KEY idx_auction_cat (auction, fee_category)
        ) {$charset_collate};");

        // 9. Title codes
        dbDelta("CREATE TABLE {$wpdb->prefix}carspace_title_codes (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title_code      VARCHAR(255) NOT NULL,
            urgent_time     VARCHAR(50) DEFAULT '',
            urgent_charge   VARCHAR(50) DEFAULT '',
            standard_time   VARCHAR(50) DEFAULT '',
            standard_charge VARCHAR(50) DEFAULT '',
            KEY idx_title_code (title_code(100))
        ) {$charset_collate};");

        // 9. Transport prices (shared with transport-price-calculator plugin)
        dbDelta("CREATE TABLE {$wpdb->prefix}tpc_prices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            location varchar(255) NOT NULL,
            loading_port varchar(255) NOT NULL,
            base_price decimal(10,2) NOT NULL DEFAULT 0,
            price1 decimal(10,2) NOT NULL DEFAULT 0,
            price2 decimal(10,2) NOT NULL DEFAULT 0,
            price3 decimal(10,2) NOT NULL DEFAULT 0,
            price4 decimal(10,2) NOT NULL DEFAULT 0,
            price5 decimal(10,2) NOT NULL DEFAULT 0,
            price6 decimal(10,2) NOT NULL DEFAULT 0,
            price7 decimal(10,2) NOT NULL DEFAULT 0,
            price8 decimal(10,2) NOT NULL DEFAULT 0,
            price9 decimal(10,2) NOT NULL DEFAULT 0,
            price10 decimal(10,2) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};");
    }

    /**
     * Add compound indexes that dbDelta cannot handle.
     * Uses INFORMATION_SCHEMA to avoid duplicate index errors.
     */
    private static function add_indexes() {
        global $wpdb;

        $indexes = array(
            array($wpdb->prefix . 'carspace_invoices', 'idx_status_owner', '(status, owner_user_id)'),
            // VIN lookups now use vin_lower instead — see add_vin_lower_column().
            array($wpdb->prefix . 'carspace_notifications', 'idx_user_status_visible', '(user_id, status, visible_until)'),
            array($wpdb->prefix . 'carspace_tickets', 'idx_author_status', '(author_id, status)'),
            array($wpdb->prefix . 'carspace_ticket_messages', 'idx_ticket_created', '(ticket_id, created_at)'),
        );

        foreach ($indexes as $idx) {
            list($table, $name, $columns) = $idx;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
                DB_NAME, $table, $name
            ));
            if (!$exists) {
                $wpdb->query("ALTER TABLE `{$table}` ADD INDEX `{$name}` {$columns}");
            }
        }
    }

    /**
     * Drop indexes superseded by compound indexes added in add_indexes().
     *
     * idx_vin_invoice (vin, invoice_id) covers all queries that idx_vin (vin)
     * served, by leftmost-prefix matching.
     * idx_user_status_visible (user_id, status, visible_until) covers all
     * queries that idx_user_status (user_id, status) served.
     * Removing the shorter indexes saves write overhead on every notification
     * insert / status change and on every invoice item insert.
     *
     * Idempotent — checks INFORMATION_SCHEMA before dropping.
     */
    private static function drop_redundant_indexes() {
        global $wpdb;

        $drops = array(
            array($wpdb->prefix . 'carspace_invoice_items', 'idx_vin'),
            array($wpdb->prefix . 'carspace_notifications', 'idx_user_status'),
        );

        foreach ($drops as $row) {
            list($table, $name) = $row;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
                DB_NAME, $table, $name
            ));
            if ($exists) {
                $wpdb->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            }
        }
    }

    /**
     * DB v1.9: add `vin_lower` generated column + compound index, retire idx_vin_invoice.
     *
     * Every VIN lookup in this plugin uses LOWER(it.vin) IN (...) — that pattern
     * defeats the index because LOWER() is a function on the indexed column. By
     * persisting the lowercased value in a STORED generated column, the existing
     * queries (after a tiny rewrite) become straight equality lookups that hit
     * the new compound index on (vin_lower, invoice_id).
     *
     * MariaDB 10.2+ / MySQL 5.7+ required — both have been WP-baseline since 2018.
     * Idempotent — checks INFORMATION_SCHEMA before each ALTER.
     */
    private static function add_vin_lower_column() {
        global $wpdb;

        $table = $wpdb->prefix . 'carspace_invoice_items';

        // 1. Add the generated column.
        $col_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'vin_lower'",
            DB_NAME, $table
        ));
        if (!$col_exists) {
            $wpdb->query("ALTER TABLE `{$table}`
                ADD COLUMN vin_lower VARCHAR(20) GENERATED ALWAYS AS (LOWER(vin)) STORED AFTER vin");
        }

        // 2. Add the compound index (vin_lower, invoice_id).
        $idx_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'idx_vin_lower_invoice'",
            DB_NAME, $table
        ));
        if (!$idx_exists) {
            $wpdb->query("ALTER TABLE `{$table}` ADD INDEX idx_vin_lower_invoice (vin_lower, invoice_id)");
        }

        // 3. Drop the now-unused idx_vin_invoice — every VIN query was rewritten
        //    to use vin_lower, and idx_vin_lower_invoice covers the same access
        //    patterns. Saves write overhead on every invoice item insert.
        $old_idx = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'idx_vin_invoice'",
            DB_NAME, $table
        ));
        if ($old_idx) {
            $wpdb->query("ALTER TABLE `{$table}` DROP INDEX idx_vin_invoice");
        }
    }

    /**
     * DB v2.0: store the form's actual item shape (description / quantity /
     * unit_price) and add customer_email to invoices.
     *
     * The invoice form's data model evolved into generic line-items with
     * description × quantity × unit_price = total, but the original schema
     * only had `make`, `amount`, etc. Items that were saved came back missing
     * description/quantity/unit_price and `total` (the form reads `total`,
     * the API was returning `amount`), so the edit form looked empty.
     *
     * Idempotent — INFORMATION_SCHEMA-checked. Old rows with no description /
     * quantity / unit_price are read-fallback'd in hydrate_invoice
     * (description ←- make, quantity ← 1, unit_price ← amount).
     */
    private static function add_invoice_form_columns() {
        global $wpdb;

        $items_table    = $wpdb->prefix . 'carspace_invoice_items';
        $invoices_table = $wpdb->prefix . 'carspace_invoices';

        $additions = array(
            array($items_table,    'description', "ADD COLUMN description VARCHAR(255) AFTER vin"),
            array($items_table,    'quantity',    "ADD COLUMN quantity DECIMAL(10,2) DEFAULT 1 AFTER description"),
            array($items_table,    'unit_price',  "ADD COLUMN unit_price DECIMAL(12,2) DEFAULT 0 AFTER quantity"),
            array($invoices_table, 'customer_email', "ADD COLUMN customer_email VARCHAR(255) AFTER customer_name"),
        );

        foreach ($additions as $row) {
            list($table, $column, $alter_clause) = $row;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table, $column
            ));
            if (!$exists) {
                $wpdb->query("ALTER TABLE `{$table}` {$alter_clause}");
            }
        }
    }

    /**
     * One-time migration: copy tpc_price_tier from wp_usermeta to carspace_user_tiers.
     * Idempotent — skips users already present in the custom table.
     */
    private static function migrate_user_tiers() {
        global $wpdb;

        $tiers_table = $wpdb->prefix . 'carspace_user_tiers';

        // Only migrate if custom table is empty (first run)
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tiers_table}");
        if ($count > 0) {
            return;
        }

        // Copy from wp_usermeta
        $wpdb->query(
            "INSERT INTO {$tiers_table} (user_id, tier)
             SELECT um.user_id, um.meta_value
             FROM {$wpdb->usermeta} um
             WHERE um.meta_key = 'tpc_price_tier'
               AND um.meta_value != ''
             ON DUPLICATE KEY UPDATE tier = VALUES(tier)"
        );
    }
}
