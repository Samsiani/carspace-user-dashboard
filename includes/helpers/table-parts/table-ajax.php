<?php
/**
 * Table AJAX Handlers
 *
 * AJAX handlers for table functionality — ACF-free version.
 * All data reads go through model classes (Carspace_Invoice, Carspace_Port_Images)
 * and native post meta.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 *
 * Override Update (2025-08-24):
 * - Transport Price refresh now checks WooCommerce REGULAR Price first (sale price no longer used).
 *   If product regular price (get_regular_price) is non-empty, it overrides the computed transport price
 *   (tier + Location ID / route) and is returned immediately.
 *   If empty, original logic runs unchanged.
 */

defined('ABSPATH') || exit;

/**
 * AJAX handler for loading car images
 */
function carspace_ajax_get_car_images() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    if (!isset($_POST['product_id'])) {
        wp_send_json_error(__('Missing product ID', 'carspace-dashboard'));
    }

    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);

    if (!$product) {
        wp_send_json_error(__('Product not found', 'carspace-dashboard'));
    }

    $assigned_user = get_post_meta($product_id, 'assigned_user', true);
    if ((int) $assigned_user !== get_current_user_id() && !current_user_can('manage_options')) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    $response = array(
        'gallery_images' => array(),
        'port_images'    => array(),
        'car_title'      => get_the_title($product_id),
    );

    // Get WooCommerce gallery images
    $gallery_ids = $product->get_gallery_image_ids();

    if (!empty($gallery_ids)) {
        foreach ($gallery_ids as $attachment_id) {
            $full_img_url  = wp_get_attachment_image_url($attachment_id, 'full');
            $thumb_img_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
            $image_title   = get_the_title($attachment_id);

            if ($full_img_url) {
                $response['gallery_images'][] = array(
                    'full'  => $full_img_url,
                    'thumb' => $thumb_img_url ?: $full_img_url,
                    'title' => $image_title ?: __('Gallery Image', 'carspace-dashboard'),
                );
            }
        }
    }

    // Get port images from custom table
    $port_image_ids = Carspace_Port_Images::get($product_id);

    if (!empty($port_image_ids)) {
        foreach ($port_image_ids as $att_id) {
            $full_img_url  = wp_get_attachment_image_url($att_id, 'full');
            $thumb_img_url = wp_get_attachment_image_url($att_id, 'thumbnail');
            $image_title   = get_the_title($att_id);

            if ($full_img_url) {
                $response['port_images'][] = array(
                    'full'  => $full_img_url,
                    'thumb' => $thumb_img_url ?: $full_img_url,
                    'title' => $image_title ?: __('Port Image', 'carspace-dashboard'),
                );
            }
        }
    }

    wp_send_json_success($response);
    exit;
}

add_action('wp_ajax_get_car_images', 'carspace_ajax_get_car_images');

/**
 * AJAX handler for fetching shipping information.
 */
function carspace_ajax_get_shipping_info() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    if (!isset($_POST['product_id'])) {
        wp_send_json_error(__('Missing product ID', 'carspace-dashboard'));
        exit;
    }

    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);

    if (!$product) {
        wp_send_json_error(__('Product not found', 'carspace-dashboard'));
        exit;
    }

    $assigned_user = get_post_meta($product_id, 'assigned_user', true);
    if ((int) $assigned_user !== get_current_user_id() && !current_user_can('manage_options')) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    // Get car VIN
    $vin = $product->get_sku();

    // Get all shipping data from product attributes ONLY
    $pickup_date      = $product->get_attribute('date-of-pickup');
    $delivery_date    = $product->get_attribute('deliver-date');
    $loading_date     = $product->get_attribute('loading-date');
    $departure_date   = $product->get_attribute('departure-date');
    $arrival_date     = $product->get_attribute('arrival-date');
    $booking_number   = $product->get_attribute('booking-number');
    $container_number = $product->get_attribute('container-number');
    $shipline_name    = $product->get_attribute('shipline-name');

    // Get tracking URL - handle both "tracking-url" and "tracking-link"
    $tracking_url = $product->get_attribute('tracking-url');
    if (empty($tracking_url)) {
        $tracking_url = $product->get_attribute('tracking-link');
    }

    // Helper to render a row, defaulting to "—"
    $render = function($label, $value) {
        $display = $value !== '' ? esc_html($value) : '&mdash;';
        return "<tr><th>{$label}</th><td>{$display}</td></tr>";
    };

    $html = '<div class="shipping-details p-2">';
    $html .= '<h6>' . sprintf(
                   esc_html__('Shipping Details for %s', 'carspace-dashboard'),
                   esc_html(get_the_title($product_id) . ' - ' . $vin)
              ) . '</h6>';
    $html .= '<table class="table table-striped"><tbody>';
    $html .= $render(esc_html__('Pickup Date', 'carspace-dashboard'), $pickup_date);
    $html .= $render(esc_html__('Delivery Date', 'carspace-dashboard'), $delivery_date);
    $html .= $render(esc_html__('Loading Date', 'carspace-dashboard'), $loading_date);
    $html .= $render(esc_html__('Departure Date', 'carspace-dashboard'), $departure_date);
    $html .= $render(esc_html__('Arrival Date', 'carspace-dashboard'), $arrival_date);
    $html .= $render(esc_html__('Booking Number', 'carspace-dashboard'), $booking_number);
    $html .= $render(esc_html__('Container Number', 'carspace-dashboard'), $container_number);
    $html .= $render(esc_html__('Shipline Name', 'carspace-dashboard'), $shipline_name);

    if (!empty($tracking_url)) {
        $link = sprintf(
            '<a href="%1$s" class="btn btn-sm btn-primary newtracking" target="_blank" rel="noopener noreferrer">%2$s</a>',
            esc_url($tracking_url),
            esc_html__('თრექინგი', 'carspace-dashboard')
        );
        $html .= "<tr><th>" . esc_html__('Tracking URL', 'carspace-dashboard') . "</th><td>{$link}</td></tr>";
    } else {
        $html .= $render(esc_html__('Tracking URL', 'carspace-dashboard'), '');
    }

    $html .= '</tbody></table>';
    $html .= '<div class="mt-3 text-muted small text-end">'
           . sprintf(
               esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
               date_i18n(get_option('date_format').' '.get_option('time_format')),
               esc_html(wp_get_current_user()->display_name)
           )
           . '</div>';
    $html .= '</div>';

    wp_send_json_success($html);
    exit;
}

