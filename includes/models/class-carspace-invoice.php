<?php
/**
 * Invoice Model
 *
 * Wraps carspace_invoices + carspace_invoice_items tables.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Invoice {

    /**
     * Find invoice by post ID.
     *
     * @param int $post_id
     * @return object|null Invoice row with ->items array attached.
     */
    public static function find($post_id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_invoices WHERE post_id = %d",
            $post_id
        ));

        if (!$row) {
            return null;
        }

        $row->items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_invoice_items WHERE invoice_id = %d ORDER BY sort_order ASC, id ASC",
            $row->id
        ));

        return $row;
    }

    /**
     * Get items for an invoice by invoice table ID.
     */
    public static function get_items($invoice_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_invoice_items WHERE invoice_id = %d ORDER BY sort_order ASC, id ASC",
            $invoice_id
        ));
    }

    /**
     * Create invoice + items.
     *
     * @param array $data Invoice fields.
     * @param array $items Array of item arrays.
     * @return int|false Invoice table ID or false.
     */
    public static function create($data, $items = array()) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'carspace_invoices',
            array(
                'post_id'               => $data['post_id'],
                'invoice_type'          => isset($data['invoice_type']) ? $data['invoice_type'] : '',
                'status'                => isset($data['status']) ? $data['status'] : 'unpaid',
                'customer_type'         => isset($data['customer_type']) ? $data['customer_type'] : '',
                'customer_name'         => isset($data['customer_name']) ? $data['customer_name'] : '',
                'customer_email'        => isset($data['customer_email']) ? $data['customer_email'] : '',
                'customer_company_name' => isset($data['customer_company_name']) ? $data['customer_company_name'] : '',
                'customer_personal_id'  => isset($data['customer_personal_id']) ? $data['customer_personal_id'] : '',
                'company_ident_number'  => isset($data['company_ident_number']) ? $data['company_ident_number'] : '',
                'invoice_date'          => !empty($data['invoice_date']) ? $data['invoice_date'] : null,
                'dealer_fee'            => isset($data['dealer_fee']) ? floatval($data['dealer_fee']) : 0,
                'dealer_fee_note'       => isset($data['dealer_fee_note']) ? $data['dealer_fee_note'] : '',
                'commission'            => isset($data['commission']) ? floatval($data['commission']) : 0,
                'subtotal'              => isset($data['subtotal']) ? floatval($data['subtotal']) : 0,
                'amount_paid'           => isset($data['amount_paid']) ? floatval($data['amount_paid']) : 0,
                'receipt_image_id'      => !empty($data['receipt_image_id']) ? intval($data['receipt_image_id']) : null,
                'receipt_image_url'     => isset($data['receipt_image_url']) ? $data['receipt_image_url'] : '',
                'owner_user_id'         => !empty($data['owner_user_id']) ? intval($data['owner_user_id']) : null,
            ),
            array('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%f','%s','%f','%f','%f','%d','%s','%d')
        );

        $invoice_id = $wpdb->insert_id;
        if (!$invoice_id) {
            return false;
        }

        if (!empty($items)) {
            self::insert_items($invoice_id, $items);
        }

        return $invoice_id;
    }

    /**
     * Update invoice metadata.
     *
     * @param int   $post_id
     * @param array $data Columns to update.
     * @return bool
     */
    public static function update($post_id, $data) {
        global $wpdb;

        $allowed = array(
            'invoice_type', 'status', 'customer_type', 'customer_name', 'customer_email',
            'customer_company_name', 'customer_personal_id', 'company_ident_number',
            'invoice_date', 'dealer_fee', 'dealer_fee_note', 'commission',
            'subtotal', 'amount_paid', 'receipt_image_id', 'receipt_image_url',
            'owner_user_id',
        );

        $update = array();
        $formats = array();

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $update[$key] = $value;
            if (in_array($key, array('dealer_fee', 'commission', 'subtotal', 'amount_paid'), true)) {
                $formats[] = '%f';
            } elseif (in_array($key, array('post_id', 'receipt_image_id', 'owner_user_id'), true)) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }

        if (empty($update)) {
            return false;
        }

        return (bool) $wpdb->update(
            $wpdb->prefix . 'carspace_invoices',
            $update,
            array('post_id' => $post_id),
            $formats,
            array('%d')
        );
    }

    /**
     * Replace all items for an invoice (DELETE + INSERT).
     *
     * @param int   $post_id
     * @param array $items
     */
    public static function update_items($post_id, $items) {
        global $wpdb;

        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}carspace_invoices WHERE post_id = %d",
            $post_id
        ));

        if (!$invoice) {
            return;
        }

        $wpdb->delete($wpdb->prefix . 'carspace_invoice_items', array('invoice_id' => $invoice->id), array('%d'));
        self::insert_items($invoice->id, $items);
    }

    /**
     * Batch insert items for an invoice.
     */
    private static function insert_items($invoice_id, $items) {
        global $wpdb;

        if (empty($items)) {
            return;
        }

        $table = $wpdb->prefix . 'carspace_invoice_items';
        $values = array();
        $placeholders = array();

        foreach ($items as $i => $item) {
            $sale_date   = !empty($item['sale_date']) ? $item['sale_date'] : '';
            $make        = isset($item['make']) ? $item['make'] : '';
            $model       = isset($item['model']) ? $item['model'] : '';
            $year        = !empty($item['year']) ? intval($item['year']) : 0;
            $vin         = isset($item['vin']) ? $item['vin'] : '';
            $description = isset($item['description']) ? $item['description'] : '';
            $quantity    = isset($item['quantity']) ? floatval($item['quantity']) : 1;
            $unit_price  = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
            $amount      = isset($item['amount']) ? floatval($item['amount']) : ($quantity * $unit_price);
            $paid        = isset($item['paid']) ? floatval($item['paid']) : 0;

            $placeholders[] = '(%d, %s, %s, %s, %d, %s, %s, %f, %f, %f, %f, %d)';
            $values[] = $invoice_id;
            $values[] = $sale_date;
            $values[] = $make;
            $values[] = $model;
            $values[] = $year;
            $values[] = $vin;
            $values[] = $description;
            $values[] = $quantity;
            $values[] = $unit_price;
            $values[] = $amount;
            $values[] = $paid;
            $values[] = $i;
        }

        $sql = "INSERT INTO {$table}
                (invoice_id, sale_date, make, model, year, vin, description, quantity, unit_price, amount, paid, sort_order)
                VALUES " . implode(', ', $placeholders);

        $wpdb->query($wpdb->prepare($sql, $values));
    }

    /**
     * Get all invoices matching a VIN.
     *
     * @param string $vin
     * @return array
     */
    public static function get_by_vin($vin) {
        global $wpdb;

        $vin = strtolower(trim($vin));
        if ($vin === '') {
            return array();
        }

        // it.vin_lower is a STORED generated column on LOWER(vin) — see
        // Carspace_Activator::add_vin_lower_column(). Indexed by
        // idx_vin_lower_invoice (vin_lower, invoice_id).
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, it.amount AS item_amount, it.vin AS item_vin
             FROM {$wpdb->prefix}carspace_invoices i
             INNER JOIN {$wpdb->prefix}carspace_invoice_items it ON it.invoice_id = i.id
             WHERE it.vin_lower = %s",
            $vin
        ));
    }

    /**
     * Get buyer name for a VIN.
     *
     * @param string $vin
     * @return string
     */
    public static function get_buyer_by_vin($vin) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT i.customer_name, i.customer_company_name, i.customer_type
             FROM {$wpdb->prefix}carspace_invoices i
             INNER JOIN {$wpdb->prefix}carspace_invoice_items it ON it.invoice_id = i.id
             WHERE it.vin_lower = %s
             LIMIT 1",
            strtolower(trim($vin))
        ));

        if (!$row) {
            return '';
        }

        if ($row->customer_type === 'Company' && !empty($row->customer_company_name)) {
            return $row->customer_company_name;
        }

        return !empty($row->customer_name) ? $row->customer_name : '';
    }

    /**
     * Batch VIN lookup — returns both buyer names and invoice data for a list of VINs.
     *
     * @param array $vins
     * @return array ['buyers' => [vin => name], 'invoices' => [vin => [invoice_data, ...]]]
     */
    public static function batch_vin_lookup($vins) {
        global $wpdb;

        $result = array('buyers' => array(), 'invoices' => array());

        if (empty($vins)) {
            return $result;
        }

        $vins_clean = array();
        foreach ($vins as $v) {
            $v = strtolower(trim($v));
            if ($v !== '') {
                $vins_clean[] = $v;
            }
        }

        if (empty($vins_clean)) {
            return $result;
        }

        $placeholders = implode(',', array_fill(0, count($vins_clean), '%s'));

        // $vins_clean is already lowercased above. Match against
        // it.vin_lower so the query hits idx_vin_lower_invoice.
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT i.post_id, i.invoice_type, i.status, i.customer_name, i.customer_company_name,
                    i.customer_type, i.dealer_fee, i.commission, i.subtotal,
                    it.vin AS item_vin, it.amount AS item_amount,
                    p.post_title, p.ID as wp_post_id
             FROM {$wpdb->prefix}carspace_invoices i
             INNER JOIN {$wpdb->prefix}carspace_invoice_items it ON it.invoice_id = i.id
             LEFT JOIN {$wpdb->posts} p ON p.ID = i.post_id
             WHERE it.vin_lower IN ({$placeholders})",
            $vins_clean
        ));

        foreach ($rows as $row) {
            $vin_key = strtolower(trim($row->item_vin));

            // Buyer map
            if (!isset($result['buyers'][$vin_key])) {
                if ($row->customer_type === 'Company' && !empty($row->customer_company_name)) {
                    $result['buyers'][$vin_key] = $row->customer_company_name;
                } elseif (!empty($row->customer_name)) {
                    $result['buyers'][$vin_key] = $row->customer_name;
                }
            }

            // Invoices map
            if (!isset($result['invoices'][$vin_key])) {
                $result['invoices'][$vin_key] = array();
            }

            $result['invoices'][$vin_key][] = array(
                'ID'        => $row->post_id,
                'title'     => $row->post_title,
                'amount'    => floatval($row->item_amount) + floatval($row->dealer_fee) + floatval($row->commission),
                'type'      => $row->invoice_type,
                'status'    => $row->status,
            );
        }

        return $result;
    }

    /**
     * Recalculate subtotal from items + dealer_fee + commission.
     *
     * @param int $post_id
     * @return float New subtotal.
     */
    public static function recalculate_subtotal($post_id) {
        global $wpdb;

        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT id, dealer_fee, commission FROM {$wpdb->prefix}carspace_invoices WHERE post_id = %d",
            $post_id
        ));

        if (!$invoice) {
            return 0;
        }

        $items_total = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}carspace_invoice_items WHERE invoice_id = %d",
            $invoice->id
        ));

        $subtotal = $items_total + floatval($invoice->dealer_fee) + floatval($invoice->commission);

        $wpdb->update(
            $wpdb->prefix . 'carspace_invoices',
            array('subtotal' => $subtotal),
            array('id' => $invoice->id),
            array('%f'),
            array('%d')
        );

        return $subtotal;
    }

    /**
     * Get invoice subtotal.
     *
     * @param int $post_id
     * @return float
     */
    public static function get_invoice_total($post_id) {
        global $wpdb;

        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT subtotal FROM {$wpdb->prefix}carspace_invoices WHERE post_id = %d",
            $post_id
        ));
    }

    /**
     * Check if an invoice exists in the custom table.
     *
     * @param int $post_id
     * @return bool
     */
    public static function exists($post_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM {$wpdb->prefix}carspace_invoices WHERE post_id = %d",
            $post_id
        ));
    }
}
