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

    const DB_VERSION = '1.7';

    /**
     * Run on activation and on plugins_loaded when DB version changes.
     */
    public static function activate() {
        self::create_tables();
        self::add_indexes();
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
            amount      DECIMAL(12,2) DEFAULT 0,
            paid        DECIMAL(12,2) DEFAULT 0,
            sort_order  TINYINT UNSIGNED DEFAULT 0,
            KEY idx_invoice_id (invoice_id),
            KEY idx_vin (vin)
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
            KEY idx_user_status (user_id, status),
            KEY idx_created (created_at)
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
            array($wpdb->prefix . 'carspace_invoice_items', 'idx_vin_invoice', '(vin, invoice_id)'),
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