add_action('wp_ajax_get_shipping_info', 'carspace_ajax_get_shipping_info');

/**
 * AJAX handler for getting invoices by VIN with financial summary
 */
function carspace_ajax_get_invoices() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    if (!isset($_POST['vin'])) {
        wp_send_json_error(__('Missing VIN parameter', 'carspace-dashboard'));
    }

    $vin = sanitize_text_field($_POST['vin']);

    try {
        $product_id = wc_get_product_id_by_sku($vin);

        if ($product_id) {
            $assigned_user = get_post_meta($product_id, 'assigned_user', true);
            if ((int) $assigned_user !== get_current_user_id() && !current_user_can('manage_options')) {
                wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
            }
        }

        // Fetch invoice posts that reference this VIN
        $args = array(
            'post_type'      => 'invoice',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => array(
                array(
                    'key'     => 'vin',
                    'value'   => $vin,
                    'compare' => '=',
                ),
            ),
        );

        $invoice_posts = get_posts($args);

        $summary = array(
            'car_price'                => 0,
            'transportation'           => 0,
            'dealer_fee'               => 0,
            'commission'               => 0,
            'subtotal'                 => 0,
            'subtotal_with_dealer_fee' => 0,
            'paid_balance'             => 0,
            'unpaid_balance'           => 0,
        );

        foreach ($invoice_posts as $inv_post) {
            $invoice_id = $inv_post->ID;
            $invoice    = Carspace_Invoice::find($invoice_id);

            if (!$invoice) {
                continue;
            }

            // Sum item amounts
            $invoice_total = 0;
            if (!empty($invoice->items)) {
                foreach ($invoice->items as $item) {
                    $invoice_total += floatval($item->amount);
                }
            }

            $dealer_fee_price = floatval($invoice->dealer_fee);
            $extra_commission = floatval($invoice->commission);

            $summary['dealer_fee']  += $dealer_fee_price;
            $summary['commission']  += $extra_commission;
            $summary['subtotal']    += $invoice_total;

            // Categorize by invoice type
            $invoice_type = $invoice->invoice_type;
            if ($invoice_type == 'ავტომობილის საფასური') {
                $summary['car_price'] += $invoice_total;
            } elseif ($invoice_type == 'ტრანსპორტირების საფასური') {
                $summary['transportation'] += $invoice_total;
            }

            // Check paid status from model
            $is_paid = (strtolower($invoice->status) === 'paid');

            $total_with_fee = $invoice_total + $dealer_fee_price + $extra_commission;
            if ($is_paid) {
                $summary['paid_balance'] += $total_with_fee;
            } else {
                $summary['unpaid_balance'] += $total_with_fee;
            }
        }

        $summary['subtotal_with_dealer_fee'] = $summary['subtotal'] + $summary['dealer_fee'] + $summary['commission'];

        $html = '<div class="invoice-summary mb-4">';
        $html .= '<div class="card">';
        $html .= '<div class="card-header bg-primary bg-opacity-10 fw-bold">' . esc_html__('Financial Summary', 'carspace-dashboard') . '</div>';
        $html .= '<div class="card-body"><div class="row">';

        $html .= '<div class="col-md-6"><table class="table table-sm table-borderless mb-0">';
        $html .= '<tr><td>' . esc_html__('Car Price:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['car_price'], 2) . '</td></tr>';
        $html .= '<tr><td>' . esc_html__('Transportation:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['transportation'], 2) . '</td></tr>';
        $html .= '<tr><td>' . esc_html__('Dealer Fee:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['dealer_fee'], 2) . '</td></tr>';
        $html .= '<tr><td>' . esc_html__('Commission:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['commission'], 2) . '</td></tr>';
        $html .= '</table></div>';

        $html .= '<div class="col-md-6"><table class="table table-sm table-borderless mb-0">';
        $html .= '<tr><td>' . esc_html__('Subtotal:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['subtotal'], 2) . '</td></tr>';
        $html .= '<tr><td>' . esc_html__('Total with Fee:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['subtotal_with_dealer_fee'], 2) . '</td></tr>';
        $html .= '<tr class="' . ($summary['paid_balance'] > 0 ? 'text-success' : '') . '"><td>' . esc_html__('Paid:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['paid_balance'], 2) . '</td></tr>';
        $html .= '<tr class="' . ($summary['unpaid_balance'] > 0 ? 'text-danger' : '') . '"><td>' . esc_html__('Unpaid:', 'carspace-dashboard') . '</td><td class="text-end fw-bold">$' . number_format($summary['unpaid_balance'], 2) . '</td></tr>';
        $html .= '</table></div>';

        $html .= '</div></div></div></div>';

        add_filter('carspace_invoice_amount_display', 'carspace_modify_invoice_amount_display', 10, 2);
        $html .= carspace_get_invoice_table_html($vin);
        remove_filter('carspace_invoice_amount_display', 'carspace_modify_invoice_amount_display');

        wp_send_json_success($html);

    } catch (Exception $e) {
        wp_send_json_error('Error processing invoice summary: ' . $e->getMessage());
    }

    exit;
}

add_action('wp_ajax_get_invoices_by_vin', 'carspace_ajax_get_invoices');

/**
 * Filter function to modify invoice amount display
 */
function carspace_modify_invoice_amount_display($amount_display, $invoice_id) {
    $invoice = Carspace_Invoice::find($invoice_id);

    $amount = 0;
    if ($invoice && !empty($invoice->items)) {
        foreach ($invoice->items as $item) {
            $amount += floatval($item->amount);
        }
    }

    $dealer_fee = $invoice ? floatval($invoice->dealer_fee) : 0;
    $total = $amount + $dealer_fee;

    return number_format($total, 2, ',', ' ') . ' $';
}

/**
 * AJAX handler for saving receiver information
 */
function carspace_ajax_save_receiver_info() {
    if (!check_ajax_referer('carspace_receiver_nonce', 'receiver_nonce', false)) {
        wp_send_json_error('Invalid security token sent.');
        return;
    }
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied.');
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$product_id || !wc_get_product($product_id)) {
        wp_send_json_error('Invalid product ID.');
        return;
    }

    $receiver_name = isset($_POST['mimgebi_piri']) ? sanitize_text_field($_POST['mimgebi_piri']) : '';
    $receiver_id   = isset($_POST['mimgebis_piradi_nomeri']) ? sanitize_text_field($_POST['mimgebis_piradi_nomeri']) : '';

    if (empty($receiver_name) || empty($receiver_id)) {
        wp_send_json_error('All fields are required.');
        return;
    }

    update_post_meta($product_id, '_receiver_name', $receiver_name);
    update_post_meta($product_id, '_receiver_personal_id', $receiver_id);

    $response_html  = '<div class="receiver-info">';
    $response_html .= '<strong>' . esc_html($receiver_name) . '</strong>';
    $response_html .= '<br><small class="text-muted">' . esc_html($receiver_id) . '</small>';
    $response_html .= '</div>';

    wp_send_json_success([
        'message' => 'Receiver information saved successfully.',
        'html'    => $response_html,
    ]);

    die();
}

add_action('wp_ajax_save_receiver_info', 'carspace_ajax_save_receiver_info');

/**
 * AJAX handler for refreshing transport prices
 *
 * UPDATED: Prefer Location ID-based pricing; fallback to route (Auction City + Loading Port).
 * OVERRIDE (Updated 2025-08-24): If WooCommerce REGULAR Price is set for the product, it overrides the computed transport price.
 * Accepts:
 * - product_id (required)
 * - location_id (optional, preferred)
 * - auction_city + loading_port (optional, fallback)
 */
function carspace_ajax_refresh_transport_price() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    if (!isset($_POST['product_id'])) {
        wp_send_json_error('Missing required parameter: product_id');
    }

    $product_id   = intval($_POST['product_id']);
    $auction_city = isset($_POST['auction_city']) ? sanitize_text_field($_POST['auction_city']) : '';
    $loading_port = isset($_POST['loading_port']) ? sanitize_text_field($_POST['loading_port']) : '';
    $location_id  = isset($_POST['location_id']) ? intval(preg_replace('/\D+/', '', (string) $_POST['location_id'])) : 0;

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Invalid product');
    }

    $assigned_user = get_post_meta($product_id, 'assigned_user', true);
    if ((int) $assigned_user !== get_current_user_id() && !current_user_can('manage_options')) {
        wp_send_json_error(__('Not authorized', 'carspace-dashboard'));
    }

    // -------- OVERRIDE BLOCK: Use REGULAR Price if present --------
    $regular_price = $product->get_regular_price();
    if ($regular_price !== '') {
        $html = '<td data-label="Transport Price" class="text-center transport-price-column"
             data-auction-city="' . esc_attr($auction_city) . '"
             data-loading-port="' . esc_attr($loading_port) . '"
             data-location-id="' . esc_attr($location_id) . '"
             data-product-id="' . esc_attr($product_id) . '">
             <div class="transport-price-cell">
                <strong class="d-block price-amount">' . wc_price( (float) $regular_price ) . '</strong>
                <small class="text-muted d-block">' . esc_html__('Overridden by Regular Price', 'carspace-dashboard') . '</small>
             </div>
             </td>';
        wp_send_json_success(['html' => $html]);
    }
    // -------- END OVERRIDE BLOCK -------------------------------

    if ($location_id <= 0 && (empty($auction_city) || empty($loading_port))) {
        $auction_city = $auction_city ?: $product->get_attribute('Auction City');
        $loading_port = $loading_port ?: $product->get_attribute('Loading Port');
        $location_attr_raw = $product->get_attribute('Location ID');
        if ($location_attr_raw) {
            $maybe_id = intval(preg_replace('/\D+/', '', (string) $location_attr_raw));
            if ($maybe_id > 0) {
                $location_id = $maybe_id;
            }
        }
    }

    $user_tier_obj = new TPC_User_Tier();
    $current_tier  = $user_tier_obj->get_user_tier(get_current_user_id(), true);

    $tier_key = '';
    if (is_string($current_tier) && preg_match('/^price\d+$/', $current_tier)) {
        $tier_key = $current_tier;
    } elseif ($current_tier === 'base_price') {
        $tier_key = 'base_price';
    } elseif (is_numeric($current_tier)) {
        $tier_key = 'price' . intval($current_tier);
    }

    $html = '<td data-label="Transport Price" class="text-center transport-price-column"
             data-auction-city="' . esc_attr($auction_city) . '"
             data-loading-port="' . esc_attr($loading_port) . '"
             data-location-id="' . esc_attr($location_id) . '"
             data-product-id="' . esc_attr($product_id) . '">';

    $transport_price = null;

    if ($location_id > 0) {
        global $wpdb;
        $tpc_table = $wpdb->prefix . 'tpc_prices';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tpc_table} WHERE id = %d LIMIT 1", $location_id));
        if ($row) {
            if ($tier_key && isset($row->$tier_key) && $row->$tier_key !== '' && $row->$tier_key !== null) {
                $transport_price = floatval($row->$tier_key);
            } else {
                $transport_price = floatval($row->base_price);
            }
        }
    }

    if ($transport_price === null && !empty($auction_city) && !empty($loading_port)) {
        $db = new TPC_Database();
        $price_data = $db->get_price_by_route($auction_city, $loading_port);
        if ($price_data) {
            if ($tier_key && isset($price_data->$tier_key)) {
                $transport_price = floatval($price_data->$tier_key);
            } else {
                $transport_price = floatval($price_data->base_price);
            }
        }
    }

    if ($transport_price !== null) {
        $html .= '<div class="transport-price-cell">';
        $html .= '<strong class="d-block price-amount">' . esc_html(number_format($transport_price, 2)) . ' $</strong>';
        $html .= '<small class="text-muted d-block">';
        $html .= sprintf(
            esc_html__('Updated: %s<br>By: %s', 'carspace-dashboard'),
            esc_html(current_time('Y-m-d H:i:s')),
            esc_html(wp_get_current_user()->display_name)
        );
        $html .= '</small>';
        $html .= '</div>';
    } else {
        $html .= '<span class="text-muted">—</span>';
    }
    $html .= '</td>';

    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_refresh_transport_price', 'carspace_ajax_refresh_transport_price');
