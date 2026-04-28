<?php
/**
 * Transport Price Model
 *
 * Wraps the wp_tpc_prices table. Each row represents a unique route
 * (location + loading_port) with tiered pricing (base_price, price1–price10).
 *
 * @package Carspace_Dashboard
 */

defined( 'ABSPATH' ) || exit;

class Carspace_Transport_Price {

    /**
     * Get the table name.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tpc_prices';
    }

    /**
     * Get all price rows ordered by location.
     */
    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM " . self::table() . " ORDER BY location, loading_port" );
    }

    /**
     * Get a single price row by ID.
     */
    public static function find( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d",
            $id
        ) );
    }

    /**
     * Get price row by route (location + loading_port).
     */
    public static function find_by_route( $location, $loading_port ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE location = %s AND loading_port = %s LIMIT 1",
            $location, $loading_port
        ) );
    }

    /**
     * Create or update a price row.
     *
     * If $data['id'] is set and the row exists, updates it.
     * If $data['id'] is set and the row doesn't exist, inserts with that ID.
     * Otherwise inserts with auto-increment.
     *
     * @param  array    $data
     * @return int|false Row ID on success, false on failure.
     */
    public static function save( $data ) {
        global $wpdb;

        $record = self::sanitize_record( $data );

        if ( ! empty( $data['id'] ) && intval( $data['id'] ) > 0 ) {
            $id     = intval( $data['id'] );
            $exists = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::table() . " WHERE id = %d", $id
            ) );

            if ( $exists ) {
                $result = $wpdb->update( self::table(), $record, array( 'id' => $id ) );
                return $result === false ? false : $id;
            }

            $result = $wpdb->insert( self::table(), array_merge( array( 'id' => $id ), $record ) );
            return $result === false ? false : $id;
        }

        $result = $wpdb->insert( self::table(), $record );
        return $result === false ? false : (int) $wpdb->insert_id;
    }

    /**
     * Delete a price row by ID.
     */
    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), array( 'id' => intval( $id ) ) );
    }

    /**
     * Delete all price rows.
     */
    public static function delete_all() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE " . self::table() );
    }

    /**
     * Get unique locations.
     */
    public static function get_locations() {
        global $wpdb;
        return $wpdb->get_col( "SELECT DISTINCT location FROM " . self::table() . " ORDER BY location" );
    }

    /**
     * Get unique loading ports.
     */
    public static function get_loading_ports() {
        global $wpdb;
        return $wpdb->get_col( "SELECT DISTINCT loading_port FROM " . self::table() . " ORDER BY loading_port" );
    }

    /**
     * Get all location→loading_port pairs.
     */
    public static function get_routes() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT DISTINCT location, loading_port FROM " . self::table() . " ORDER BY location, loading_port"
        );
    }

    /**
     * Batch-fetch price rows by IDs. Returns [ id => row ].
     */
    public static function batch_by_ids( $ids ) {
        global $wpdb;
        $map = array();
        if ( empty( $ids ) ) {
            return $map;
        }
        $ids          = array_unique( array_map( 'intval', $ids ) );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $rows         = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id IN ({$placeholders})",
            $ids
        ) );
        foreach ( $rows as $row ) {
            $map[ (int) $row->id ] = $row;
        }
        return $map;
    }

    /**
     * Calculate transport price for a user given a location ID.
     *
     * @param  int      $location_id  Row ID in tpc_prices.
     * @param  int|null $user_id      User to look up tier for. Defaults to current user.
     * @return float|null             Price or null if not found.
     */
    public static function calculate( $location_id, $user_id = null ) {
        $row = self::find( $location_id );
        if ( ! $row ) {
            return null;
        }

        $tier = self::get_user_tier( $user_id );

        if ( ! empty( $tier ) && isset( $row->$tier ) && $row->$tier !== '' && $row->$tier !== null ) {
            return (float) $row->$tier;
        }

        return (float) $row->base_price;
    }

    /**
     * Calculate transport price by route fallback.
     *
     * @param  string   $location
     * @param  string   $loading_port
     * @param  int|null $user_id
     * @return float|null
     */
    public static function calculate_by_route( $location, $loading_port, $user_id = null ) {
        $row = self::find_by_route( $location, $loading_port );
        if ( ! $row ) {
            return null;
        }

        $tier = self::get_user_tier( $user_id );

        if ( ! empty( $tier ) && isset( $row->$tier ) && $row->$tier !== '' && $row->$tier !== null ) {
            return (float) $row->$tier;
        }

        return (float) $row->base_price;
    }

    /* ------------------------------------------------------------------
     * User tier helpers
     * ----------------------------------------------------------------*/

    /**
     * Get the user tiers table name.
     */
    private static function tiers_table() {
        global $wpdb;
        return $wpdb->prefix . 'carspace_user_tiers';
    }

    /**
     * Available tier column names.
     */
    public static function get_available_tiers() {
        return array(
            'base_price' => 'Base Price',
            'price1'     => 'Price 1',
            'price2'     => 'Price 2',
            'price3'     => 'Price 3',
            'price4'     => 'Price 4',
            'price5'     => 'Price 5',
            'price6'     => 'Price 6',
            'price7'     => 'Price 7',
            'price8'     => 'Price 8',
            'price9'     => 'Price 9',
            'price10'    => 'Price 10',
        );
    }

    /**
     * Get a user's tier (column name).
     * Reads from carspace_user_tiers custom table (PK lookup = fast).
     */
    public static function get_user_tier( $user_id = null ) {
        global $wpdb;

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        if ( ! $user_id ) {
            return 'base_price';
        }

        $tier = $wpdb->get_var( $wpdb->prepare(
            "SELECT tier FROM " . self::tiers_table() . " WHERE user_id = %d",
            $user_id
        ) );

        if ( empty( $tier ) || ! array_key_exists( $tier, self::get_available_tiers() ) ) {
            return 'base_price';
        }

        return $tier;
    }

    /**
     * Set a user's tier.
     * Uses REPLACE INTO for upsert (PK = user_id).
     */
    public static function set_user_tier( $user_id, $tier ) {
        global $wpdb;

        if ( ! array_key_exists( $tier, self::get_available_tiers() ) ) {
            return false;
        }

        return $wpdb->replace(
            self::tiers_table(),
            array(
                'user_id' => (int) $user_id,
                'tier'    => $tier,
            ),
            array( '%d', '%s' )
        );
    }

    /**
     * Batch-fetch tiers for multiple users. Returns [ user_id => tier_key ].
     */
    public static function batch_user_tiers( $user_ids ) {
        global $wpdb;

        $map = array();
        if ( empty( $user_ids ) ) {
            return $map;
        }

        $ids          = array_unique( array_map( 'intval', $user_ids ) );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $rows         = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, tier FROM " . self::tiers_table() . " WHERE user_id IN ({$placeholders})",
            $ids
        ) );

        foreach ( $rows as $row ) {
            $map[ (int) $row->user_id ] = $row->tier;
        }

        return $map;
    }

    /* ------------------------------------------------------------------
     * CSV import
     * ----------------------------------------------------------------*/

    /**
     * Process an uploaded CSV file (from $_FILES entry).
     *
     * @param  array $file  $_FILES entry (with tmp_name, etc.)
     * @return array|WP_Error
     */
    public static function import_csv( $file ) {
        if ( ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_file', 'Invalid file upload.' );
        }

        $handle = fopen( $file['tmp_name'], 'r' );
        if ( ! $handle ) {
            return new WP_Error( 'file_open_error', 'Could not open the file.' );
        }

        $raw_headers = fgetcsv( $handle );
        if ( $raw_headers === false ) {
            fclose( $handle );
            return new WP_Error( 'invalid_headers', 'CSV file is empty or headers are missing.' );
        }

        // Map column index → canonical field name
        $header_map = array();
        foreach ( $raw_headers as $i => $raw ) {
            $header_map[ $i ] = self::canonicalize_header( $raw );
        }

        if ( ! in_array( 'location', $header_map, true ) || ! in_array( 'loading_port', $header_map, true ) ) {
            fclose( $handle );
            return new WP_Error( 'invalid_headers', 'CSV must include "location" and "loading_port" headers.' );
        }

        $rows_processed = 0;
        $errors         = array();
        $row_number     = 1;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_number++;

            if ( ! is_array( $row ) || count( array_filter( $row, function( $v ) { return trim( (string) $v ) !== ''; } ) ) === 0 ) {
                continue;
            }

            $data = array();
            foreach ( $row as $i => $value ) {
                if ( ! isset( $header_map[ $i ] ) ) continue;
                $key = $header_map[ $i ];
                if ( $key === null ) continue;

                if ( $key === 'id' ) {
                    $v = trim( (string) $value );
                    if ( $v !== '' && preg_match( '/^\d+$/', $v ) ) {
                        $data['id'] = (int) $v;
                    }
                } elseif ( $key === 'location' || $key === 'loading_port' ) {
                    $data[ $key ] = trim( (string) $value );
                } else {
                    $num = self::parse_csv_number( $value );
                    $data[ $key ] = ( $num === null ) ? 0 : $num;
                }
            }

            if ( empty( $data['location'] ) || empty( $data['loading_port'] ) ) {
                $errors[] = sprintf( 'Row %d: Location and loading port are required.', $row_number );
                continue;
            }

            // Ensure all numeric fields
            foreach ( array( 'base_price', 'price1', 'price2', 'price3', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9', 'price10' ) as $nf ) {
                if ( ! isset( $data[ $nf ] ) ) {
                    $data[ $nf ] = 0;
                }
            }

            $result = self::save( $data );
            if ( $result === false ) {
                $errors[] = sprintf( 'Row %d: Failed to save data for %s - %s.', $row_number, $data['location'], $data['loading_port'] );
            } else {
                $rows_processed++;
            }
        }

        fclose( $handle );

        return array(
            'success'        => true,
            'rows_processed' => $rows_processed,
            'errors'         => $errors,
        );
    }

    /**
     * Export all prices as CSV string.
     *
     * @return string CSV content.
     */
    public static function export_csv() {
        $prices = self::get_all();

        $output = fopen( 'php://temp', 'r+' );
        fputs( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM

        fputcsv( $output, array(
            'id', 'location', 'loading_port', 'base_price',
            'price1', 'price2', 'price3', 'price4', 'price5',
            'price6', 'price7', 'price8', 'price9', 'price10',
        ) );

        foreach ( $prices as $p ) {
            fputcsv( $output, array(
                $p->id, $p->location, $p->loading_port, $p->base_price,
                $p->price1, $p->price2, $p->price3, $p->price4, $p->price5,
                $p->price6, $p->price7, $p->price8, $p->price9, $p->price10,
            ) );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    /* ------------------------------------------------------------------
     * Auto-pricing on car assignment
     * ----------------------------------------------------------------*/

    /**
     * Calculate and set transport price as WC regular price when a car
     * (product) is assigned to a user.
     *
     * @param int $product_id WooCommerce product ID.
     * @param int $user_id    Assigned user ID (0 to clear).
     */
    public static function auto_set_transport_price( $product_id, $user_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        // Unassigned → clear regular price
        if ( ! $user_id || $user_id <= 0 ) {
            update_post_meta( $product_id, '_regular_price', '' );
            update_post_meta( $product_id, '_price', '' );
            wc_delete_product_transients( $product_id );
            return;
        }

        // Try Location ID first (custom attribute name as shown in WC product edit)
        $location_id_raw = $product->get_attribute( 'Location ID' );
        if ( ! $location_id_raw ) {
            $location_id_raw = $product->get_attribute( 'location-id' );
        }
        if ( ! $location_id_raw ) {
            $location_id_raw = $product->get_attribute( 'pa_location-id' );
        }
        $location_id = intval( preg_replace( '/\D+/', '', (string) $location_id_raw ) );

        $price = null;

        if ( $location_id > 0 ) {
            $price = self::calculate( $location_id, $user_id );
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
                $price = self::calculate_by_route( $auction_city, $loading_port, $user_id );
            }
        }

        // Write directly to postmeta to avoid $product->save() re-triggering
        // save_post_product (which lets WC overwrite with $_POST['_regular_price'])
        if ( $price !== null ) {
            update_post_meta( $product_id, '_regular_price', wc_format_decimal( $price ) );
            update_post_meta( $product_id, '_price', wc_format_decimal( $price ) );
        } else {
            update_post_meta( $product_id, '_regular_price', '' );
            update_post_meta( $product_id, '_price', '' );
        }
        wc_delete_product_transients( $product_id );
    }

    /* ------------------------------------------------------------------
     * Private helpers
     * ----------------------------------------------------------------*/

    private static function sanitize_record( $data ) {
        $record = array(
            'location'     => isset( $data['location'] ) ? sanitize_text_field( $data['location'] ) : '',
            'loading_port' => isset( $data['loading_port'] ) ? sanitize_text_field( $data['loading_port'] ) : '',
        );

        foreach ( array( 'base_price', 'price1', 'price2', 'price3', 'price4', 'price5', 'price6', 'price7', 'price8', 'price9', 'price10' ) as $col ) {
            $record[ $col ] = isset( $data[ $col ] ) ? floatval( $data[ $col ] ) : 0;
        }

        return $record;
    }

    private static function canonicalize_header( $header ) {
        if ( $header === null ) return null;

        $h = preg_replace( '/^\xEF\xBB\xBF/', '', trim( $header ) );
        $h = strtolower( $h );
        $h = preg_replace( '/[^a-z0-9]+/', '', $h );

        if ( $h === 'id' ) return 'id';
        if ( $h === 'location' ) return 'location';
        if ( in_array( $h, array( 'loadingport', 'port', 'loading' ), true ) ) return 'loading_port';
        if ( in_array( $h, array( 'baseprice', 'base', 'baseprc' ), true ) ) return 'base_price';

        if ( preg_match( '/^price([0-9]{1,2})$/', $h, $m ) ) {
            $n = intval( $m[1] );
            if ( $n >= 1 && $n <= 10 ) return 'price' . $n;
        }

        return null;
    }

    private static function parse_csv_number( $value ) {
        if ( $value === null ) return null;
        $v = trim( (string) $value );
        if ( $v === '' ) return null;
        $v = str_replace( ',', '', $v );
        $v = preg_replace( '/[^0-9.\-]/', '', $v );
        if ( $v === '' || $v === '-' || $v === '.' || $v === '-.' ) return null;
        return (float) $v;
    }
}
