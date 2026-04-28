<?php
/**
 * Auction Fee Model
 *
 * Wraps carspace_auction_fees table + wp_options for fixed fees.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Auction_Fee {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'carspace_auction_fees';
    }

    /* ------------------------------------------------------------------
     * Fee ranges CRUD
     * ----------------------------------------------------------------*/

    /**
     * Get all ranges for an auction + category.
     *
     * @param string $auction      copart|iaai
     * @param string $fee_category non_clean_title|virtual_bid
     */
    public static function get_ranges( $auction, $fee_category ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE auction = %s AND fee_category = %s ORDER BY sort_order ASC, price_from ASC",
            $auction, $fee_category
        ) );
    }

    /**
     * Get ALL ranges grouped by auction.
     */
    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . " ORDER BY auction, fee_category, sort_order ASC, price_from ASC"
        );
    }

    /**
     * Save a single range.
     */
    public static function save( $data ) {
        global $wpdb;

        $record = array(
            'auction'      => sanitize_text_field( $data['auction'] ?? '' ),
            'fee_category' => sanitize_text_field( $data['fee_category'] ?? '' ),
            'price_from'   => floatval( $data['price_from'] ?? 0 ),
            'price_to'     => floatval( $data['price_to'] ?? 0 ),
            'fee'          => floatval( $data['fee'] ?? 0 ),
            'fee_type'     => ( $data['fee_type'] ?? 'fixed' ) === 'percentage' ? 'percentage' : 'fixed',
            'sort_order'   => intval( $data['sort_order'] ?? 0 ),
        );

        if ( ! empty( $data['id'] ) ) {
            $id = intval( $data['id'] );
            $wpdb->update( self::table(), $record, array( 'id' => $id ) );
            return $id;
        }

        $wpdb->insert( self::table(), $record );
        return $wpdb->insert_id ?: false;
    }

    /**
     * Delete a single range.
     */
    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), array( 'id' => intval( $id ) ) );
    }

    /**
     * Delete multiple ranges.
     */
    public static function delete_many( $ids ) {
        global $wpdb;
        if ( empty( $ids ) ) return 0;
        $ids = array_map( 'intval', $ids );
        $ph  = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        return $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . self::table() . " WHERE id IN ({$ph})", $ids
        ) );
    }

    /**
     * Delete all ranges for an auction + category.
     */
    public static function delete_category( $auction, $fee_category ) {
        global $wpdb;
        return $wpdb->delete( self::table(), array(
            'auction'      => $auction,
            'fee_category' => $fee_category,
        ) );
    }

    /* ------------------------------------------------------------------
     * Fixed fees (wp_options)
     * ----------------------------------------------------------------*/

    public static function get_fixed_fees( $auction ) {
        $defaults = array( 'environmental_fee' => 15, 'gate_fee' => 95, 'title_pickup_fee' => 20 );
        return get_option( "carspace_auction_fixed_fees_{$auction}", $defaults );
    }

    public static function save_fixed_fees( $auction, $fees ) {
        $clean = array(
            'environmental_fee' => floatval( $fees['environmental_fee'] ?? 0 ),
            'gate_fee'          => floatval( $fees['gate_fee'] ?? 0 ),
            'title_pickup_fee'  => floatval( $fees['title_pickup_fee'] ?? 0 ),
        );
        update_option( "carspace_auction_fixed_fees_{$auction}", $clean, true );
        return $clean;
    }

    /* ------------------------------------------------------------------
     * Calculation
     * ----------------------------------------------------------------*/

    /**
     * Calculate total for a bid price.
     *
     * @param string $auction  copart|iaai
     * @param float  $bid_price
     * @return array Breakdown.
     */
    public static function calculate( $auction, $bid_price ) {
        $bid_price = floatval( $bid_price );

        $title_ranges = self::get_ranges( $auction, 'non_clean_title' );
        $vbid_ranges  = self::get_ranges( $auction, 'virtual_bid' );
        $fixed        = self::get_fixed_fees( $auction );

        $title_fee = self::find_fee( $title_ranges, $bid_price );
        $vbid_fee  = self::find_fee( $vbid_ranges, $bid_price );

        $fixed_total = floatval( $fixed['environmental_fee'] )
                     + floatval( $fixed['gate_fee'] )
                     + floatval( $fixed['title_pickup_fee'] );

        $charges = $title_fee + $vbid_fee + $fixed_total;
        $total   = $bid_price + $charges;

        return array(
            'bid_price'          => $bid_price,
            'non_clean_title_fee' => $title_fee,
            'virtual_bid_fee'    => $vbid_fee,
            'environmental_fee'  => floatval( $fixed['environmental_fee'] ),
            'gate_fee'           => floatval( $fixed['gate_fee'] ),
            'title_pickup_fee'   => floatval( $fixed['title_pickup_fee'] ),
            'fixed_fees_total'   => $fixed_total,
            'charges'            => $charges,
            'total'              => $total,
        );
    }

    /**
     * Find the fee for a given price from a set of ranges.
     */
    private static function find_fee( $ranges, $price ) {
        foreach ( $ranges as $r ) {
            $from = floatval( $r->price_from );
            $to   = floatval( $r->price_to );

            if ( $price >= $from && ( $to <= 0 || $price <= $to ) ) {
                if ( $r->fee_type === 'percentage' ) {
                    return round( $price * floatval( $r->fee ) / 100, 2 );
                }
                return floatval( $r->fee );
            }
        }
        return 0;
    }

    /* ------------------------------------------------------------------
     * CSV import / export
     * ----------------------------------------------------------------*/

    /**
     * Import CSV for a specific auction + category.
     *
     * CSV columns: #, Final Bid Price, Fee Amount, Fee Type
     * Price format: "$0 - $49.99" or "$15000+"
     * Fee format: "$250" or "6%"
     */
    public static function import_csv( $file, $auction, $fee_category ) {
        if ( ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_file', 'Invalid file upload.' );
        }

        $handle = fopen( $file['tmp_name'], 'r' );
        if ( ! $handle ) {
            return new WP_Error( 'file_open_error', 'Could not open file.' );
        }

        // Skip header
        $header = fgetcsv( $handle );
        if ( $header === false ) {
            fclose( $handle );
            return new WP_Error( 'empty_file', 'CSV is empty.' );
        }

        $rows_processed = 0;
        $errors = array();
        $order = 0;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $order++;
            if ( ! is_array( $row ) || count( $row ) < 3 ) continue;

            // Column 0: row number (skip)
            // Column 1: price range "$0 - $49.99" or "$15000+"
            // Column 2: fee "$250" or "6%"
            // Column 3: fee type label (optional, we detect from fee format)
            $price_str = isset( $row[1] ) ? trim( $row[1] ) : '';
            $fee_str   = isset( $row[2] ) ? trim( $row[2] ) : '';

            // Parse price range
            $price_str = str_replace( array( '$', ',' ), '', $price_str );

            if ( strpos( $price_str, '+' ) !== false ) {
                // "$15000+"
                $from = floatval( str_replace( '+', '', $price_str ) );
                $to   = 0; // 0 = no upper limit
            } elseif ( strpos( $price_str, '-' ) !== false ) {
                $parts = explode( '-', $price_str, 2 );
                $from  = floatval( trim( $parts[0] ) );
                $to    = floatval( trim( $parts[1] ) );
            } else {
                $errors[] = "Row {$order}: Invalid price range '{$row[1]}'.";
                continue;
            }

            // Parse fee
            $fee_str = str_replace( array( '$', ',' ), '', $fee_str );
            if ( strpos( $fee_str, '%' ) !== false ) {
                $fee_type = 'percentage';
                $fee_val  = floatval( str_replace( '%', '', $fee_str ) );
            } else {
                $fee_type = 'fixed';
                $fee_val  = floatval( $fee_str );
            }

            $result = self::save( array(
                'auction'      => $auction,
                'fee_category' => $fee_category,
                'price_from'   => $from,
                'price_to'     => $to,
                'fee'          => $fee_val,
                'fee_type'     => $fee_type,
                'sort_order'   => $order,
            ) );

            if ( $result === false ) {
                $errors[] = "Row {$order}: Failed to save.";
            } else {
                $rows_processed++;
            }
        }

        fclose( $handle );
        return array( 'success' => true, 'rows_processed' => $rows_processed, 'errors' => $errors );
    }

    /**
     * Export CSV for an auction + category.
     */
    public static function export_csv( $auction, $fee_category ) {
        $ranges = self::get_ranges( $auction, $fee_category );
        $output = fopen( 'php://temp', 'r+' );
        fputs( $output, "\xEF\xBB\xBF" );

        $fee_label = $fee_category === 'non_clean_title' ? 'Non-Clean Title' : 'Virtual Bid Fee';
        fputcsv( $output, array( '#', 'Final Bid Price', $fee_label, 'Fee Type' ) );

        foreach ( $ranges as $i => $r ) {
            $from = '$' . number_format( $r->price_from, 2, '.', '' );
            if ( floatval( $r->price_to ) <= 0 ) {
                $price = $from . '+';
            } else {
                $price = $from . ' - $' . number_format( $r->price_to, 2, '.', '' );
            }

            if ( $r->fee_type === 'percentage' ) {
                $fee_display = number_format( $r->fee, 0 ) . '%';
            } else {
                $fee_display = '$' . number_format( $r->fee, 0 );
            }

            $type_label = $r->fee_type === 'percentage' ? 'Percentage' : 'Fixed Amount';
            fputcsv( $output, array( $i + 1, $price, $fee_display, $type_label ) );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );
        return $csv;
    }
}
