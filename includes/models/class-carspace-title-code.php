<?php
/**
 * Title Code Model
 *
 * Wraps carspace_title_codes table for title code check feature.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Title_Code {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'carspace_title_codes';
    }

    /**
     * Get all title codes.
     */
    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . self::table() . " ORDER BY title_code ASC");
    }

    /**
     * Find a single record by ID.
     */
    public static function find( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d",
            $id
        ) );
    }

    /**
     * Create or update a title code record.
     *
     * @param array $data
     * @return int|false  Record ID or false on failure.
     */
    public static function save( $data ) {
        global $wpdb;

        $record = array(
            'title_code'      => isset( $data['title_code'] ) ? sanitize_text_field( $data['title_code'] ) : '',
            'urgent_time'     => isset( $data['urgent_time'] ) ? sanitize_text_field( $data['urgent_time'] ) : '',
            'urgent_charge'   => isset( $data['urgent_charge'] ) ? sanitize_text_field( $data['urgent_charge'] ) : '',
            'standard_time'   => isset( $data['standard_time'] ) ? sanitize_text_field( $data['standard_time'] ) : '',
            'standard_charge' => isset( $data['standard_charge'] ) ? sanitize_text_field( $data['standard_charge'] ) : '',
        );

        if ( ! empty( $data['id'] ) && intval( $data['id'] ) > 0 ) {
            $id = intval( $data['id'] );
            $wpdb->update( self::table(), $record, array( 'id' => $id ) );
            return $id;
        }

        $result = $wpdb->insert( self::table(), $record );
        return $result === false ? false : (int) $wpdb->insert_id;
    }

    /**
     * Delete a single record.
     */
    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), array( 'id' => intval( $id ) ) );
    }

    /**
     * Delete multiple records by IDs.
     */
    public static function delete_many( $ids ) {
        global $wpdb;

        if ( empty( $ids ) ) {
            return 0;
        }

        $ids          = array_map( 'intval', $ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        return $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . self::table() . " WHERE id IN ({$placeholders})",
            $ids
        ) );
    }

    /**
     * Delete all records.
     */
    public static function delete_all() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE " . self::table() );
    }

    /* ------------------------------------------------------------------
     * CSV import
     * ----------------------------------------------------------------*/

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

        if ( ! in_array( 'title_code', $header_map, true ) ) {
            fclose( $handle );
            return new WP_Error( 'invalid_headers', 'CSV must include a "Title Code" column.' );
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
                $data[ $key ] = trim( (string) $value );
            }

            if ( empty( $data['title_code'] ) ) {
                $errors[] = sprintf( 'Row %d: Title Code is required.', $row_number );
                continue;
            }

            $result = self::save( $data );
            if ( $result === false ) {
                $errors[] = sprintf( 'Row %d: Failed to save "%s".', $row_number, $data['title_code'] );
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

    /* ------------------------------------------------------------------
     * CSV export
     * ----------------------------------------------------------------*/

    public static function export_csv() {
        $codes = self::get_all();

        $output = fopen( 'php://temp', 'r+' );
        fputs( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM

        fputcsv( $output, array( 'Title Code', 'Urgent', 'Charges', 'Normal', 'Charges2' ) );

        foreach ( $codes as $c ) {
            fputcsv( $output, array(
                $c->title_code,
                $c->urgent_time,
                $c->urgent_charge,
                $c->standard_time,
                $c->standard_charge,
            ) );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    /* ------------------------------------------------------------------
     * Helpers
     * ----------------------------------------------------------------*/

    private static function canonicalize_header( $header ) {
        if ( $header === null ) return null;
        $h = preg_replace( '/^\xEF\xBB\xBF/', '', trim( $header ) );
        $h = strtolower( $h );
        $h = preg_replace( '/[^a-z0-9]+/', '', $h );

        if ( $h === 'titlecode' || $h === 'title' || $h === 'code' ) return 'title_code';
        if ( $h === 'urgent' )                                        return 'urgent_time';
        if ( $h === 'charges' || $h === 'urgentcharges' || $h === 'urgentcharge' ) return 'urgent_charge';
        if ( $h === 'normal' || $h === 'standard' )                   return 'standard_time';
        if ( $h === 'charges2' || $h === 'normalcharges' || $h === 'standardcharge' || $h === 'standardcharges' ) return 'standard_charge';
        if ( $h === 'id' )                                            return 'id';

        return null;
    }
}
