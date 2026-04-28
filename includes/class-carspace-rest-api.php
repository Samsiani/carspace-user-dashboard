<?php
/**
 * Carspace REST API
 *
 * Registers all REST endpoints under carspace/v1 for the React SPA frontend.
 *
 * @package Carspace_Dashboard
 * @since   5.1.0
 */

defined( 'ABSPATH' ) || exit;

class Carspace_REST_API {

    const NAMESPACE = 'carspace/v1';

    /**
     * Register routes on rest_api_init.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /* ------------------------------------------------------------------
     * Route registration
     * ----------------------------------------------------------------*/

    public static function register_routes() {

        /* --- Cars --- */
        register_rest_route( self::NAMESPACE, '/cars', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_cars' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            'args'                => self::cars_args(),
        ) );

        register_rest_route( self::NAMESPACE, '/cars/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_car' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        register_rest_route( self::NAMESPACE, '/cars/(?P<id>\d+)/transport-price', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( __CLASS__, 'update_car_transport_price' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/cars/(?P<id>\d+)/calculate-price', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'calculate_car_transport_price' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        /* --- Invoices --- */
        register_rest_route( self::NAMESPACE, '/invoices', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_invoices' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
                'args'                => self::invoices_args(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'create_invoice' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/invoices/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_invoice' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( __CLASS__, 'update_invoice_endpoint' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_invoice' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
        ) );

        /* --- Notifications --- */
        register_rest_route( self::NAMESPACE, '/notifications', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_notifications' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            'args'                => array(
                'page'     => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
                'per_page' => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
                'unread'   => array( 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'create_notification' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/unread-count', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_unread_count' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/(?P<id>\d+)/read', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'mark_notification_read' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/read-all', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'mark_all_notifications_read' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( __CLASS__, 'delete_notification' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/all', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_all_notifications_admin' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
            'args'                => array(
                'page'     => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
                'per_page' => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
                'search'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/bulk-delete', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'bulk_delete_notifications' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/notifications/settings', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_notification_settings' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => array( __CLASS__, 'update_notification_settings' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        /* --- Tickets --- */
        register_rest_route( self::NAMESPACE, '/tickets', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_tickets' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
                'args'                => array(
                    'page'     => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
                    'per_page' => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
                    'status'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
                    'search'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'create_ticket' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/tickets/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_ticket' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( __CLASS__, 'update_ticket' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_ticket' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/tickets/(?P<id>\d+)/reply', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'reply_ticket' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        /* --- Users (admin only) --- */
        register_rest_route( self::NAMESPACE, '/users', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_users' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        /* --- Transport Prices --- */
        register_rest_route( self::NAMESPACE, '/transport-prices', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_transport_prices' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'create_transport_price' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/transport-prices/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( __CLASS__, 'update_transport_price' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_transport_price' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/transport-prices/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'import_transport_prices' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/transport-prices/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'export_transport_prices' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/transport-prices/delete-all', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( __CLASS__, 'delete_all_transport_prices' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/transport-prices/calculate', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'calculate_transport_price' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            'args'                => array(
                'location_id'  => array( 'sanitize_callback' => 'absint' ),
                'location'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
                'loading_port' => array( 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/transport-prices/locations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_transport_locations' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        /* --- User Tier (admin only) --- */
        register_rest_route( self::NAMESPACE, '/users/(?P<id>\d+)/tier', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_user_tier' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( __CLASS__, 'set_user_tier' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        /* --- Dashboard stats --- */
        register_rest_route( self::NAMESPACE, '/dashboard/stats', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_dashboard_stats' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        /* --- Auction Fees --- */
        register_rest_route( self::NAMESPACE, '/auction-fees', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_auction_fees' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/ranges', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'save_auction_fee_range' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/ranges/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( __CLASS__, 'update_auction_fee_range' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_auction_fee_range' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/bulk-delete', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'bulk_delete_auction_fees' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/fixed', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_auction_fixed_fees' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'save_auction_fixed_fees' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'import_auction_fees' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'export_auction_fees' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/auction-fees/calculate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'calculate_auction_fee' ),
            'permission_callback' => array( __CLASS__, 'check_logged_in' ),
        ) );

        /* --- Title Codes --- */
        register_rest_route( self::NAMESPACE, '/title-codes', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_title_codes' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'save_title_code' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/title-codes/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( __CLASS__, 'update_title_code' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_title_code' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/title-codes/bulk-delete', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'bulk_delete_title_codes' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/title-codes/delete-all', array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( __CLASS__, 'delete_all_title_codes' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/title-codes/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'import_title_codes' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        register_rest_route( self::NAMESPACE, '/title-codes/export', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'export_title_codes' ),
            'permission_callback' => array( __CLASS__, 'check_admin' ),
        ) );

        /* --- Translations --- */
        register_rest_route( self::NAMESPACE, '/translations', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_translations' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'save_translations' ),
                'permission_callback' => array( __CLASS__, 'check_admin' ),
            ),
        ) );

        /* --- Profile --- */
        register_rest_route( self::NAMESPACE, '/profile', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_profile' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'update_profile' ),
                'permission_callback' => array( __CLASS__, 'check_logged_in' ),
            ),
        ) );

        /* --- Auth (login / logout) --- */
        register_rest_route( self::NAMESPACE, '/auth/login', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'auth_login' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( self::NAMESPACE, '/auth/logout', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'auth_logout' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /* ------------------------------------------------------------------
     * Permission callbacks
     * ----------------------------------------------------------------*/

    public static function check_logged_in() {
        return is_user_logged_in();
    }

    public static function check_admin() {
        return current_user_can( 'manage_options' );
    }

    /* ------------------------------------------------------------------
     * Response cache (transient-based, versioned keys)
     *
     * Heavy aggregate endpoints (/dashboard/stats, /users) cache their
     * full response for a short TTL. The cache key embeds a global
     * version stamp; any data-mutating event bumps the stamp via
     * carspace_bust_data_cache(), which orphans all old entries.
     * ----------------------------------------------------------------*/

    const CACHE_TTL = 60; // seconds

    private static function cache_version() {
        // autoload=false option, written only by carspace_bust_data_cache().
        return (int) get_option( 'carspace_data_cache_version', 1 );
    }

    private static function cache_get( $key ) {
        return get_transient( $key );
    }

    private static function cache_set( $key, $data ) {
        set_transient( $key, $data, self::CACHE_TTL );
    }

    /**
     * Wrap data in a WP_REST_Response with a private browser cache TTL.
     * Used for admin-managed reference data (auction fees, title codes,
     * locations) that rarely changes — saves the network round-trip on
     * dashboard reload. `private` keeps shared CDNs out (responses are
     * authenticated by cookie).
     */
    private static function reference_response( $data, $max_age = 300 ) {
        $response = rest_ensure_response( $data );
        $response->header( 'Cache-Control', 'private, max-age=' . (int) $max_age );
        return $response;
    }

    /* ------------------------------------------------------------------
     * Role helpers
     * ----------------------------------------------------------------*/

    /**
     * Determine normalised role string for a WP_User.
     *
     * @param  WP_User|null $user
     * @return string       admin | manager | dealer
     */
    private static function get_user_role( $user = null ) {
        if ( ! $user ) {
            $user = wp_get_current_user();
        }

        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return 'admin';
        }

        if ( array_intersect( array( 'editor', 'shop_manager' ), (array) $user->roles ) ) {
            return 'manager';
        }

        return 'dealer';
    }

    /**
     * Check whether the current user can see all data (admin / manager).
     */
    private static function is_elevated() {
        return in_array( self::get_user_role(), array( 'admin', 'manager' ), true );
    }

    /**
     * Whether the current user can see ALL invoices (manager only).
     */
    private static function is_manager() {
        return self::get_user_role() === 'manager';
    }

    /**
     * Get VINs (SKUs) of all products assigned to a user.
     */
    private static function get_user_assigned_vins( $user_id ) {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare(
            "SELECT pm_sku.meta_value
             FROM {$wpdb->postmeta} pm_assign
             INNER JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_assign.post_id AND pm_sku.meta_key = '_sku'
             INNER JOIN {$wpdb->posts} p ON p.ID = pm_assign.post_id AND p.post_status = 'publish'
             WHERE pm_assign.meta_key = 'assigned_user' AND pm_assign.meta_value = %s AND pm_sku.meta_value != ''",
            $user_id
        ) );
    }

    /* ------------------------------------------------------------------
     * 1. GET /cars
     * ----------------------------------------------------------------*/

    private static function cars_args() {
        return array(
            'page'     => array( 'default' => 1,  'sanitize_callback' => 'absint' ),
            'per_page' => array( 'default' => 50, 'sanitize_callback' => 'absint' ),
            'search'   => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
            'status'   => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    public static function get_cars( $request ) {
        $page     = max( 1, $request->get_param( 'page' ) );
        $per_page = min( 500, max( 1, $request->get_param( 'per_page' ) ) );
        $search   = $request->get_param( 'search' );
        $status   = $request->get_param( 'status' );

        $user_id  = get_current_user_id();

        // Build WP_Query args for WC products.
        // fields=ids + suppress implicit cache priming — we hydrate the
        // products via wc_get_products() below and prime meta/term caches
        // explicitly. WP_Query's own priming would duplicate that work.
        $args = array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => $per_page,
            'paged'                  => $page,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'fields'                 => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        // All users see only their assigned cars
        $args['meta_query'] = array(
            array(
                'key'   => 'assigned_user',
                'value' => $user_id,
            ),
        );

        // Keyword search (title + SKU)
        if ( $search ) {
            // First check if it matches a SKU exactly or partially
            global $wpdb;
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $sku_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_sku' AND meta_value LIKE %s",
                $like
            ) );

            if ( ! empty( $sku_ids ) ) {
                // Search by title OR SKU match
                add_filter( 'posts_where', $sku_where = function( $where ) use ( $sku_ids, $wpdb, $search ) {
                    $ids_str = implode( ',', array_map( 'intval', $sku_ids ) );
                    $title_like = '%' . $wpdb->esc_like( $search ) . '%';
                    $where .= $wpdb->prepare(
                        " AND ({$wpdb->posts}.ID IN ({$ids_str}) OR {$wpdb->posts}.post_title LIKE %s)",
                        $title_like
                    );
                    return $where;
                } );
            } else {
                $args['s'] = $search;
            }
        }

        $query = new WP_Query( $args );

        // Remove filter if added
        if ( $search && ! empty( $sku_ids ) ) {
            remove_filter( 'posts_where', $sku_where );
        }

        // With fields=ids, $query->posts is already an array of IDs.
        $product_ids = array_map( 'intval', $query->posts );

        if ( empty( $product_ids ) ) {
            return new WP_REST_Response( array(
                'items'       => array(),
                'total'       => 0,
                'total_pages' => 0,
                'page'        => $page,
            ), 200 );
        }

        // Batch prime caches
        update_meta_cache( 'post', $product_ids );
        update_object_term_cache( $product_ids, 'product' );

        // Batch load WC product objects
        $wc_products = wc_get_products( array(
            'include' => $product_ids,
            'limit'   => count( $product_ids ),
            'return'  => 'objects',
        ) );
        $wc_map = array();
        foreach ( $wc_products as $p ) {
            $wc_map[ $p->get_id() ] = $p;
        }

        // Batch port-images check
        $port_images_map = Carspace_Port_Images::batch_check( $product_ids );

        // Batch load actual port image attachment IDs for delivered cars
        $port_images_data = self::batch_port_images( $product_ids );

        // Collect VINs for batch invoice lookup
        $vins = array();
        foreach ( $product_ids as $pid ) {
            $product = isset( $wc_map[ $pid ] ) ? $wc_map[ $pid ] : null;
            if ( $product ) {
                $vin = $product->get_sku();
                if ( $vin ) {
                    $vins[] = $vin;
                }
            }
        }
        $vin_lookup = Carspace_Invoice::batch_vin_lookup( $vins );

        // Batch fetch assigned dealer names
        $dealer_ids = array_unique( array_filter( array_map( function( $pid ) {
            return (int) get_post_meta( $pid, 'assigned_user', true );
        }, $product_ids ) ) );
        $dealers_map = ! empty( $dealer_ids ) ? self::batch_users( $dealer_ids ) : array();

        // Batch fetch all attachment URLs (gallery + featured + port images) in one query
        $all_att_ids = array();
        foreach ( $product_ids as $pid ) {
            $product = isset( $wc_map[ $pid ] ) ? $wc_map[ $pid ] : null;
            if ( ! $product ) continue;
            $fid = $product->get_image_id();
            if ( $fid ) $all_att_ids[] = (int) $fid;
            foreach ( $product->get_gallery_image_ids() as $gid ) {
                $all_att_ids[] = (int) $gid;
            }
        }
        foreach ( $port_images_data as $atts ) {
            foreach ( $atts as $aid ) {
                $all_att_ids[] = (int) $aid;
            }
        }
        $att_url_map = self::batch_attachment_urls( $all_att_ids );

        // Hydrate each car
        $items = array();
        foreach ( $product_ids as $pid ) {
            $product = isset( $wc_map[ $pid ] ) ? $wc_map[ $pid ] : null;
            if ( ! $product ) {
                continue;
            }

            $car = self::hydrate_car( $product, $port_images_map, $port_images_data, $vin_lookup, $dealers_map, $att_url_map );

            // Optional status filter (post-hydration because status is computed)
            if ( $status && $car['status'] !== $status ) {
                continue;
            }

            $items[] = $car;
        }

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => $page,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 2. GET /cars/{id}
     * ----------------------------------------------------------------*/

    public static function get_car( $request ) {
        $id      = (int) $request->get_param( 'id' );
        $product = wc_get_product( $id );

        if ( ! $product || $product->get_type() === 'variation' ) {
            return new WP_Error( 'not_found', 'Car not found', array( 'status' => 404 ) );
        }

        // All users see only their assigned cars
        $assigned = get_post_meta( $id, 'assigned_user', true );
        if ( (int) $assigned !== get_current_user_id() ) {
            return new WP_Error( 'forbidden', 'You do not have access to this car', array( 'status' => 403 ) );
        }

        $port_images_map  = Carspace_Port_Images::batch_check( array( $id ) );
        $port_images_data = self::batch_port_images( array( $id ) );

        $vin        = $product->get_sku();
        $vin_lookup = $vin ? Carspace_Invoice::batch_vin_lookup( array( $vin ) ) : array( 'buyers' => array(), 'invoices' => array() );

        $dealer_id   = (int) get_post_meta( $id, 'assigned_user', true );
        $dealers_map = $dealer_id ? self::batch_users( array( $dealer_id ) ) : array();

        // Batch attachment URLs
        $all_att_ids = array();
        $fid = $product->get_image_id();
        if ( $fid ) $all_att_ids[] = (int) $fid;
        foreach ( $product->get_gallery_image_ids() as $gid ) {
            $all_att_ids[] = (int) $gid;
        }
        if ( isset( $port_images_data[ $id ] ) ) {
            foreach ( $port_images_data[ $id ] as $aid ) {
                $all_att_ids[] = (int) $aid;
            }
        }
        $att_url_map = self::batch_attachment_urls( $all_att_ids );

        $car = self::hydrate_car( $product, $port_images_map, $port_images_data, $vin_lookup, $dealers_map, $att_url_map );

        return new WP_REST_Response( $car, 200 );
    }

    /* ------------------------------------------------------------------
     * 2b. PUT /cars/{id}/transport-price — set transport (regular) price
     * ----------------------------------------------------------------*/

    public static function update_car_transport_price( $request ) {
        $id   = (int) $request->get_param( 'id' );
        $body = $request->get_json_params();

        $product = wc_get_product( $id );
        if ( ! $product ) {
            return new WP_Error( 'not_found', 'Car not found', array( 'status' => 404 ) );
        }

        $price = isset( $body['transport_price'] ) ? floatval( $body['transport_price'] ) : null;
        if ( $price === null ) {
            return new WP_Error( 'missing_price', 'transport_price is required', array( 'status' => 400 ) );
        }

        if ( $price > 0 ) {
            update_post_meta( $id, '_regular_price', wc_format_decimal( $price ) );
            update_post_meta( $id, '_price', wc_format_decimal( $price ) );
        } else {
            update_post_meta( $id, '_regular_price', '' );
            update_post_meta( $id, '_price', '' );
        }
        wc_delete_product_transients( $id );

        return new WP_REST_Response( array(
            'id'              => $id,
            'transport_price' => $price,
            'message'         => 'Transport price updated.',
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 2c. POST /cars/{id}/calculate-price — auto-calculate from user tier
     * ----------------------------------------------------------------*/

    public static function calculate_car_transport_price( $request ) {
        $id = (int) $request->get_param( 'id' );

        $product = wc_get_product( $id );
        if ( ! $product ) {
            return new WP_Error( 'not_found', 'Car not found', array( 'status' => 404 ) );
        }

        $user_id = (int) get_post_meta( $id, 'assigned_user', true );

        if ( $user_id ) {
            // Has assigned user — use their tier
            Carspace_Transport_Price::auto_set_transport_price( $id, $user_id );
            $tier = Carspace_Transport_Price::get_user_tier( $user_id );
        } else {
            // No assigned user — calculate base price directly
            $price = null;

            $location_id_raw = $product->get_attribute( 'Location ID' );
            if ( ! $location_id_raw ) {
                $location_id_raw = $product->get_attribute( 'location-id' );
            }
            if ( ! $location_id_raw ) {
                $location_id_raw = $product->get_attribute( 'pa_location-id' );
            }
            $location_id = intval( preg_replace( '/\D+/', '', (string) $location_id_raw ) );

            if ( $location_id > 0 ) {
                $row = Carspace_Transport_Price::find( $location_id );
                if ( $row ) {
                    $price = (float) $row->base_price;
                }
            }

            // Fallback: Auction City + Loading Port
            if ( $price === null ) {
                $auction_city = $product->get_attribute( 'Auction City' );
                if ( ! $auction_city ) {
                    $auction_city = $product->get_attribute( 'auction-city' );
                }
                $loading_port = $product->get_attribute( 'Loading Port' );
                if ( ! $loading_port ) {
                    $loading_port = $product->get_attribute( 'loading-port' );
                }
                if ( $auction_city && $loading_port ) {
                    $row = Carspace_Transport_Price::find_by_route( $auction_city, $loading_port );
                    if ( $row ) {
                        $price = (float) $row->base_price;
                    }
                }
            }

            if ( $price !== null ) {
                update_post_meta( $id, '_regular_price', wc_format_decimal( $price ) );
                update_post_meta( $id, '_price', wc_format_decimal( $price ) );
                wc_delete_product_transients( $id );
            }

            $tier = 'base_price';
        }

        $new_price = get_post_meta( $id, '_regular_price', true );

        return new WP_REST_Response( array(
            'id'              => $id,
            'transport_price' => $new_price !== '' ? (float) $new_price : 0,
            'tier'            => $tier ?: 'base_price',
            'message'         => $new_price !== ''
                ? 'Price calculated: $' . number_format( (float) $new_price, 2 )
                : 'No price found for this location/tier combination.',
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 3. GET /invoices
     * ----------------------------------------------------------------*/

    private static function invoices_args() {
        return array(
            'page'     => array( 'default' => 1,  'sanitize_callback' => 'absint' ),
            'per_page' => array( 'default' => 50, 'sanitize_callback' => 'absint' ),
            'status'   => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
            'search'   => array( 'default' => '',  'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    public static function get_invoices( $request ) {
        global $wpdb;

        $page     = max( 1, $request->get_param( 'page' ) );
        $per_page = min( 200, max( 1, $request->get_param( 'per_page' ) ) );
        $status   = $request->get_param( 'status' );
        $search   = $request->get_param( 'search' );

        $user_id  = get_current_user_id();

        $table    = $wpdb->prefix . 'carspace_invoices';
        $itm_tbl  = $wpdb->prefix . 'carspace_invoice_items';

        // Build WHERE clauses
        $where   = array( '1=1', 'p.ID IS NOT NULL' );
        $params  = array();
        $extra_join = '';

        // Only managers see all invoices; others see invoices matching their assigned cars' VINs
        if ( ! self::is_manager() ) {
            $user_vins = self::get_user_assigned_vins( $user_id );
            if ( ! empty( $user_vins ) ) {
                $vin_placeholders = implode( ',', array_fill( 0, count( $user_vins ), '%s' ) );
                $extra_join = " INNER JOIN {$itm_tbl} vi ON vi.invoice_id = i.id";
                $where[]    = $wpdb->prepare( "vi.vin IN ({$vin_placeholders})", $user_vins );
            } else {
                $where[] = '1=0';
            }
        }

        if ( $status ) {
            $where[]  = 'i.status = %s';
            $params[] = $status;
        }

        if ( $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $where[]   = '(i.customer_name LIKE %s OR i.customer_company_name LIKE %s OR p.post_title LIKE %s)';
            $params[]  = $like;
            $params[]  = $like;
            $params[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Count (DISTINCT to handle VIN join duplicates)
        $count_sql = "SELECT COUNT(DISTINCT i.id) FROM {$table} i {$extra_join} LEFT JOIN {$wpdb->posts} p ON p.ID = i.post_id WHERE {$where_sql}";
        $total     = (int) ( empty( $params )
            ? $wpdb->get_var( $count_sql )
            : $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) )
        );

        $total_pages = max( 1, (int) ceil( $total / $per_page ) );
        $offset      = ( $page - 1 ) * $per_page;

        // Fetch rows (DISTINCT to handle VIN join duplicates)
        $data_sql = "SELECT DISTINCT i.*, p.post_title, p.post_date, p.post_modified
                     FROM {$table} i
                     {$extra_join}
                     LEFT JOIN {$wpdb->posts} p ON p.ID = i.post_id
                     WHERE {$where_sql}
                     ORDER BY i.id DESC
                     LIMIT %d OFFSET %d";

        $all_params   = array_merge( $params, array( $per_page, $offset ) );
        $rows         = $wpdb->get_results( $wpdb->prepare( $data_sql, $all_params ) );

        if ( empty( $rows ) ) {
            return new WP_REST_Response( array(
                'items'       => array(),
                'total'       => $total,
                'total_pages' => $total_pages,
                'page'        => $page,
            ), 200 );
        }

        // Batch fetch items for all invoices
        $invoice_ids = wp_list_pluck( $rows, 'id' );
        $items_map   = self::batch_invoice_items( $invoice_ids );

        // Batch author info
        $author_ids = array_unique( array_filter( wp_list_pluck( $rows, 'owner_user_id' ) ) );
        $authors    = self::batch_users( $author_ids );

        // Batch SKU→product_id lookup for all invoice VINs
        $all_vins = array();
        foreach ( $items_map as $inv_items ) {
            foreach ( $inv_items as $it ) {
                if ( ! empty( $it->vin ) ) {
                    $all_vins[] = $it->vin;
                }
            }
        }
        $sku_map = ! empty( $all_vins ) ? self::batch_sku_to_product_id( $all_vins ) : array();

        $items = array();
        foreach ( $rows as $row ) {
            $items[] = self::hydrate_invoice( $row, $items_map, $authors, $sku_map );
        }

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => $total,
            'total_pages' => $total_pages,
            'page'        => $page,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 4. GET /invoices/{id}
     * ----------------------------------------------------------------*/

    public static function get_invoice( $request ) {
        $post_id = (int) $request->get_param( 'id' );

        $invoice = Carspace_Invoice::find( $post_id );
        if ( ! $invoice ) {
            return new WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        // Access check — only managers can see all invoices; others need matching assigned VINs
        if ( ! self::is_manager() ) {
            $user_vins = self::get_user_assigned_vins( get_current_user_id() );
            $invoice_vins = wp_list_pluck( $invoice->items, 'vin' );
            if ( empty( array_intersect( $user_vins, $invoice_vins ) ) ) {
                return new WP_Error( 'forbidden', 'You do not have access to this invoice', array( 'status' => 403 ) );
            }
        }

        $post = get_post( $post_id );
        if ( $post ) {
            $invoice->post_title    = $post->post_title;
            $invoice->post_date     = $post->post_date;
            $invoice->post_modified = $post->post_modified;
        }

        // Build items map from the already-loaded items
        $items_map = array( (int) $invoice->id => $invoice->items );

        // Author
        $authors = array();
        if ( $invoice->owner_user_id ) {
            $authors = self::batch_users( array( (int) $invoice->owner_user_id ) );
        }

        // SKU→product_id for this invoice's VINs
        $vins = array();
        foreach ( $invoice->items as $it ) {
            if ( ! empty( $it->vin ) ) $vins[] = $it->vin;
        }
        $sku_map = ! empty( $vins ) ? self::batch_sku_to_product_id( $vins ) : array();

        $data = self::hydrate_invoice( $invoice, $items_map, $authors, $sku_map );

        return new WP_REST_Response( $data, 200 );
    }

    /* ------------------------------------------------------------------
     * 4b. POST /invoices
     * ----------------------------------------------------------------*/

    public static function create_invoice( $request ) {
        $body    = $request->get_json_params();
        $user_id = get_current_user_id();

        // Generate invoice number via atomic counter (avoids COUNT(*) full scan)
        $next = self::next_invoice_number();
        $invoice_number = 'INV-' . str_pad( $next, 4, '0', STR_PAD_LEFT );

        // Create CPT post
        $post_id = wp_insert_post( array(
            'post_type'   => 'invoice',
            'post_status' => 'publish',
            'post_title'  => $invoice_number,
            'post_author' => $user_id,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'create_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
        }

        // Map frontend items to DB items
        $db_items = array();
        if ( ! empty( $body['items'] ) && is_array( $body['items'] ) ) {
            foreach ( $body['items'] as $item ) {
                $db_items[] = array(
                    'sale_date' => '',
                    'make'      => isset( $item['description'] ) ? $item['description'] : '',
                    'model'     => '',
                    'year'      => null,
                    'vin'       => isset( $body['car_vin'] ) ? $body['car_vin'] : '',
                    'amount'    => isset( $item['total'] ) ? floatval( $item['total'] ) : ( ( floatval( $item['quantity'] ?? 1 ) ) * floatval( $item['unit_price'] ?? 0 ) ),
                    'paid'      => isset( $item['paid'] ) ? floatval( $item['paid'] ) : 0,
                );
            }
        }

        $items_total = 0;
        $items_paid  = 0;
        foreach ( $db_items as $it ) {
            $items_total += $it['amount'];
            $items_paid  += $it['paid'];
        }

        $dealer_fee  = floatval( $body['dealer_fee'] ?? 0 );
        $commission  = floatval( $body['commission'] ?? 0 );
        $amount_paid = floatval( $body['amount_paid'] ?? 0 );
        $subtotal    = $items_total + $dealer_fee + $commission;

        // Determine status
        $status = 'unpaid';
        if ( $subtotal > 0 && $amount_paid >= $subtotal ) {
            $status = 'paid';
        } elseif ( $amount_paid > 0 ) {
            $status = 'partially_paid';
        }
        if ( ! empty( $body['status'] ) ) {
            $status = sanitize_text_field( $body['status'] );
        }

        $invoice_id = Carspace_Invoice::create( array(
            'post_id'               => $post_id,
            'invoice_type'          => sanitize_text_field( $body['type'] ?? 'standard' ),
            'status'                => $status,
            'customer_type'         => '',
            'customer_name'         => sanitize_text_field( $body['customer_name'] ?? '' ),
            'customer_company_name' => '',
            'customer_personal_id'  => sanitize_text_field( $body['customer_phone'] ?? '' ),
            'company_ident_number'  => '',
            'invoice_date'          => ! empty( $body['due_date'] ) ? sanitize_text_field( $body['due_date'] ) : current_time( 'Y-m-d' ),
            'dealer_fee'            => $dealer_fee,
            'dealer_fee_note'       => sanitize_text_field( $body['notes'] ?? '' ),
            'commission'            => $commission,
            'subtotal'              => $subtotal,
            'amount_paid'           => $amount_paid,
            'owner_user_id'         => $user_id,
        ), $db_items );

        if ( ! $invoice_id ) {
            wp_delete_post( $post_id, true );
            return new WP_Error( 'create_failed', 'Failed to create invoice record', array( 'status' => 500 ) );
        }

        // Return the created invoice
        $get_req = new WP_REST_Request( 'GET', '/carspace/v1/invoices/' . $post_id );
        $get_req->set_param( 'id', $post_id );
        return self::get_invoice( $get_req );
    }

    /* ------------------------------------------------------------------
     * 4c. PUT /invoices/{id}
     * ----------------------------------------------------------------*/

    public static function update_invoice_endpoint( $request ) {
        $post_id = (int) $request->get_param( 'id' );
        $body    = $request->get_json_params();

        $invoice = Carspace_Invoice::find( $post_id );
        if ( ! $invoice ) {
            return new WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        // Access check — only managers can access all invoices; others need matching assigned VINs
        if ( ! self::is_manager() ) {
            $user_vins = self::get_user_assigned_vins( get_current_user_id() );
            $inv_items = Carspace_Invoice::get_items( $invoice->id );
            $invoice_vins = wp_list_pluck( $inv_items, 'vin' );
            if ( empty( array_intersect( $user_vins, $invoice_vins ) ) ) {
                return new WP_Error( 'forbidden', 'You do not have access to this invoice', array( 'status' => 403 ) );
            }
        }

        // Update items if provided
        if ( isset( $body['items'] ) && is_array( $body['items'] ) ) {
            $db_items = array();
            foreach ( $body['items'] as $item ) {
                $db_items[] = array(
                    'sale_date' => '',
                    'make'      => isset( $item['description'] ) ? $item['description'] : '',
                    'model'     => '',
                    'year'      => null,
                    'vin'       => isset( $body['car_vin'] ) ? $body['car_vin'] : '',
                    'amount'    => isset( $item['total'] ) ? floatval( $item['total'] ) : ( floatval( $item['quantity'] ?? 1 ) * floatval( $item['unit_price'] ?? 0 ) ),
                    'paid'      => isset( $item['paid'] ) ? floatval( $item['paid'] ) : 0,
                );
            }
            Carspace_Invoice::update_items( $post_id, $db_items );
        }

        // Build update data
        $update = array();

        if ( isset( $body['type'] ) )          $update['invoice_type'] = sanitize_text_field( $body['type'] );
        if ( isset( $body['status'] ) )        $update['status'] = sanitize_text_field( $body['status'] );
        if ( isset( $body['customer_name'] ) ) $update['customer_name'] = sanitize_text_field( $body['customer_name'] );
        if ( isset( $body['dealer_fee'] ) )    $update['dealer_fee'] = floatval( $body['dealer_fee'] );
        if ( isset( $body['commission'] ) )    $update['commission'] = floatval( $body['commission'] );
        if ( isset( $body['amount_paid'] ) )   $update['amount_paid'] = floatval( $body['amount_paid'] );
        if ( isset( $body['notes'] ) )         $update['dealer_fee_note'] = sanitize_text_field( $body['notes'] );
        if ( isset( $body['due_date'] ) )      $update['invoice_date'] = sanitize_text_field( $body['due_date'] );

        if ( ! empty( $update ) ) {
            Carspace_Invoice::update( $post_id, $update );
        }

        // Recalculate subtotal
        Carspace_Invoice::recalculate_subtotal( $post_id );

        // Return updated invoice
        $req = new WP_REST_Request( 'GET', '/carspace/v1/invoices/' . $post_id );
        $req->set_param( 'id', $post_id );
        return self::get_invoice( $req );
    }

    /* ------------------------------------------------------------------
     * 4d. DELETE /invoices/{id}
     * ----------------------------------------------------------------*/

    public static function delete_invoice( $request ) {
        global $wpdb;

        $post_id = (int) $request->get_param( 'id' );

        $invoice = Carspace_Invoice::find( $post_id );
        if ( ! $invoice ) {
            return new WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        // Access check — only managers can access all invoices; others need matching assigned VINs
        if ( ! self::is_manager() ) {
            $user_vins = self::get_user_assigned_vins( get_current_user_id() );
            $inv_items = Carspace_Invoice::get_items( $invoice->id );
            $invoice_vins = wp_list_pluck( $inv_items, 'vin' );
            if ( empty( array_intersect( $user_vins, $invoice_vins ) ) ) {
                return new WP_Error( 'forbidden', 'You do not have access to this invoice', array( 'status' => 403 ) );
            }
        }

        // Delete items
        $wpdb->delete( $wpdb->prefix . 'carspace_invoice_items', array( 'invoice_id' => $invoice->id ), array( '%d' ) );

        // Delete invoice row
        $wpdb->delete( $wpdb->prefix . 'carspace_invoices', array( 'post_id' => $post_id ), array( '%d' ) );

        // Delete CPT post
        wp_delete_post( $post_id, true );

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /* ------------------------------------------------------------------
     * 5. GET /notifications
     * ----------------------------------------------------------------*/

    public static function get_notifications( $request ) {
        $user_id  = get_current_user_id();
        $page     = max( 1, $request->get_param( 'page' ) );
        $per_page = min( 100, max( 1, $request->get_param( 'per_page' ) ) );
        $unread   = (bool) $request->get_param( 'unread' );

        $result = Carspace_Notification::get_for_user( $user_id, $unread, $per_page, $page );

        $items = array();
        foreach ( $result['items'] as $row ) {
            $items[] = self::hydrate_notification( $row );
        }

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => $result['total'],
            'total_pages' => max( 1, (int) ceil( $result['total'] / $per_page ) ),
            'page'        => $page,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 6. GET /notifications/unread-count
     * ----------------------------------------------------------------*/

    public static function get_unread_count() {
        return new WP_REST_Response( array(
            'count' => Carspace_Notification::count_unread( get_current_user_id() ),
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 7. POST /notifications/{id}/read
     * ----------------------------------------------------------------*/

    public static function mark_notification_read( $request ) {
        $id     = (int) $request->get_param( 'id' );
        $result = Carspace_Notification::mark_read( $id, get_current_user_id() );

        if ( ! $result ) {
            return new WP_Error( 'not_found', 'Notification not found or already read', array( 'status' => 404 ) );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /* ------------------------------------------------------------------
     * 8. POST /notifications/read-all
     * ----------------------------------------------------------------*/

    public static function mark_all_notifications_read() {
        $count = Carspace_Notification::mark_all_read( get_current_user_id() );

        return new WP_REST_Response( array(
            'success' => true,
            'updated' => $count,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 9. DELETE /notifications/{id}
     * ----------------------------------------------------------------*/

    public static function delete_notification( $request ) {
        $id     = (int) $request->get_param( 'id' );
        $result = Carspace_Notification::delete( $id, get_current_user_id() );

        if ( ! $result ) {
            return new WP_Error( 'not_found', 'Notification not found', array( 'status' => 404 ) );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /* ------------------------------------------------------------------
     * 9b. POST /notifications (admin — send notification)
     * ----------------------------------------------------------------*/

    public static function create_notification( $request ) {
        $body = $request->get_json_params();

        $title   = sanitize_text_field( $body['title'] ?? '' );
        $message = wp_kses_post( $body['message'] ?? '' );
        $type    = sanitize_text_field( $body['type'] ?? 'info' );
        $link    = isset( $body['link'] ) ? esc_url_raw( $body['link'] ) : '';

        if ( empty( $title ) ) {
            return new WP_Error( 'missing_title', 'Title is required.', array( 'status' => 400 ) );
        }

        // user_ids: array of IDs, or empty/null for all users
        $user_ids = array();
        if ( ! empty( $body['user_ids'] ) && is_array( $body['user_ids'] ) ) {
            $user_ids = array_map( 'intval', $body['user_ids'] );
        } else {
            // Send to all users with dashboard roles
            $wp_users = get_users( array(
                'role__in' => array( 'administrator', 'shop_manager', 'customer', 'subscriber' ),
                'fields'   => 'ID',
            ) );
            $user_ids = array_map( 'intval', $wp_users );
        }

        if ( empty( $user_ids ) ) {
            return new WP_Error( 'no_users', 'No users to notify.', array( 'status' => 400 ) );
        }

        $count = Carspace_Notification::create( $user_ids, $title, $message, $type, $link );

        return new WP_REST_Response( array(
            'success'    => true,
            'recipients' => is_wp_error( $count ) ? 0 : $count,
        ), 201 );
    }

    /* ------------------------------------------------------------------
     * 9c. GET /notifications/all (admin — sent archive)
     * ----------------------------------------------------------------*/

    public static function get_all_notifications_admin( $request ) {
        $page     = max( 1, (int) $request->get_param( 'page' ) );
        $per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $search   = $request->get_param( 'search' ) ?? '';

        $result = Carspace_Notification::get_all_admin( $per_page, $page, $search );

        $items = array();
        foreach ( $result['items'] as $row ) {
            $items[] = array(
                'id'              => (int) $row->id,
                'title'           => $row->title,
                'message'         => $row->message,
                'type'            => $row->type,
                'link'            => $row->link,
                'created_at'      => $row->created_at,
                'recipient_count' => (int) $row->recipient_count,
                'read_count'      => (int) $row->read_count,
                'all_ids'         => array_map( 'intval', explode( ',', $row->all_ids ) ),
            );
        }

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => $result['total'],
            'total_pages' => max( 1, (int) ceil( $result['total'] / $per_page ) ),
            'page'        => $page,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 9d. POST /notifications/bulk-delete (admin)
     * ----------------------------------------------------------------*/

    public static function bulk_delete_notifications( $request ) {
        $body = $request->get_json_params();
        $ids  = isset( $body['ids'] ) && is_array( $body['ids'] ) ? $body['ids'] : array();

        if ( empty( $ids ) ) {
            return new WP_Error( 'missing_ids', 'No notification IDs provided.', array( 'status' => 400 ) );
        }

        $count = Carspace_Notification::bulk_delete( $ids );

        return new WP_REST_Response( array(
            'success' => true,
            'deleted' => $count,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 9e. GET/PUT /notifications/settings (admin)
     * ----------------------------------------------------------------*/

    public static function get_notification_settings() {
        return new WP_REST_Response( array(
            'cleanup_months' => (int) get_option( 'carspace_notification_cleanup_months', 5 ),
        ), 200 );
    }

    public static function update_notification_settings( $request ) {
        $body   = $request->get_json_params();
        $months = isset( $body['cleanup_months'] ) ? max( 0, (int) $body['cleanup_months'] ) : 5;

        update_option( 'carspace_notification_cleanup_months', $months );

        return new WP_REST_Response( array(
            'success'        => true,
            'cleanup_months' => $months,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 10. GET /users (admin only)
     * ----------------------------------------------------------------*/

    public static function get_users( $request ) {
        global $wpdb;

        $cache_key = 'cs_users_v' . self::cache_version();
        $cached    = self::cache_get( $cache_key );
        if ( $cached !== false ) {
            return new WP_REST_Response( $cached, 200 );
        }

        // Fetch users with relevant roles
        $wp_users = get_users( array(
            'role__in' => array( 'administrator', 'editor', 'shop_manager', 'subscriber', 'customer' ),
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'number'   => 500,
        ) );

        if ( empty( $wp_users ) ) {
            return new WP_REST_Response( array(), 200 );
        }

        $user_ids = wp_list_pluck( $wp_users, 'ID' );

        // Batch: assigned cars count per user
        $placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

        $assigned_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT meta_value AS user_id, COUNT(*) AS cnt
             FROM {$wpdb->postmeta}
             WHERE meta_key = 'assigned_user' AND meta_value IN ({$placeholders})
             GROUP BY meta_value",
            $user_ids
        ) );
        $assigned_map = array();
        foreach ( $assigned_rows as $r ) {
            $assigned_map[ (int) $r->user_id ] = (int) $r->cnt;
        }

        // Batch: invoice stats per user
        $inv_table = $wpdb->prefix . 'carspace_invoices';
        $inv_rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT owner_user_id,
                    COUNT(*) AS invoice_count,
                    COALESCE(SUM(subtotal), 0) AS total_revenue
             FROM {$inv_table}
             WHERE owner_user_id IN ({$placeholders})
             GROUP BY owner_user_id",
            $user_ids
        ) );
        $inv_map = array();
        foreach ( $inv_rows as $r ) {
            $inv_map[ (int) $r->owner_user_id ] = array(
                'invoice_count' => (int) $r->invoice_count,
                'total_revenue' => (float) $r->total_revenue,
            );
        }

        // Batch: user price tiers (from custom table, not wp_usermeta)
        $tiers_map = Carspace_Transport_Price::batch_user_tiers( $user_ids );

        $items = array();
        foreach ( $wp_users as $u ) {
            $uid  = (int) $u->ID;
            $role = self::get_user_role( $u );

            $inv  = isset( $inv_map[ $uid ] ) ? $inv_map[ $uid ] : array( 'invoice_count' => 0, 'total_revenue' => 0 );

            $items[] = array(
                'id'            => (string) $uid,
                'name'          => $u->display_name,
                'email'         => $u->user_email,
                'phone'         => get_user_meta( $uid, 'billing_phone', true ) ?: '',
                'avatar'        => get_avatar_url( $uid, array( 'size' => 96 ) ),
                'role'          => $role,
                'assigned_cars' => isset( $assigned_map[ $uid ] ) ? $assigned_map[ $uid ] : 0,
                'total_revenue' => $inv['total_revenue'],
                'invoice_count' => $inv['invoice_count'],
                'active'        => true,
                'created_at'    => $u->user_registered,
                'price_tier'    => isset( $tiers_map[ $uid ] ) ? $tiers_map[ $uid ] : 'base_price',
            );
        }

        self::cache_set( $cache_key, $items );

        return new WP_REST_Response( $items, 200 );
    }

    /* ------------------------------------------------------------------
     * 11. GET /dashboard/stats
     * ----------------------------------------------------------------*/

    public static function get_dashboard_stats( $request ) {
        $user_id   = get_current_user_id();
        $cache_key = 'cs_stats_v' . self::cache_version() . '_u' . $user_id;
        $cached    = self::cache_get( $cache_key );
        if ( $cached !== false ) {
            return new WP_REST_Response( $cached, 200 );
        }

        // All users see only their assigned cars
        $posts = carspace_get_user_assigned_cars( $user_id );
        $assigned_products = wp_list_pluck( $posts, 'ID' );

        $product_ids = array_map( 'intval', $assigned_products );
        $total_cars  = count( $product_ids );

        // Initialise counters
        $delivered        = 0;
        $not_delivered    = 0;
        $booking_container = 0;
        $loaded_container  = 0;
        $not_loaded        = 0;

        if ( ! empty( $product_ids ) ) {
            // Batch prime caches
            update_meta_cache( 'post', $product_ids );
            update_object_term_cache( $product_ids, 'product' );

            // Batch load WC products
            $wc_products = wc_get_products( array(
                'include' => $product_ids,
                'limit'   => count( $product_ids ),
                'return'  => 'objects',
            ) );
            $wc_map = array();
            foreach ( $wc_products as $p ) {
                $wc_map[ $p->get_id() ] = $p;
            }

            // Batch port-images check
            $port_map = Carspace_Port_Images::batch_check( $product_ids );

            foreach ( $product_ids as $pid ) {
                $product = isset( $wc_map[ $pid ] ) ? $wc_map[ $pid ] : null;
                if ( ! $product ) {
                    continue;
                }

                $is_delivered = ! empty( $port_map[ $pid ] );
                if ( $is_delivered ) {
                    $delivered++;
                    continue;
                }

                $has_container = ! empty( $product->get_attribute( 'container-number' ) );
                if ( $has_container ) {
                    $loaded_container++;
                    continue;
                }

                $has_booking = ! empty( $product->get_attribute( 'booking-number' ) );
                if ( $has_booking ) {
                    $booking_container++;
                    continue;
                }

                $has_featured = (bool) $product->get_image_id();
                $has_gallery  = ! empty( $product->get_gallery_image_ids() );

                if ( ! $has_featured && ! $has_gallery ) {
                    $not_delivered++;
                } elseif ( $has_featured ) {
                    $not_loaded++;
                }
            }
        }

        // Invoice stats — managers see all, others see only invoices matching their assigned cars' VINs
        global $wpdb;
        $inv_table = $wpdb->prefix . 'carspace_invoices';
        $itm_table = $wpdb->prefix . 'carspace_invoice_items';

        // For non-managers: get matching invoice IDs via VIN lookup
        $is_mgr       = self::is_manager();
        $inv_id_where = '';
        if ( ! $is_mgr ) {
            $user_vins = array();
            if ( ! empty( $product_ids ) ) {
                $pid_list = implode( ',', $product_ids );
                $user_vins = $wpdb->get_col(
                    "SELECT meta_value FROM {$wpdb->postmeta}
                     WHERE post_id IN ({$pid_list}) AND meta_key = '_sku' AND meta_value != ''"
                );
            }

            // Lowercase the SKU values so the query matches it.vin_lower
            // and benefits from idx_vin_lower_invoice. Filter empties.
            $user_vins_lower = array();
            foreach ( $user_vins as $v ) {
                $v = strtolower( trim( $v ) );
                if ( $v !== '' ) {
                    $user_vins_lower[] = $v;
                }
            }

            if ( ! empty( $user_vins_lower ) ) {
                $vin_placeholders = implode( ',', array_fill( 0, count( $user_vins_lower ), '%s' ) );
                $matched_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT DISTINCT invoice_id FROM {$itm_table} WHERE vin_lower IN ({$vin_placeholders})",
                    $user_vins_lower
                ) );
            } else {
                $matched_ids = array();
            }

            if ( ! empty( $matched_ids ) ) {
                $ids_str = implode( ',', array_map( 'intval', $matched_ids ) );
                $inv_id_where = " AND i.id IN ({$ids_str})";
            } else {
                $inv_id_where = ' AND 1=0';
            }
        }

        // Total invoices
        $total_invoices = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$inv_table} i WHERE 1=1{$inv_id_where}"
        );

        // Paid / unpaid balances
        $balance_sql = "SELECT i.status,
                               COALESCE(SUM(it_sum.item_total), 0) + COALESCE(SUM(i.dealer_fee), 0) + COALESCE(SUM(i.commission), 0) AS balance
                        FROM {$inv_table} i
                        LEFT JOIN (
                            SELECT invoice_id, SUM(amount) AS item_total
                            FROM {$itm_table}
                            GROUP BY invoice_id
                        ) it_sum ON it_sum.invoice_id = i.id
                        WHERE 1=1{$inv_id_where}
                        GROUP BY i.status";

        $balance_rows = $wpdb->get_results( $balance_sql );

        $paid_balance   = 0;
        $unpaid_balance = 0;
        foreach ( $balance_rows as $br ) {
            if ( $br->status === 'paid' ) {
                $paid_balance = (float) $br->balance;
            } else {
                $unpaid_balance += (float) $br->balance;
            }
        }

        $balance_delta = $paid_balance - $unpaid_balance;

        // Invoice status counts
        $status_sql = "SELECT i.status, COUNT(*) AS cnt FROM {$inv_table} i WHERE 1=1{$inv_id_where} GROUP BY i.status";
        $status_rows = $wpdb->get_results( $status_sql );

        $invoice_status_counts = array( 'paid' => 0, 'partially_paid' => 0, 'unpaid' => 0, 'cancelled' => 0 );
        foreach ( $status_rows as $sr ) {
            if ( isset( $invoice_status_counts[ $sr->status ] ) ) {
                $invoice_status_counts[ $sr->status ] = (int) $sr->cnt;
            }
        }

        // Monthly revenue (last 6 months)
        $monthly_sql = "SELECT DATE_FORMAT(p.post_date, '%%Y-%%m') AS month_key,
                               SUM(COALESCE(it_sum.item_total, 0) + COALESCE(i.dealer_fee, 0) + COALESCE(i.commission, 0)) AS revenue,
                               SUM(i.amount_paid) AS paid
                        FROM {$inv_table} i
                        LEFT JOIN {$wpdb->posts} p ON p.ID = i.post_id
                        LEFT JOIN (
                            SELECT invoice_id, SUM(amount) AS item_total FROM {$itm_table} GROUP BY invoice_id
                        ) it_sum ON it_sum.invoice_id = i.id
                        WHERE i.status != 'cancelled' AND p.post_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH){$inv_id_where}
                        GROUP BY month_key
                        ORDER BY month_key ASC";
        $monthly_rows = $wpdb->get_results( $monthly_sql );

        $monthly_revenue = array();
        foreach ( $monthly_rows as $mr ) {
            $monthly_revenue[] = array(
                'month'   => $mr->month_key,
                'revenue' => round( (float) $mr->revenue, 2 ),
                'paid'    => round( (float) $mr->paid, 2 ),
            );
        }

        // Recent 5 invoices
        $recent_sql = "SELECT i.*, p.post_title, p.post_date, p.post_modified
                       FROM {$inv_table} i
                       LEFT JOIN {$wpdb->posts} p ON p.ID = i.post_id
                       WHERE 1=1{$inv_id_where}
                       ORDER BY i.id DESC
                       LIMIT 5";
        $recent_rows = $wpdb->get_results( $recent_sql );

        $recent_invoices = array();
        if ( ! empty( $recent_rows ) ) {
            $recent_inv_ids = wp_list_pluck( $recent_rows, 'id' );
            $recent_items_map = self::batch_invoice_items( $recent_inv_ids );
            $recent_author_ids = array_unique( array_filter( wp_list_pluck( $recent_rows, 'owner_user_id' ) ) );
            $recent_authors = self::batch_users( $recent_author_ids );

            // Batch SKU→product_id for recent invoice VINs
            $recent_vins = array();
            foreach ( $recent_items_map as $inv_items ) {
                foreach ( $inv_items as $it ) {
                    if ( ! empty( $it->vin ) ) $recent_vins[] = $it->vin;
                }
            }
            $recent_sku_map = ! empty( $recent_vins ) ? self::batch_sku_to_product_id( $recent_vins ) : array();

            foreach ( $recent_rows as $rr ) {
                $recent_invoices[] = self::hydrate_invoice( $rr, $recent_items_map, $recent_authors, $recent_sku_map );
            }
        }

        $stats_data = array(
            'total_cars'            => $total_cars,
            'delivered'             => $delivered,
            'not_delivered'         => $not_delivered,
            'booking_container'     => $booking_container,
            'loaded_container'      => $loaded_container,
            'not_loaded'            => $not_loaded,
            'total_invoices'        => $total_invoices,
            'paid_balance'          => round( $paid_balance, 2 ),
            'unpaid_balance'        => round( $unpaid_balance, 2 ),
            'balance_delta'         => round( $balance_delta, 2 ),
            'invoice_status_counts' => $invoice_status_counts,
            'monthly_revenue'       => $monthly_revenue,
            'recent_invoices'       => $recent_invoices,
        );

        self::cache_set( $cache_key, $stats_data );

        return new WP_REST_Response( $stats_data, 200 );
    }

    /* ==================================================================
     * HYDRATION HELPERS
     * ================================================================*/

    /**
     * Get a WC product attribute value, returning empty string if absent.
     */
    private static function get_attr( $product, $slug, $alt_slugs = array() ) {
        // Try primary slug
        $val = $product->get_attribute( $slug );
        if ( $val !== '' ) {
            return $val;
        }
        // Try display name form (e.g. 'Auction Name')
        $name = ucwords( str_replace( '-', ' ', $slug ) );
        $val  = $product->get_attribute( $name );
        if ( $val !== '' ) {
            return $val;
        }
        // Try alternate slugs
        foreach ( $alt_slugs as $alt ) {
            $val = $product->get_attribute( $alt );
            if ( $val !== '' ) {
                return $val;
            }
        }
        return '';
    }

    /**
     * Compute car status from product state.
     */
    private static function compute_car_status( $product, $has_port_images ) {
        if ( $has_port_images ) {
            return 'delivered';
        }
        if ( ! empty( $product->get_attribute( 'container-number' ) ) ) {
            return 'loaded_container';
        }
        if ( ! empty( $product->get_attribute( 'booking-number' ) ) ) {
            return 'booking_container';
        }
        if ( (bool) $product->get_image_id() ) {
            return 'not_loaded';
        }
        return 'not_delivered';
    }

    /**
     * Parse year, make, model from product title.
     * Expects format like "2024 Toyota Camry" or similar.
     */
    private static function parse_title( $title ) {
        $result = array( 'year' => 0, 'make' => '', 'model' => '' );

        if ( preg_match( '/^(\d{4})\s+(\S+)\s+(.+)$/u', trim( $title ), $m ) ) {
            $result['year']  = (int) $m[1];
            $result['make']  = $m[2];
            $result['model'] = $m[3];
        }

        return $result;
    }

    /**
     * Hydrate a single Car response from a WC_Product.
     */
    private static function hydrate_car( $product, $port_images_map, $port_images_data, $vin_lookup, $dealers_map = array(), $att_url_map = array() ) {
        $pid = $product->get_id();
        $vin = $product->get_sku();

        // Year / make / model: try attributes first, then parse title
        $attr_year  = self::get_attr( $product, 'year' );
        $attr_make  = self::get_attr( $product, 'make' );
        $attr_model = self::get_attr( $product, 'model' );

        $parsed = self::parse_title( $product->get_name() );

        $year  = $attr_year  ? (int) $attr_year  : $parsed['year'];
        $make  = $attr_make  ?: $parsed['make'];
        $model = $attr_model ?: $parsed['model'];

        // Featured image (from pre-fetched URL map)
        $featured_id  = $product->get_image_id();
        $featured_url = $featured_id && isset( $att_url_map[ (int) $featured_id ] ) ? $att_url_map[ (int) $featured_id ] : '';

        // Gallery images (from pre-fetched URL map)
        $gallery_ids  = $product->get_gallery_image_ids();
        $gallery_urls = array();
        foreach ( $gallery_ids as $gid ) {
            if ( isset( $att_url_map[ (int) $gid ] ) ) {
                $gallery_urls[] = $att_url_map[ (int) $gid ];
            }
        }

        // Port images (from pre-fetched URL map)
        $has_port = ! empty( $port_images_map[ $pid ] );
        $port_urls = array();
        if ( isset( $port_images_data[ $pid ] ) ) {
            foreach ( $port_images_data[ $pid ] as $att_id ) {
                if ( isset( $att_url_map[ (int) $att_id ] ) ) {
                    $port_urls[] = $att_url_map[ (int) $att_id ];
                }
            }
        }

        // Prices — regular price IS the transport price (set via "Update Price" button)
        // Car price comes from "Price ($)" attribute (auction/purchase price)
        $transport_price = (float) $product->get_regular_price();
        $car_price       = (float) self::get_attr( $product, 'price' );
        $total_price     = $car_price + $transport_price;

        // Receiver info
        $receiver_name = get_post_meta( $pid, '_receiver_name', true ) ?: '';
        $receiver_phone = get_post_meta( $pid, '_receiver_personal_id', true ) ?: '';

        // Buyer info from batch VIN lookup
        $vin_lower   = strtolower( trim( $vin ) );
        $buyer_name  = isset( $vin_lookup['buyers'][ $vin_lower ] ) ? $vin_lookup['buyers'][ $vin_lower ] : '';
        $buyer_phone = '';

        // Invoice IDs for this car
        $invoice_ids = array();
        if ( isset( $vin_lookup['invoices'][ $vin_lower ] ) ) {
            foreach ( $vin_lookup['invoices'][ $vin_lower ] as $inv ) {
                $invoice_ids[] = (string) $inv['ID'];
            }
            $invoice_ids = array_unique( $invoice_ids );
        }

        // Assigned dealer
        $assigned_dealer_id = get_post_meta( $pid, 'assigned_user', true ) ?: '';
        $assigned_dealer_name = '';
        if ( $assigned_dealer_id && isset( $dealers_map[ (int) $assigned_dealer_id ] ) ) {
            $assigned_dealer_name = $dealers_map[ (int) $assigned_dealer_id ]['name'];
        }

        // Purchase date
        $purchase_date = self::get_attr( $product, 'purchase-date' ) ?: get_post_meta( $pid, '_purchase_date', true ) ?: '';

        // Notes (get_post uses WP object cache — no extra query if meta cache is primed)
        $post = get_post( $pid );
        $notes = $post ? $post->post_excerpt : '';

        // Shipping info from WC product attributes
        // Attribute names match the WC product edit screen:
        //   Pickup Date, Delivery Date (=warehouse), Loading Date,
        //   Departure Date (=send), Arrival Date, Container Number,
        //   Booking Number, Tracking Link, Shipline Name,
        //   Auction Name, Auction City, Lot Number
        $shipping = array(
            'auction'            => self::get_attr( $product, 'auction-name', array( 'auction' ) ),
            'auction_city'       => self::get_attr( $product, 'auction-city' ),
            'lot_number'         => self::get_attr( $product, 'lot-number' ),
            'pickup_date'        => self::get_attr( $product, 'pickup-date' ),
            'warehouse_date'     => self::get_attr( $product, 'delivery-date' ),
            'loading_date'       => self::get_attr( $product, 'loading-date' ),
            'send_date'          => self::get_attr( $product, 'departure-date' ),
            'booking_number'     => self::get_attr( $product, 'booking-number' ),
            'container_number'   => self::get_attr( $product, 'container-number' ),
            'shipline_name'      => self::get_attr( $product, 'shipline-name' ),
            'tracking_url'       => self::get_attr( $product, 'tracking-link' ),
            'loading_port'       => self::get_attr( $product, 'loading-port' ),
            'destination_port'   => self::get_attr( $product, 'destination-port' ),
            'estimated_arrival'  => self::get_attr( $product, 'arrival-date' ),
            'actual_arrival'     => '',
        );

        $status = self::compute_car_status( $product, $has_port );

        return array(
            'id'                 => (string) $pid,
            'title'              => $product->get_name(),
            'vin'                => $vin,
            'year'               => $year,
            'make'               => $make,
            'model'              => $model,
            'color'              => self::get_attr( $product, 'color' ),
            'mileage'            => (int) get_post_meta( $pid, '_mileage', true ),
            'car_price'          => $car_price,
            'transport_price'    => $transport_price,
            'total_price'        => $total_price,
            'featured_image'     => $featured_url,
            'gallery_images'     => $gallery_urls,
            'port_images'        => $port_urls,
            'buyer_name'         => $buyer_name,
            'buyer_phone'        => $buyer_phone,
            'receiver_name'      => $receiver_name,
            'receiver_phone'     => $receiver_phone,
            'assigned_dealer_id'   => (string) $assigned_dealer_id,
            'assigned_dealer_name' => $assigned_dealer_name,
            'shipping'           => $shipping,
            'invoice_ids'        => array_values( $invoice_ids ),
            'purchase_date'      => $purchase_date,
            'notes'              => $notes,
            'status'             => $status,
            'created_at'         => $post ? $post->post_date : '',
            'updated_at'         => $post ? $post->post_modified : '',
        );
    }

    /**
     * Hydrate a single Invoice response.
     */
    private static function hydrate_invoice( $row, $items_map, $authors, $sku_map = array() ) {
        $invoice_id = (int) $row->id;
        $post_id    = (int) $row->post_id;

        // Items
        $raw_items = isset( $items_map[ $invoice_id ] ) ? $items_map[ $invoice_id ] : array();
        $items     = array();
        $car_vin   = '';
        $car_title = '';

        foreach ( $raw_items as $it ) {
            $items[] = array(
                'id'        => isset( $it->id ) ? (string) $it->id : '',
                'sale_date' => isset( $it->sale_date ) ? $it->sale_date : '',
                'make'      => isset( $it->make ) ? $it->make : '',
                'model'     => isset( $it->model ) ? $it->model : '',
                'year'      => isset( $it->year ) ? (int) $it->year : 0,
                'vin'       => isset( $it->vin ) ? $it->vin : '',
                'amount'    => isset( $it->amount ) ? (float) $it->amount : 0,
                'paid'      => isset( $it->paid ) ? (float) $it->paid : 0,
            );

            // Use first item's VIN as the car_vin
            if ( ! $car_vin && ! empty( $it->vin ) ) {
                $car_vin = $it->vin;
            }
        }

        // Compute totals
        $items_total = 0;
        $items_paid  = 0;
        foreach ( $raw_items as $it ) {
            $items_total += (float) ( $it->amount ?? 0 );
            $items_paid  += (float) ( $it->paid ?? 0 );
        }

        $dealer_fee  = (float) ( $row->dealer_fee ?? 0 );
        $commission  = (float) ( $row->commission ?? 0 );
        $subtotal    = (float) ( $row->subtotal ?? 0 );
        $amount_paid = (float) ( $row->amount_paid ?? 0 );
        $total       = $items_total + $dealer_fee + $commission;
        $balance_due = $total - $amount_paid;

        // Customer display name
        $customer_name = '';
        if ( isset( $row->customer_type ) && $row->customer_type === 'Company' && ! empty( $row->customer_company_name ) ) {
            $customer_name = $row->customer_company_name;
        } elseif ( ! empty( $row->customer_name ) ) {
            $customer_name = $row->customer_name;
        }

        // Author
        $author_id   = (int) ( $row->owner_user_id ?? 0 );
        $author_name = isset( $authors[ $author_id ] ) ? $authors[ $author_id ]['name'] : '';

        // Car ID: use pre-fetched SKU→product_id map (falls back to individual lookup)
        $car_id = '';
        if ( $car_vin ) {
            $vin_lower = strtolower( trim( $car_vin ) );
            $product_id = isset( $sku_map[ $vin_lower ] ) ? $sku_map[ $vin_lower ] : wc_get_product_id_by_sku( $car_vin );
            if ( $product_id ) {
                $car_id    = (string) $product_id;
                $car_title = get_the_title( $product_id );
            }
        }

        // Receipt image
        $receipt_image = '';
        if ( ! empty( $row->receipt_image_url ) ) {
            $receipt_image = $row->receipt_image_url;
        } elseif ( ! empty( $row->receipt_image_id ) ) {
            $receipt_image = wp_get_attachment_url( (int) $row->receipt_image_id ) ?: '';
        }

        return array(
            'id'               => (string) $post_id,
            'invoice_number'   => isset( $row->post_title ) ? $row->post_title : '',
            'type'             => $row->invoice_type ?? '',
            'status'           => $row->status ?? 'unpaid',
            'car_id'           => $car_id,
            'car_vin'          => $car_vin,
            'car_title'        => $car_title,
            'customer_name'    => $customer_name,
            'customer_email'   => '',
            'customer_phone'   => '',
            'items'            => $items,
            'subtotal'         => round( $subtotal, 2 ),
            'dealer_fee'       => round( $dealer_fee, 2 ),
            'dealer_fee_paid'  => 0,
            'commission'       => round( $commission, 2 ),
            'commission_paid'  => 0,
            'total'            => round( $total, 2 ),
            'amount_paid'      => round( $amount_paid, 2 ),
            'balance_due'      => round( $balance_due, 2 ),
            'receipt_image'    => $receipt_image,
            'notes'            => $row->dealer_fee_note ?? '',
            'created_at'       => $row->post_date ?? '',
            'due_date'         => $row->invoice_date ?? '',
            'paid_at'          => ( ( $row->status ?? '' ) === 'paid' && ! empty( $row->post_modified ) ) ? $row->post_modified : '',
            'author_id'        => (string) $author_id,
            'author_name'      => $author_name,
        );
    }

    /**
     * Hydrate a notification row.
     */
    private static function hydrate_notification( $row ) {
        // Try to resolve actor from the notification (if there is a pattern)
        $actor_name   = '';
        $actor_avatar = '';

        return array(
            'id'           => (string) $row->id,
            'type'         => $row->type ?? 'info',
            'title'        => $row->title ?? '',
            'message'      => $row->message ?? '',
            'read'         => ( $row->status ?? 'unread' ) === 'read',
            'link'         => $row->link ?? '',
            'user_id'      => (string) ( $row->user_id ?? 0 ),
            'created_at'   => $row->created_at ?? '',
            'actor_name'   => $actor_name,
            'actor_avatar' => $actor_avatar,
        );
    }

    /* ==================================================================
     * BATCH HELPERS
     * ================================================================*/

    /**
     * Batch-fetch invoice items for multiple invoice IDs.
     *
     * @param  array $invoice_ids  Array of carspace_invoices.id values.
     * @return array               [invoice_id => [item_row, ...]]
     */
    private static function batch_invoice_items( $invoice_ids ) {
        global $wpdb;

        $map = array();
        if ( empty( $invoice_ids ) ) {
            return $map;
        }

        $ids          = array_map( 'intval', $invoice_ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_invoice_items WHERE invoice_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC",
            $ids
        ) );

        foreach ( $rows as $r ) {
            $map[ (int) $r->invoice_id ][] = $r;
        }

        return $map;
    }

    /**
     * Batch-fetch port image attachment IDs for multiple product IDs.
     *
     * @param  array $product_ids
     * @return array [product_id => [attachment_id, ...]]
     */
    private static function batch_port_images( $product_ids ) {
        global $wpdb;

        $map = array();
        if ( empty( $product_ids ) ) {
            return $map;
        }

        $ids          = array_map( 'intval', $product_ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT product_id, attachment_id FROM {$wpdb->prefix}carspace_port_images WHERE product_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC",
            $ids
        ) );

        foreach ( $rows as $r ) {
            $map[ (int) $r->product_id ][] = (int) $r->attachment_id;
        }

        return $map;
    }

    /**
     * Batch-fetch basic user data.
     *
     * @param  array $user_ids
     * @return array [user_id => ['name' => ..., 'avatar' => ...]]
     */
    /* ------------------------------------------------------------------
     * Tickets
     * ----------------------------------------------------------------*/

    public static function get_tickets( $request ) {
        $user_id = get_current_user_id();
        $role    = self::get_user_role();

        $args = array(
            'per_page' => min( 100, $request->get_param( 'per_page' ) ?: 20 ),
            'page'     => $request->get_param( 'page' ) ?: 1,
            'status'   => $request->get_param( 'status' ) ?: '',
            'search'   => $request->get_param( 'search' ) ?: '',
        );

        // Dealers see only their own tickets
        if ( $role === 'dealer' ) {
            $args['author_id'] = $user_id;
        }

        $result = Carspace_Ticket::list( $args );

        // Batch-fetch author info
        $author_ids = array_unique( array_filter( wp_list_pluck( $result['items'], 'author_id' ) ) );
        $authors = self::batch_users( $author_ids );

        // Count last message for each ticket
        $items = array();
        foreach ( $result['items'] as $row ) {
            $items[] = self::format_ticket_row( $row, $authors );
        }

        return new WP_REST_Response( array(
            'items'       => $items,
            'total'       => $result['total'],
            'page'        => $result['page'],
            'total_pages' => $result['total_pages'],
        ), 200 );
    }

    public static function get_ticket( $request ) {
        $id      = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();
        $role    = self::get_user_role();

        $ticket = Carspace_Ticket::find( $id );
        if ( ! $ticket ) {
            return new WP_REST_Response( array( 'message' => 'Ticket not found.' ), 404 );
        }

        // Dealers can only view their own tickets
        if ( $role === 'dealer' && (int) $ticket->author_id !== $user_id ) {
            return new WP_REST_Response( array( 'message' => 'Access denied.' ), 403 );
        }

        $messages = Carspace_Ticket::get_messages( $id );

        // Batch-fetch all authors (ticket + messages)
        $all_author_ids = array_merge(
            array( (int) $ticket->author_id ),
            array_map( 'intval', wp_list_pluck( $messages, 'author_id' ) )
        );
        if ( $ticket->assigned_to ) {
            $all_author_ids[] = (int) $ticket->assigned_to;
        }
        $authors = self::batch_users( array_unique( array_filter( $all_author_ids ) ) );

        $formatted = self::format_ticket_row( $ticket, $authors );
        $formatted['messages'] = array();

        foreach ( $messages as $msg ) {
            $aid = (int) $msg->author_id;
            $author = isset( $authors[ $aid ] ) ? $authors[ $aid ] : array( 'name' => 'Unknown', 'avatar' => '' );

            // Determine role
            $wp_user = get_user_by( 'id', $aid );
            $msg_role = 'dealer';
            if ( $wp_user ) {
                if ( in_array( 'administrator', (array) $wp_user->roles, true ) ) {
                    $msg_role = 'admin';
                } elseif ( array_intersect( array( 'editor', 'shop_manager' ), (array) $wp_user->roles ) ) {
                    $msg_role = 'manager';
                }
            }

            $formatted['messages'][] = array(
                'id'          => (string) $msg->id,
                'ticket_id'   => (string) $msg->ticket_id,
                'author_id'   => (string) $aid,
                'author_name' => $author['name'],
                'author_avatar' => $author['avatar'],
                'author_role' => $msg_role,
                'content'     => $msg->content,
                'attachments' => array(),
                'created_at'  => $msg->created_at,
            );
        }

        return new WP_REST_Response( $formatted, 200 );
    }

    public static function create_ticket( $request ) {
        $data    = $request->get_json_params();
        $user_id = get_current_user_id();

        if ( empty( $data['subject'] ) || empty( $data['content'] ) ) {
            return new WP_REST_Response( array( 'message' => 'Subject and message are required.' ), 400 );
        }

        $ticket_id = Carspace_Ticket::create( array(
            'subject'   => $data['subject'],
            'priority'  => $data['priority'] ?? 'medium',
            'category'  => $data['category'] ?? 'general',
            'author_id' => $user_id,
        ) );

        if ( ! $ticket_id ) {
            return new WP_REST_Response( array( 'message' => 'Failed to create ticket.' ), 500 );
        }

        // Add the initial message
        Carspace_Ticket::add_message( $ticket_id, $user_id, $data['content'] );

        // Notify admins about new ticket
        $admin_users = get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) );
        if ( ! empty( $admin_users ) ) {
            $user = wp_get_current_user();
            Carspace_Notification::create(
                $admin_users,
                sprintf( 'New support ticket: %s', $data['subject'] ),
                sprintf( '%s opened a new support ticket.', $user->display_name ),
                'system'
            );
        }

        $ticket = Carspace_Ticket::find( $ticket_id );
        $authors = self::batch_users( array( $user_id ) );
        $formatted = self::format_ticket_row( $ticket, $authors );
        $formatted['messages'] = array();

        $messages = Carspace_Ticket::get_messages( $ticket_id );
        foreach ( $messages as $msg ) {
            $aid = (int) $msg->author_id;
            $author = isset( $authors[ $aid ] ) ? $authors[ $aid ] : array( 'name' => 'Unknown', 'avatar' => '' );
            $formatted['messages'][] = array(
                'id'            => (string) $msg->id,
                'ticket_id'     => (string) $msg->ticket_id,
                'author_id'     => (string) $aid,
                'author_name'   => $author['name'],
                'author_avatar' => $author['avatar'],
                'author_role'   => self::get_user_role( get_user_by( 'id', $aid ) ),
                'content'       => $msg->content,
                'attachments'   => array(),
                'created_at'    => $msg->created_at,
            );
        }

        return new WP_REST_Response( $formatted, 201 );
    }

    public static function update_ticket( $request ) {
        $id      = (int) $request->get_param( 'id' );
        $data    = $request->get_json_params();
        $user_id = get_current_user_id();
        $role    = self::get_user_role();

        $ticket = Carspace_Ticket::find( $id );
        if ( ! $ticket ) {
            return new WP_REST_Response( array( 'message' => 'Ticket not found.' ), 404 );
        }

        // Dealers can only close their own tickets
        if ( $role === 'dealer' ) {
            if ( (int) $ticket->author_id !== $user_id ) {
                return new WP_REST_Response( array( 'message' => 'Access denied.' ), 403 );
            }
            // Dealers can only change status to 'closed'
            $data = array_intersect_key( $data, array( 'status' => '' ) );
            if ( isset( $data['status'] ) && $data['status'] !== 'closed' ) {
                return new WP_REST_Response( array( 'message' => 'Dealers can only close tickets.' ), 403 );
            }
        }

        Carspace_Ticket::update( $id, $data );

        $ticket = Carspace_Ticket::find( $id );
        $authors = self::batch_users( array( (int) $ticket->author_id ) );

        return new WP_REST_Response( self::format_ticket_row( $ticket, $authors ), 200 );
    }

    public static function delete_ticket( $request ) {
        $id = (int) $request->get_param( 'id' );

        $ticket = Carspace_Ticket::find( $id );
        if ( ! $ticket ) {
            return new WP_REST_Response( array( 'message' => 'Ticket not found.' ), 404 );
        }

        Carspace_Ticket::delete( $id );

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public static function reply_ticket( $request ) {
        $id      = (int) $request->get_param( 'id' );
        $data    = $request->get_json_params();
        $user_id = get_current_user_id();
        $role    = self::get_user_role();

        $ticket = Carspace_Ticket::find( $id );
        if ( ! $ticket ) {
            return new WP_REST_Response( array( 'message' => 'Ticket not found.' ), 404 );
        }

        // Dealers can only reply to their own tickets
        if ( $role === 'dealer' && (int) $ticket->author_id !== $user_id ) {
            return new WP_REST_Response( array( 'message' => 'Access denied.' ), 403 );
        }

        if ( $ticket->status === 'closed' ) {
            return new WP_REST_Response( array( 'message' => 'Cannot reply to a closed ticket.' ), 400 );
        }

        if ( empty( $data['content'] ) ) {
            return new WP_REST_Response( array( 'message' => 'Message content is required.' ), 400 );
        }

        $msg_id = Carspace_Ticket::add_message( $id, $user_id, $data['content'] );
        if ( ! $msg_id ) {
            return new WP_REST_Response( array( 'message' => 'Failed to add reply.' ), 500 );
        }

        // Update ticket status based on who replied
        if ( $role === 'admin' || $role === 'manager' ) {
            Carspace_Ticket::update( $id, array( 'status' => 'answered' ) );
            // Notify the ticket author
            Carspace_Notification::create(
                array( (int) $ticket->author_id ),
                sprintf( 'Reply on ticket: %s', $ticket->subject ),
                'Your support ticket has a new reply.',
                'system'
            );
        } else {
            Carspace_Ticket::update( $id, array( 'status' => 'waiting' ) );
        }

        $authors = self::batch_users( array( $user_id ) );
        $author = isset( $authors[ $user_id ] ) ? $authors[ $user_id ] : array( 'name' => 'Unknown', 'avatar' => '' );

        global $wpdb;
        $msg_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_ticket_messages WHERE id = %d",
            $msg_id
        ) );

        return new WP_REST_Response( array(
            'id'            => (string) $msg_row->id,
            'ticket_id'     => (string) $msg_row->ticket_id,
            'author_id'     => (string) $user_id,
            'author_name'   => $author['name'],
            'author_avatar' => $author['avatar'],
            'author_role'   => $role,
            'content'       => $msg_row->content,
            'attachments'   => array(),
            'created_at'    => $msg_row->created_at,
        ), 201 );
    }

    /**
     * Format a ticket row for REST response.
     */
    private static function format_ticket_row( $row, $authors = array() ) {
        $aid = (int) $row->author_id;
        $author = isset( $authors[ $aid ] ) ? $authors[ $aid ] : array( 'name' => '', 'avatar' => '' );

        return array(
            'id'          => (string) $row->id,
            'subject'     => $row->subject,
            'status'      => $row->status,
            'priority'    => $row->priority,
            'category'    => $row->category ?: '',
            'author_id'   => (string) $aid,
            'author_name' => $author['name'],
            'assigned_to' => $row->assigned_to ? (string) $row->assigned_to : '',
            'messages'    => array(),
            'created_at'  => $row->created_at,
            'updated_at'  => $row->updated_at,
        );
    }

    /* ------------------------------------------------------------------
     * Transport Prices
     * ----------------------------------------------------------------*/

    /**
     * GET /transport-prices — list all routes (admin only).
     */
    public static function get_transport_prices() {
        $rows = Carspace_Transport_Price::get_all();
        $items = array();
        foreach ( $rows as $row ) {
            $items[] = self::format_price_row( $row );
        }
        return new WP_REST_Response( array(
            'items' => $items,
            'total' => count( $items ),
            'tiers' => Carspace_Transport_Price::get_available_tiers(),
        ), 200 );
    }

    /**
     * POST /transport-prices — create a route.
     */
    public static function create_transport_price( $request ) {
        $data = $request->get_json_params();
        if ( empty( $data['location'] ) || empty( $data['loading_port'] ) ) {
            return new WP_REST_Response( array( 'message' => 'Location and loading port are required.' ), 400 );
        }
        $id = Carspace_Transport_Price::save( $data );
        if ( $id === false ) {
            return new WP_REST_Response( array( 'message' => 'Failed to create transport price.' ), 500 );
        }
        $row = Carspace_Transport_Price::find( $id );
        return new WP_REST_Response( self::format_price_row( $row ), 201 );
    }

    /**
     * PUT /transport-prices/{id} — update a route.
     */
    public static function update_transport_price( $request ) {
        $id   = (int) $request->get_param( 'id' );
        $data = $request->get_json_params();
        $data['id'] = $id;

        $existing = Carspace_Transport_Price::find( $id );
        if ( ! $existing ) {
            return new WP_REST_Response( array( 'message' => 'Transport price not found.' ), 404 );
        }

        $result = Carspace_Transport_Price::save( $data );
        if ( $result === false ) {
            return new WP_REST_Response( array( 'message' => 'Failed to update transport price.' ), 500 );
        }

        $row = Carspace_Transport_Price::find( $id );
        return new WP_REST_Response( self::format_price_row( $row ), 200 );
    }

    /**
     * DELETE /transport-prices/{id}
     */
    public static function delete_transport_price( $request ) {
        $id = (int) $request->get_param( 'id' );
        $existing = Carspace_Transport_Price::find( $id );
        if ( ! $existing ) {
            return new WP_REST_Response( array( 'message' => 'Transport price not found.' ), 404 );
        }
        Carspace_Transport_Price::delete( $id );
        return new WP_REST_Response( null, 204 );
    }

    /**
     * POST /transport-prices/import — CSV file upload.
     */
    public static function import_transport_prices( $request ) {
        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new WP_REST_Response( array( 'message' => 'No file uploaded.' ), 400 );
        }

        $result = Carspace_Transport_Price::import_csv( $files['file'] );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'message' => $result->get_error_message() ), 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /**
     * GET /transport-prices/export — returns CSV as downloadable response.
     */
    public static function export_transport_prices() {
        $csv = Carspace_Transport_Price::export_csv();
        $response = new WP_REST_Response( $csv );
        $response->header( 'Content-Type', 'text/csv; charset=utf-8' );
        $response->header( 'Content-Disposition', 'attachment; filename="transport_prices_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv"' );
        return $response;
    }

    /**
     * DELETE /transport-prices/delete-all
     */
    public static function delete_all_transport_prices() {
        Carspace_Transport_Price::delete_all();
        return new WP_REST_Response( null, 204 );
    }

    /**
     * GET /transport-prices/calculate — calculate price for current user.
     */
    public static function calculate_transport_price( $request ) {
        $location_id  = (int) $request->get_param( 'location_id' );
        $location     = $request->get_param( 'location' );
        $loading_port = $request->get_param( 'loading_port' );
        $user_id      = get_current_user_id();

        $price = null;

        if ( $location_id > 0 ) {
            $price = Carspace_Transport_Price::calculate( $location_id, $user_id );
        } elseif ( $location && $loading_port ) {
            $price = Carspace_Transport_Price::calculate_by_route( $location, $loading_port, $user_id );
        }

        if ( $price === null ) {
            return new WP_REST_Response( array( 'message' => 'No pricing data found.' ), 404 );
        }

        return new WP_REST_Response( array(
            'price'     => $price,
            'tier'      => Carspace_Transport_Price::get_user_tier( $user_id ),
            'formatted' => '$' . number_format( $price, 2 ),
        ), 200 );
    }

    /**
     * GET /transport-prices/locations — unique locations and ports.
     */
    public static function get_transport_locations() {
        return self::reference_response( array(
            'locations'     => Carspace_Transport_Price::get_locations(),
            'loading_ports' => Carspace_Transport_Price::get_loading_ports(),
            'routes'        => Carspace_Transport_Price::get_routes(),
        ) );
    }

    /**
     * GET /users/{id}/tier
     */
    public static function get_user_tier( $request ) {
        $user_id = (int) $request->get_param( 'id' );
        $user    = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_REST_Response( array( 'message' => 'User not found.' ), 404 );
        }

        return new WP_REST_Response( array(
            'user_id'   => $user_id,
            'tier'      => Carspace_Transport_Price::get_user_tier( $user_id ),
            'tier_name' => Carspace_Transport_Price::get_available_tiers()[ Carspace_Transport_Price::get_user_tier( $user_id ) ] ?? 'Base Price',
        ), 200 );
    }

    /**
     * PUT /users/{id}/tier
     */
    public static function set_user_tier( $request ) {
        $user_id = (int) $request->get_param( 'id' );
        $data    = $request->get_json_params();
        $tier    = isset( $data['tier'] ) ? sanitize_text_field( $data['tier'] ) : '';

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_REST_Response( array( 'message' => 'User not found.' ), 404 );
        }

        if ( ! array_key_exists( $tier, Carspace_Transport_Price::get_available_tiers() ) ) {
            return new WP_REST_Response( array( 'message' => 'Invalid tier.' ), 400 );
        }

        Carspace_Transport_Price::set_user_tier( $user_id, $tier );

        return new WP_REST_Response( array(
            'user_id'   => $user_id,
            'tier'      => $tier,
            'tier_name' => Carspace_Transport_Price::get_available_tiers()[ $tier ],
        ), 200 );
    }

    /**
     * Format a tpc_prices row for JSON response.
     */
    private static function format_price_row( $row ) {
        return array(
            'id'           => (int) $row->id,
            'location'     => $row->location,
            'loading_port' => $row->loading_port,
            'base_price'   => (float) $row->base_price,
            'price1'       => (float) $row->price1,
            'price2'       => (float) $row->price2,
            'price3'       => (float) $row->price3,
            'price4'       => (float) $row->price4,
            'price5'       => (float) $row->price5,
            'price6'       => (float) $row->price6,
            'price7'       => (float) $row->price7,
            'price8'       => (float) $row->price8,
            'price9'       => (float) $row->price9,
            'price10'      => (float) $row->price10,
        );
    }

    /* ------------------------------------------------------------------
     * Helper: batch users
     * ----------------------------------------------------------------*/

    private static function batch_users( $user_ids ) {
        $map = array();
        if ( empty( $user_ids ) ) {
            return $map;
        }

        $users = get_users( array(
            'include' => array_map( 'intval', $user_ids ),
            'fields'  => array( 'ID', 'display_name' ),
        ) );

        foreach ( $users as $u ) {
            $map[ (int) $u->ID ] = array(
                'name'   => $u->display_name,
                'avatar' => get_avatar_url( (int) $u->ID, array( 'size' => 96 ) ),
            );
        }

        return $map;
    }

    /**
     * Batch-fetch attachment URLs for multiple attachment IDs in one query.
     * Replaces per-attachment wp_get_attachment_url() calls.
     *
     * @param  array $att_ids Array of attachment post IDs.
     * @return array [attachment_id => url]
     */
    private static function batch_attachment_urls( $att_ids ) {
        global $wpdb;

        $map = array();
        if ( empty( $att_ids ) ) {
            return $map;
        }

        $ids = array_unique( array_map( 'intval', array_filter( $att_ids ) ) );
        if ( empty( $ids ) ) {
            return $map;
        }

        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $upload_dir   = wp_get_upload_dir();
        $base_url     = trailingslashit( $upload_dir['baseurl'] );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta}
             WHERE post_id IN ({$placeholders}) AND meta_key = '_wp_attached_file'",
            $ids
        ) );

        foreach ( $rows as $r ) {
            $map[ (int) $r->post_id ] = $base_url . ltrim( $r->meta_value, '/' );
        }

        return $map;
    }

    /**
     * Batch-fetch SKU → product_id mapping for multiple VINs.
     * Replaces per-VIN wc_get_product_id_by_sku() calls.
     *
     * @param  array $skus Array of SKU strings.
     * @return array [lowercase_sku => product_id]
     */
    private static function batch_sku_to_product_id( $skus ) {
        global $wpdb;

        $map = array();
        if ( empty( $skus ) ) {
            return $map;
        }

        $clean = array();
        foreach ( $skus as $s ) {
            $s = strtolower( trim( $s ) );
            if ( $s !== '' ) {
                $clean[] = $s;
            }
        }
        $clean = array_unique( $clean );
        if ( empty( $clean ) ) {
            return $map;
        }

        $placeholders = implode( ',', array_fill( 0, count( $clean ), '%s' ) );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT pm.meta_value AS sku, pm.post_id
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_sku' AND LOWER(pm.meta_value) IN ({$placeholders})
               AND p.post_type IN ('product', 'product_variation')
               AND p.post_status = 'publish'",
            $clean
        ) );

        foreach ( $rows as $r ) {
            $key = strtolower( trim( $r->sku ) );
            $map[ $key ] = (int) $r->post_id;
        }

        return $map;
    }

    /**
     * Atomic invoice number counter using wp_options.
     * Initializes from current max post count on first use.
     *
     * @return int Next invoice number.
     */
    private static function next_invoice_number() {
        global $wpdb;

        $option_name = 'carspace_invoice_counter';

        // Atomic increment — if option exists, increment and return.
        // The raw UPDATE bypasses WP's option cache, so drop the stale entry
        // first; otherwise get_option() returns the pre-increment value.
        $updated = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s",
            $option_name
        ) );

        if ( $updated ) {
            wp_cache_delete( $option_name, 'options' );
            return (int) get_option( $option_name );
        }

        // First run: initialize from current count
        $current = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'invoice' AND post_status IN ('publish','draft','trash')"
        );
        $next = $current + 1;
        add_option( $option_name, $next, '', 'no' );

        return $next;
    }

    /* ------------------------------------------------------------------
     * Auction Fees
     * ----------------------------------------------------------------*/

    public static function get_auction_fees( WP_REST_Request $request ) {
        $auction  = sanitize_text_field( $request->get_param( 'auction' ) ?? '' );
        $category = sanitize_text_field( $request->get_param( 'category' ) ?? '' );

        if ( $auction && $category ) {
            return self::reference_response( Carspace_Auction_Fee::get_ranges( $auction, $category ) );
        }

        return self::reference_response( Carspace_Auction_Fee::get_all() );
    }

    public static function save_auction_fee_range( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $id   = Carspace_Auction_Fee::save( $data );
        if ( $id === false ) {
            return new WP_Error( 'save_failed', 'Failed to save range.', array( 'status' => 500 ) );
        }
        return rest_ensure_response( array( 'ok' => true, 'id' => $id ) );
    }

    public static function update_auction_fee_range( WP_REST_Request $request ) {
        $data       = $request->get_json_params();
        $data['id'] = (int) $request->get_param( 'id' );
        $id         = Carspace_Auction_Fee::save( $data );
        if ( $id === false ) {
            return new WP_Error( 'update_failed', 'Failed to update range.', array( 'status' => 500 ) );
        }
        return rest_ensure_response( array( 'ok' => true, 'id' => $id ) );
    }

    public static function delete_auction_fee_range( WP_REST_Request $request ) {
        Carspace_Auction_Fee::delete( (int) $request->get_param( 'id' ) );
        return rest_ensure_response( array( 'ok' => true ) );
    }

    public static function bulk_delete_auction_fees( WP_REST_Request $request ) {
        $ids = $request->get_param( 'ids' );
        if ( ! is_array( $ids ) || empty( $ids ) ) {
            return new WP_Error( 'invalid_ids', 'No IDs provided.', array( 'status' => 400 ) );
        }
        $deleted = Carspace_Auction_Fee::delete_many( $ids );
        return rest_ensure_response( array( 'ok' => true, 'deleted' => $deleted ) );
    }

    public static function get_auction_fixed_fees() {
        return self::reference_response( array(
            'copart' => Carspace_Auction_Fee::get_fixed_fees( 'copart' ),
            'iaai'   => Carspace_Auction_Fee::get_fixed_fees( 'iaai' ),
        ) );
    }

    public static function save_auction_fixed_fees( WP_REST_Request $request ) {
        $data    = $request->get_json_params();
        $auction = sanitize_text_field( $data['auction'] ?? '' );
        if ( ! in_array( $auction, array( 'copart', 'iaai' ), true ) ) {
            return new WP_Error( 'invalid_auction', 'Invalid auction type.', array( 'status' => 400 ) );
        }
        $saved = Carspace_Auction_Fee::save_fixed_fees( $auction, $data );
        return rest_ensure_response( $saved );
    }

    public static function import_auction_fees( WP_REST_Request $request ) {
        $files    = $request->get_file_params();
        $params   = $request->get_params();
        $auction  = sanitize_text_field( $params['auction'] ?? '' );
        $category = sanitize_text_field( $params['category'] ?? '' );

        if ( ! in_array( $auction, array( 'copart', 'iaai' ), true ) ) {
            return new WP_Error( 'invalid_auction', 'Invalid auction type.', array( 'status' => 400 ) );
        }
        if ( ! in_array( $category, array( 'non_clean_title', 'virtual_bid' ), true ) ) {
            return new WP_Error( 'invalid_category', 'Invalid fee category.', array( 'status' => 400 ) );
        }
        if ( empty( $files['file'] ) ) {
            return new WP_Error( 'no_file', 'No file uploaded.', array( 'status' => 400 ) );
        }

        // Clear existing ranges for this auction+category before import
        Carspace_Auction_Fee::delete_category( $auction, $category );

        $result = Carspace_Auction_Fee::import_csv( $files['file'], $auction, $category );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( $result );
    }

    public static function export_auction_fees( WP_REST_Request $request ) {
        $auction  = sanitize_text_field( $request->get_param( 'auction' ) ?? 'copart' );
        $category = sanitize_text_field( $request->get_param( 'category' ) ?? 'non_clean_title' );

        $csv      = Carspace_Auction_Fee::export_csv( $auction, $category );
        $response = new WP_REST_Response( $csv );
        $response->header( 'Content-Type', 'text/csv; charset=utf-8' );
        $response->header( 'Content-Disposition', "attachment; filename=\"{$auction}-{$category}.csv\"" );
        return $response;
    }

    public static function calculate_auction_fee( WP_REST_Request $request ) {
        $data    = $request->get_json_params();
        $auction = sanitize_text_field( $data['auction'] ?? 'copart' );
        $bid     = floatval( $data['bid_price'] ?? 0 );

        if ( ! in_array( $auction, array( 'copart', 'iaai' ), true ) ) {
            return new WP_Error( 'invalid_auction', 'Invalid auction type.', array( 'status' => 400 ) );
        }

        return rest_ensure_response( Carspace_Auction_Fee::calculate( $auction, $bid ) );
    }

    /* ------------------------------------------------------------------
     * Title Codes
     * ----------------------------------------------------------------*/

    public static function get_title_codes() {
        return self::reference_response( Carspace_Title_Code::get_all() );
    }

    public static function save_title_code( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $id   = Carspace_Title_Code::save( $data );

        if ( $id === false ) {
            return new WP_Error( 'save_failed', 'Failed to save title code.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( Carspace_Title_Code::find( $id ) );
    }

    public static function update_title_code( WP_REST_Request $request ) {
        $id   = (int) $request->get_param( 'id' );
        $data = $request->get_json_params();
        $data['id'] = $id;

        $result = Carspace_Title_Code::save( $data );
        if ( $result === false ) {
            return new WP_Error( 'update_failed', 'Failed to update title code.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( Carspace_Title_Code::find( $id ) );
    }

    public static function delete_title_code( WP_REST_Request $request ) {
        $id = (int) $request->get_param( 'id' );
        Carspace_Title_Code::delete( $id );
        return rest_ensure_response( array( 'ok' => true ) );
    }

    public static function bulk_delete_title_codes( WP_REST_Request $request ) {
        $ids = $request->get_param( 'ids' );
        if ( ! is_array( $ids ) || empty( $ids ) ) {
            return new WP_Error( 'invalid_ids', 'No IDs provided.', array( 'status' => 400 ) );
        }
        $deleted = Carspace_Title_Code::delete_many( $ids );
        return rest_ensure_response( array( 'ok' => true, 'deleted' => $deleted ) );
    }

    public static function delete_all_title_codes() {
        Carspace_Title_Code::delete_all();
        return rest_ensure_response( array( 'ok' => true ) );
    }

    public static function import_title_codes( WP_REST_Request $request ) {
        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new WP_Error( 'no_file', 'No file uploaded.', array( 'status' => 400 ) );
        }

        $result = Carspace_Title_Code::import_csv( $files['file'] );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public static function export_title_codes() {
        $csv = Carspace_Title_Code::export_csv();

        $response = new WP_REST_Response( $csv );
        $response->header( 'Content-Type', 'text/csv; charset=utf-8' );
        $response->header( 'Content-Disposition', 'attachment; filename="title-codes.csv"' );

        return $response;
    }

    /* ------------------------------------------------------------------
     * Translations
     * ----------------------------------------------------------------*/

    public static function get_translations() {
        $overrides = get_option( 'carspace_translation_overrides', null );
        return rest_ensure_response( $overrides ? $overrides : new \stdClass() );
    }

    public static function save_translations( WP_REST_Request $request ) {
        $overrides = $request->get_json_params();

        if ( empty( $overrides ) || ! is_array( $overrides ) ) {
            delete_option( 'carspace_translation_overrides' );
            return rest_ensure_response( array( 'ok' => true ) );
        }

        // Sanitize: only allow known lang keys, string values
        $clean = array();
        foreach ( array( 'en', 'ka', 'ru' ) as $lang ) {
            if ( ! isset( $overrides[ $lang ] ) || ! is_array( $overrides[ $lang ] ) ) {
                continue;
            }
            $clean[ $lang ] = array();
            foreach ( $overrides[ $lang ] as $key => $value ) {
                $clean[ $lang ][ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
            }
        }

        if ( empty( $clean ) ) {
            delete_option( 'carspace_translation_overrides' );
        } else {
            update_option( 'carspace_translation_overrides', $clean, true );
        }

        return rest_ensure_response( array( 'ok' => true ) );
    }

    /* ------------------------------------------------------------------
     * Profile
     * ----------------------------------------------------------------*/

    public static function get_profile() {
        $user = wp_get_current_user();

        return rest_ensure_response( array(
            'id'           => $user->ID,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'email'        => $user->user_email,
            'avatar'       => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
            'registered'   => $user->user_registered,
        ) );
    }

    public static function update_profile( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $data    = $request->get_json_params();
        $updates = array( 'ID' => $user_id );
        $changed = array();

        // Display name
        if ( isset( $data['display_name'] ) ) {
            $name = sanitize_text_field( trim( $data['display_name'] ) );
            if ( $name === '' ) {
                return new WP_Error( 'invalid_name', 'Display name cannot be empty.', array( 'status' => 400 ) );
            }
            $updates['display_name'] = $name;
            $changed[] = 'display_name';
        }

        // First name
        if ( isset( $data['first_name'] ) ) {
            $updates['first_name'] = sanitize_text_field( trim( $data['first_name'] ) );
            $changed[] = 'first_name';
        }

        // Last name
        if ( isset( $data['last_name'] ) ) {
            $updates['last_name'] = sanitize_text_field( trim( $data['last_name'] ) );
            $changed[] = 'last_name';
        }

        // Email
        if ( isset( $data['email'] ) ) {
            $email = sanitize_email( $data['email'] );
            if ( ! is_email( $email ) ) {
                return new WP_Error( 'invalid_email', 'Please enter a valid email address.', array( 'status' => 400 ) );
            }
            // Check uniqueness
            $existing = email_exists( $email );
            if ( $existing && $existing !== $user_id ) {
                return new WP_Error( 'email_taken', 'This email is already used by another account.', array( 'status' => 400 ) );
            }
            $updates['user_email'] = $email;
            $changed[] = 'email';
        }

        // Password
        if ( ! empty( $data['new_password'] ) ) {
            $current = $data['current_password'] ?? '';
            $user    = get_user_by( 'ID', $user_id );

            if ( ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
                return new WP_Error( 'wrong_password', 'Current password is incorrect.', array( 'status' => 400 ) );
            }

            $new_pass = $data['new_password'];
            if ( strlen( $new_pass ) < 6 ) {
                return new WP_Error( 'weak_password', 'New password must be at least 6 characters.', array( 'status' => 400 ) );
            }

            $updates['user_pass'] = $new_pass;
            $changed[] = 'password';
        }

        if ( empty( $changed ) ) {
            return new WP_Error( 'no_changes', 'No changes to save.', array( 'status' => 400 ) );
        }

        $result = wp_update_user( $updates );

        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'update_failed', $result->get_error_message(), array( 'status' => 500 ) );
        }

        // Return updated profile
        return self::get_profile();
    }

    /* ------------------------------------------------------------------
     * Auth (login / logout)
     * ----------------------------------------------------------------*/

    public static function auth_login( WP_REST_Request $request ) {
        $data     = $request->get_json_params();
        $username = sanitize_text_field( $data['username'] ?? '' );
        $password = $data['password'] ?? '';

        if ( empty( $username ) || empty( $password ) ) {
            return new WP_Error( 'missing_fields', 'Username and password are required.', array( 'status' => 400 ) );
        }

        // Allow login by email or username
        if ( is_email( $username ) ) {
            $user_obj = get_user_by( 'email', $username );
            if ( $user_obj ) {
                $username = $user_obj->user_login;
            }
        }

        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => ! empty( $data['remember'] ),
        );

        $user = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user ) ) {
            return new WP_Error( 'login_failed', 'Invalid username or password.', array( 'status' => 401 ) );
        }

        // Set current user so nonce generation works
        wp_set_current_user( $user->ID );

        $role = 'dealer';
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            $role = 'admin';
        } elseif ( in_array( 'editor', (array) $user->roles, true ) || in_array( 'shop_manager', (array) $user->roles, true ) ) {
            $role = 'manager';
        }

        return rest_ensure_response( array(
            'user' => array(
                'id'     => $user->ID,
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'avatar' => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
                'role'   => $role,
            ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    public static function auth_logout() {
        wp_logout();
        return rest_ensure_response( array( 'ok' => true ) );
    }
}
