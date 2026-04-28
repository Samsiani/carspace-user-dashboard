<?php
/**
 * Table Data Provider Functions
 *
 * Functions for retrieving data for car tables.
 * All invoice data comes from Carspace_Invoice model (custom tables).
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

/**
 * Single static cache for batch VIN lookup results.
 * Stores ['buyers' => [vin => name], 'invoices' => [vin => [...]]]
 *
 * @param array|null $data  Data to store (when $set is true).
 * @param bool       $set   Whether to store data.
 * @return array|null Cached data or null.
 */
function _carspace_batch_cache($data = null, $set = false) {
    static $cache = null;
    if ($set) {
        $cache = $data;
    }
    return $cache;
}

/**
 * Preload VIN-to-buyer-name map for a batch of VINs.
 * Uses Carspace_Invoice::batch_vin_lookup() and stores in static cache.
 *
 * @param array $vins Array of VIN strings.
 */
function carspace_preload_vin_buyer_map($vins) {
    if (empty($vins)) {
        $cache = _carspace_batch_cache();
        if ($cache === null) {
            _carspace_batch_cache(array('buyers' => array(), 'invoices' => array()), true);
        }
        return;
    }

    $cache = _carspace_batch_cache();
    if ($cache !== null) {
        return; // Already fetched
    }

    $result = Carspace_Invoice::batch_vin_lookup($vins);
    _carspace_batch_cache($result, true);
}

/**
 * Preload VIN-to-invoices map for a batch of VINs.
 * Uses Carspace_Invoice::batch_vin_lookup() and stores in static cache.
 *
 * @param array $vins Array of VIN strings.
 */
function carspace_preload_vin_invoices_map($vins) {
    if (empty($vins)) {
        $cache = _carspace_batch_cache();
        if ($cache === null) {
            _carspace_batch_cache(array('buyers' => array(), 'invoices' => array()), true);
        }
        return;
    }

    $cache = _carspace_batch_cache();
    if ($cache !== null) {
        return; // Already fetched
    }

    $result = Carspace_Invoice::batch_vin_lookup($vins);
    _carspace_batch_cache($result, true);
}

/**
 * Extract buyer name from an invoice by ID.
 * Uses Carspace_Invoice::find() to read customer_name / customer_company_name.
 *
 * @param int $invoice_id Invoice ID.
 * @return string Buyer name or empty string.
 */
function _carspace_extract_buyer_name($invoice_id) {
    $invoice = Carspace_Invoice::find($invoice_id);
    if (!$invoice) {
        return '';
    }

    if (!empty($invoice->customer_name)) {
        return $invoice->customer_name;
    }

    if (!empty($invoice->customer_company_name)) {
        return $invoice->customer_company_name;
    }

    return '';
}

/**
 * Get buyer name from invoices for a specific VIN.
 *
 * @param string $vin Vehicle Identification Number.
 * @return string Buyer name or empty string.
 */
function carspace_get_buyer_name_from_invoices($vin) {
    if (empty($vin)) {
        return '';
    }

    $vin_key = strtolower(trim($vin));

    // Check preloaded cache first
    $cache = _carspace_batch_cache();
    if (is_array($cache) && isset($cache['buyers'])) {
        return isset($cache['buyers'][$vin_key]) ? $cache['buyers'][$vin_key] : '';
    }

    // Fallback: single VIN query via model
    $buyer = Carspace_Invoice::get_buyer_by_vin($vin);
    return !empty($buyer) ? $buyer : '';
}

/**
 * Get all invoices for a VIN.
 *
 * @param string $vin Vehicle Identification Number.
 * @return array Array of invoice data arrays.
 */
function carspace_get_all_invoices_by_vin($vin) {
    if (empty($vin)) {
        return array();
    }

    $vin_key = strtolower(trim($vin));

    // Check preloaded cache first
    $cache = _carspace_batch_cache();
    if (is_array($cache) && isset($cache['invoices'])) {
        return isset($cache['invoices'][$vin_key]) ? $cache['invoices'][$vin_key] : array();
    }

    // Fallback: single VIN query via model, then format
    $raw_invoices = Carspace_Invoice::get_by_vin($vin);
    if (empty($raw_invoices)) {
        return array();
    }

    $invoices = array();
    foreach ($raw_invoices as $inv) {
        $invoices[] = array(
            'ID'        => isset($inv->id) ? $inv->id : (isset($inv->ID) ? $inv->ID : 0),
            'title'     => isset($inv->title) ? $inv->title : (isset($inv->invoice_number) ? $inv->invoice_number : ''),
            'permalink' => isset($inv->permalink) ? $inv->permalink : '',
            'amount'    => isset($inv->amount) ? $inv->amount : (isset($inv->total_amount) ? $inv->total_amount : 0),
            'type'      => isset($inv->type) ? $inv->type : (isset($inv->invoice_type) ? $inv->invoice_type : ''),
            'status'    => isset($inv->status) ? $inv->status : 'unknown',
        );
    }

    return $invoices;
}

/**
 * Generate HTML for invoice table content.
 *
 * @param string $vin Vehicle Identification Number.
 * @return string HTML for invoice table.
 */
function carspace_get_invoice_table_html($vin) {
    $invoices = carspace_get_all_invoices_by_vin($vin);
    $output = '';

    if (!empty($invoices)) {
        $output .= '<div class="table-responsive">
            <table class="table table-hover invoice-modal-table">
                <thead>
                    <tr>
                        <th>' . esc_html__('Invoice Number', 'carspace-dashboard') . '</th>
                        <th>' . esc_html__('Amount', 'carspace-dashboard') . '</th>
                        <th>' . esc_html__('Invoice Type', 'carspace-dashboard') . '</th>
                        <th>' . esc_html__('Status', 'carspace-dashboard') . '</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($invoices as $invoice) {
            $status_class = '';
            $status_text = '';

            switch ($invoice['status']) {
                case 'paid':
                    $status_class = 'bg-success';
                    $status_text = __('Paid', 'carspace-dashboard');
                    break;
                case 'partly_paid':
                    $status_class = 'bg-warning text-dark';
                    $status_text = __('Partly Paid', 'carspace-dashboard');
                    break;
                case 'unpaid':
                    $status_class = 'bg-danger';
                    $status_text = __('Unpaid', 'carspace-dashboard');
                    break;
                default:
                    $status_class = 'bg-secondary';
                    $status_text = __('Unknown', 'carspace-dashboard');
            }

            $output .= '<tr>
                <td>
                    <a href="' . esc_url($invoice['permalink']) . '" target="_blank">
                        ' . esc_html($invoice['title']) . '
                    </a>
                </td>
                <td>' . wc_price($invoice['amount']) . '</td>
                <td>' . esc_html($invoice['type']) . '</td>
                <td>
                    <span class="badge ' . esc_attr($status_class) . '">
                        ' . esc_html($status_text) . '
                    </span>
                </td>
            </tr>';
        }

        $output .= '</tbody></table></div>';
    } else {
        $output .= '<div class="alert alert-info">' . esc_html__('No invoices found for this vehicle.', 'carspace-dashboard') . '</div>';
    }

    return $output;
}

/**
 * Get the appropriate status class based on car status.
 *
 * @param string $status Car status.
 * @return string CSS class.
 */
function carspace_get_status_class($status) {
    switch ($status) {
        case 'in_transit':
            return 'border-warning';
        case 'delivered':
            return 'border-success';
        case 'pending':
            return 'border-primary';
        case 'problem':
            return 'border-danger';
        default:
            return '';
    }
}
