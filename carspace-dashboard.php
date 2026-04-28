<?php
/*
Plugin Name: Carspace User Dashboard
Description: Car dealer CRM dashboard — React SPA with WordPress REST API backend
Version: 5.10.0
Author: Carspace
Text Domain: carspace-dashboard
Domain Path: /languages
*/

defined('ABSPATH') || exit;

define('CARSPACE_VERSION', '5.10.0');
define('CARSPACE_DB_VERSION', '1.5');
define('CARSPACE_PATH', plugin_dir_path(__FILE__));
define('CARSPACE_URL', plugin_dir_url(__FILE__));
define('CARSPACE_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class — React SPA dashboard with WP REST API backend.
 *
 * All frontend rendering is handled by the React app loaded via [carspace_app] shortcode.
 * No WooCommerce My Account endpoints or legacy PHP/Bootstrap UI.
 */
class Carspace_Dashboard {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->include_files();
        $this->register_hooks();
    }

    private function include_files() {
        // Database activator
        require_once CARSPACE_PATH . 'includes/class-carspace-activator.php';

        // Models
        require_once CARSPACE_PATH . 'includes/models/class-carspace-invoice.php';
        require_once CARSPACE_PATH . 'includes/models/class-carspace-notification.php';
        require_once CARSPACE_PATH . 'includes/models/class-carspace-port-images.php';
        require_once CARSPACE_PATH . 'includes/models/class-carspace-transport-price.php';
        require_once CARSPACE_PATH . 'includes/models/class-carspace-ticket.php';
        require_once CARSPACE_PATH . 'includes/models/class-carspace-title-code.php';
        require_once CARSPACE_PATH . 'includes/models/class-carspace-auction-fee.php';

        // REST API (serves data to React SPA)
        require_once CARSPACE_PATH . 'includes/class-carspace-rest-api.php';

        // Frontend (React SPA shortcode loader)
        require_once CARSPACE_PATH . 'includes/class-carspace-frontend.php';

        // Standalone shortcodes (embeddable widgets)
        require_once CARSPACE_PATH . 'includes/class-carspace-auction-fee-shortcode.php';

        // Admin pages
        require_once CARSPACE_PATH . 'includes/class-carspace-admin.php';

        // Port images metabox (product edit screen)
        require_once CARSPACE_PATH . 'includes/class-carspace-port-images-metabox.php';

        // Car assignment metabox (product edit screen)
        require_once CARSPACE_PATH . 'includes/class-carspace-assign-user-metabox.php';

        // Utility functions (used by REST API)
        require_once CARSPACE_PATH . 'includes/helpers/utils.php';

        // Notification helper functions (used by notification hooks)
        require_once CARSPACE_PATH . 'includes/helpers/notifications.php';

        // Notification triggers (backend hooks — fire on car assignment, delivery, invoice creation)
        require_once CARSPACE_PATH . 'includes/notifications/init.php';

        // WP-CLI migration command
        if (defined('WP_CLI') && WP_CLI) {
            require_once CARSPACE_PATH . 'includes/class-carspace-migration.php';
        }
    }

    private function register_hooks() {
        // DB upgrade check
        add_action('plugins_loaded', array('Carspace_Activator', 'maybe_upgrade'));

        // Register [carspace_app] shortcode
        add_action('init', array('Carspace_Frontend', 'init'));

        // REST API endpoints
        Carspace_REST_API::init();

        // Admin menu
        Carspace_Admin::init();

        // Standalone shortcodes
        Carspace_Auction_Fee_Shortcode::init();

        // Port images metabox on product edit
        Carspace_Port_Images_Metabox::init();

        // Car assignment metabox on product edit
        Carspace_Assign_User_Metabox::init();

        // Legacy shortcode alias
        add_shortcode('carspace_dashboard', array($this, 'render_dashboard_shortcode'));

        // Invoice CPT
        add_action('init', array($this, 'register_invoice_cpt'));

        // Clean up custom table data when invoice post is deleted
        add_action('before_delete_post', array($this, 'cleanup_invoice_data'));

        // Activation
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
    }

    public function register_invoice_cpt() {
        if (!post_type_exists('invoice')) {
            register_post_type('invoice', array(
                'public'       => false,
                'show_ui'      => true,
                'supports'     => array('title', 'editor'),
                'menu_icon'    => 'dashicons-media-text',
                'labels'       => array(
                    'name'               => __('Invoices', 'carspace-dashboard'),
                    'singular_name'      => __('Invoice', 'carspace-dashboard'),
                    'menu_name'          => __('Invoices', 'carspace-dashboard'),
                    'add_new'            => __('Add New', 'carspace-dashboard'),
                    'add_new_item'       => __('Add New Invoice', 'carspace-dashboard'),
                    'edit_item'          => __('Edit Invoice', 'carspace-dashboard'),
                    'new_item'           => __('New Invoice', 'carspace-dashboard'),
                    'view_item'          => __('View Invoice', 'carspace-dashboard'),
                    'search_items'       => __('Search Invoices', 'carspace-dashboard'),
                    'not_found'          => __('No invoices found', 'carspace-dashboard'),
                    'not_found_in_trash' => __('No invoices found in trash', 'carspace-dashboard'),
                ),
            ));
        }
    }

    public function render_dashboard_shortcode() {
        return Carspace_Frontend::render_shortcode();
    }

    public function cleanup_invoice_data($post_id) {
        if (get_post_type($post_id) !== 'invoice') return;

        global $wpdb;
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}carspace_invoices WHERE post_id = %d",
            $post_id
        ));
        if ($invoice) {
            $wpdb->delete($wpdb->prefix . 'carspace_invoice_items', array('invoice_id' => $invoice->id), array('%d'));
            $wpdb->delete($wpdb->prefix . 'carspace_invoices', array('post_id' => $post_id), array('%d'));
        }
    }

    public function plugin_activation() {
        Carspace_Activator::activate();
        flush_rewrite_rules();
        update_option('carspace_dashboard_version', CARSPACE_VERSION);
    }
}

// Boot
Carspace_Dashboard::get_instance();

/**
 * GitHub Releases auto-update via plugin-update-checker.
 *
 * PUC polls https://api.github.com/repos/Samsiani/carspace-user-dashboard/releases
 * and surfaces new versions through WP's native update flow (Plugins page +
 * update-core.php). The plugin slug MUST stay 'carspace-dashboard' — that is
 * the folder name on disk, and changing it breaks the update path matching.
 *
 * The release-asset path is enabled with no arguments so PUC picks the first
 * .zip attached to a release (our workflow attaches carspace-dashboard-vX.Y.Z.zip
 * which extracts to ./carspace-dashboard/, matching the install location).
 */
require_once CARSPACE_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

$carspace_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/Samsiani/carspace-user-dashboard/',
    __FILE__,
    'carspace-dashboard'
);
$carspace_update_checker->getVcsApi()->enableReleaseAssets();

/**
 * Bump the global response-cache version stamp.
 *
 * Read by Carspace_REST_API::cache_version() to build cache keys, so any
 * call here orphans every existing cached response without touching them.
 * Used by the hooks below to invalidate /dashboard/stats and /users on
 * data mutations. Stored as autoload=false to keep wp_options light.
 */
function carspace_bust_data_cache() {
    update_option( 'carspace_data_cache_version', time(), false );
}

// Invoice CPT mutations.
add_action( 'save_post_invoice', 'carspace_bust_data_cache' );
add_action( 'deleted_post', function ( $post_id, $post = null ) {
    if ( $post && $post->post_type === 'invoice' ) {
        carspace_bust_data_cache();
    }
}, 10, 2 );

// Car assignment / pricing meta changes that affect stats + user revenue.
add_action( 'updated_post_meta', function ( $meta_id, $object_id, $meta_key ) {
    static $tracked = array(
        'assigned_user'  => 1,
        '_regular_price' => 1,
        '_price'         => 1,
    );
    if ( isset( $tracked[ $meta_key ] ) ) {
        carspace_bust_data_cache();
    }
}, 10, 3 );
add_action( 'added_post_meta', function ( $meta_id, $object_id, $meta_key ) {
    static $tracked = array(
        'assigned_user'  => 1,
        '_regular_price' => 1,
        '_price'         => 1,
    );
    if ( isset( $tracked[ $meta_key ] ) ) {
        carspace_bust_data_cache();
    }
}, 10, 3 );

// User-table changes that affect /users response.
add_action( 'profile_update', 'carspace_bust_data_cache' );
add_action( 'user_register', 'carspace_bust_data_cache' );
add_action( 'delete_user', 'carspace_bust_data_cache' );
