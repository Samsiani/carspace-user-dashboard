<?php
/**
 * Dashboard Cards functionality
 *
 * Provides functions for generating and displaying dashboard stat cards
 *
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

/**
 * Get dashboard cards data for a specific user
 *
 * @param int $user_id User ID
 * @return array Associative array of cards data
 */
function carspace_get_dashboard_cards_data($user_id) {
    if (!$user_id) {
        return array();
    }

    // Check transient cache (5 min TTL)
    $transient_key = 'carspace_cards_' . $user_id;
    $cached = get_transient($transient_key);
    if (false !== $cached) {
        return $cached;
    }

    // Get all user's assigned cars
    $assigned_products = carspace_get_user_assigned_cars($user_id);

    // Initialize counters
    $stats = array(
        'total_car' => count($assigned_products),
        'car_delivered' => 0,
        'car_not_delivered' => 0, // Updated logic below
        'booking_container' => 0,
        'loaded_container' => 0,
        'car_not_loaded' => 0,    // Updated logic below
    );

    // Batch-prime meta cache for all products and batch-load WC products
    $product_ids = wp_list_pluck($assigned_products, 'ID');
    if (!empty($product_ids)) {
        update_meta_cache('post', $product_ids);
        update_object_term_cache($product_ids, 'product');
    }

    $wc_products_map = array();
    if (!empty($product_ids)) {
        $wc_products = wc_get_products(array(
            'include' => $product_ids,
            'limit'   => count($product_ids),
            'return'  => 'objects',
        ));
        foreach ($wc_products as $wc_p) {
            $wc_products_map[$wc_p->get_id()] = $wc_p;
        }
    }

    // Process each car to count various statuses
    foreach ($assigned_products as $product_post) {
        $product_id = $product_post->ID;
        $product = isset($wc_products_map[$product_id]) ? $wc_products_map[$product_id] : null;

        if (!$product) {
            continue;
        }

        $has_booking  = carspace_has_booking_number($product);  // booking-number attribute exists
        $is_loaded    = carspace_is_car_loaded($product);       // container-number attribute exists
        $is_delivered = carspace_is_car_delivered($product_id); // has port images in custom table

        // First check if car is delivered
        if ($is_delivered) {
            $stats['car_delivered']++;
            continue; // Skip other checks since delivered cars shouldn't be counted elsewhere
        }

        // For non-delivered cars, keep existing booking/loaded buckets
        if ($is_loaded) {
            // If car is loaded, only count in loaded container
            $stats['loaded_container']++;
            continue;
        }

        if ($has_booking) {
            // If car has booking but not loaded, only count in booking container
            $stats['booking_container']++;
            continue;
        }

        // New logic for the remaining "neither booking-number nor container-number" cars:
        // - Car Not Delivered: no booking, no container, no gallery photos, no featured image
        // - Car Not Loaded:    no booking, no container, has featured image
        $has_featured = (bool) $product->get_image_id();
        $gallery_ids  = $product->get_gallery_image_ids();
        $has_gallery  = !empty($gallery_ids);

        if (!$has_featured && !$has_gallery) {
            $stats['car_not_delivered']++;
        } elseif ($has_featured) {
            $stats['car_not_loaded']++;
        }
        // Note: If there are gallery images but no featured image, it's not counted in either
        // of these two cards per your strict definitions.
    }

    // Get invoice data
    $invoices = carspace_get_user_invoices($user_id);
    $stats['total_invoice'] = count($invoices);

    // Batch-prime meta cache for all invoices
    $invoice_ids = wp_list_pluck($invoices, 'ID');
    if (!empty($invoice_ids)) {
        update_meta_cache('post', $invoice_ids);
    }

    // Calculate invoice balances
    $paid_balance = 0;
    $unpaid_balance = 0;

    foreach ($invoices as $invoice) {
        $invoice_id = $invoice->ID;

        // Fetch invoice row from custom table once
        $invoice_row = Carspace_Invoice::find($invoice_id);

        if (!$invoice_row) {
            continue;
        }

        // Sum item amounts from the invoice items
        $base_total = 0;
        if (!empty($invoice_row->items) && is_array($invoice_row->items)) {
            foreach ($invoice_row->items as $item) {
                $base_total += floatval($item->amount ?? 0);
            }
        }

        // Dealer Fee
        $dealer_fee = is_numeric($invoice_row->dealer_fee) ? floatval($invoice_row->dealer_fee) : 0;

        // Extra commission
        $extra_commission = is_numeric($invoice_row->commission) ? floatval($invoice_row->commission) : 0;

        // Final invoice total
        $invoice_total = $base_total + $dealer_fee + $extra_commission;

        if ($invoice_row->status === 'paid') {
            $paid_balance += $invoice_total;
        } else {
            $unpaid_balance += $invoice_total;
        }
    }

    // Calculate invoice balance delta (paid - unpaid)
    $invoice_balance_delta = $paid_balance - $unpaid_balance;

    // Format minus sign if negative
    $invoice_balance_display = '';
    if ($invoice_balance_delta < 0) {
        // Make sure minus sign is visible and NOT inside span
        $invoice_balance_display = '-' . wc_price(abs($invoice_balance_delta));
    } else {
        $invoice_balance_display = wc_price($invoice_balance_delta);
    }

    // Format dashboard cards data
    $result = array(
        __('Total Car', 'carspace-dashboard') => array(
            'value' => $stats['total_car'],
            'icon' => 'car',
            'link' => 'assigned-cars'
        ),
        __('Car Delivered', 'carspace-dashboard') => array(
            'value' => $stats['car_delivered'],
            'icon' => 'ship',
            'link' => 'car-delivered'
        ),
        __('Car Not Delivered', 'carspace-dashboard') => array(
            'value' => $stats['car_not_delivered'],
            'icon' => 'hourglass',
            'link' => 'car-not-delivered'
        ),
        __('Booking Container', 'carspace-dashboard') => array(
            'value' => $stats['booking_container'],
            'icon' => 'package-check',
            'link' => 'booking-container'
        ),
        __('Loaded Container', 'carspace-dashboard') => array(
            'value' => $stats['loaded_container'],
            'icon' => 'container',
            'link' => 'loaded-container'
        ),
        __('Car Not Loaded', 'carspace-dashboard') => array(
            'value' => $stats['car_not_loaded'],
            'icon' => 'ban',
            'link' => 'car-not-loaded'
        ),
        __('Total Invoice', 'carspace-dashboard') => array(
            'value' => $stats['total_invoice'],
            'icon' => 'file-text',
            'link' => 'car-invoices'
        ),
        __('Paid Invoice Balance', 'carspace-dashboard') => array(
            'value' => wc_price($paid_balance),
            'icon' => 'wallet',
            'link' => 'car-invoices'
        ),
        __('Unpaid Invoice Balance', 'carspace-dashboard') => array(
            'value' => wc_price($unpaid_balance),
            'icon' => 'banknote-x',
            'link' => 'car-invoices'
        ),
        __('Invoice Balance', 'carspace-dashboard') => array(
            'value' => $invoice_balance_display,
            'icon'  => 'scale',
            'link'  => 'car-invoices'
        ),
    );

    // Cache for 5 minutes
    set_transient($transient_key, $result, 5 * MINUTE_IN_SECONDS);

    return $result;
}

/**
 * Render the dashboard with master toggle functionality
 *
 * @return void
 */
function carspace_render_dashboard() {
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('You need to log in to view your dashboard.', 'carspace-dashboard') . '</p>';
        return;
    }

    $current_user_id = get_current_user_id();
    $cards = carspace_get_dashboard_cards_data($current_user_id);

    if (empty($cards)) {
        echo '<p>' . esc_html__('No data available for your dashboard.', 'carspace-dashboard') . '</p>';
        return;
    }

    // Add styles
    ?>
    <style>
    /* Dashboard Toggle Styles */
    .carspace-dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
        min-height: 95px;
    }

    span.toggle-text {
        padding: 5px;
    }

    .dashboard-user-info {
        display: flex;
        flex-direction: column;
    }

    .dashboard-toggle-container {
        display: flex;
        align-items: center;
    }

    .master-toggle-btn {
        display: inline-flex;
        align-items: center;
        padding: 5.6px 21px;
        background-color: #3468b3;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        margin-right: 15px;
    }

    .master-toggle-btn:hover {
        background-color: #005177;
    }

    .master-toggle-btn i {
        margin-right: 8px;
    }

    /* Icon view styles */
    .dashboard-icon-view {
        display: none;
        flex-grow: 1;
        overflow-x: auto; /* Enable horizontal scrolling if needed */
    }

    .dashboard-icon-view-inner {
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap; /* Prevent wrapping */
        position: relative;
    }

    .dashboard-icon-item {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        text-decoration: none;
        color: #0273aa;
        background: #f8f9fa;
        position: relative;
    }

    .dashboard-icon-item:hover {
        background-color: #ddd;
        text-decoration: none;
    }

    /* Custom tooltip */
    .icon-tooltip {
        visibility: hidden;
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        white-space: nowrap;
        z-index: 100;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .icon-tooltip::before {
        content: "";
        position: absolute;
        bottom: 100%;
        left: 50%;
        margin-left: -5px;
        /*border-width: 5px;
        border-style: solid;
        border-color: transparent transparent #333 transparent;*/
    }

    .dashboard-icon-item:hover .icon-tooltip {
        visibility: visible;
        opacity: 1;
    }
    </style>
    <?php

    // Dashboard header
    echo '<div class="carspace-dashboard-header">';

    // User info (visible when dashboard is expanded)
    $current_user   = wp_get_current_user();
    $current_login  = $current_user->user_login;
    $current_utc    = gmdate('Y-m-d H:i:s');

    echo '<div id="dashboard-user-info" class="dashboard-user-info">';
    echo '<span><strong>' . esc_html__('Current User\'s Login', 'carspace-dashboard') . ':</strong> ' . esc_html($current_login) . '</span>';
    echo '<span><strong>' . esc_html__('Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted)', 'carspace-dashboard') . ':</strong> ' . esc_html($current_utc) . '</span>';
    echo '</div>';

    // Icon view (visible when dashboard is collapsed)
    echo '<div id="dashboard-icon-view" class="dashboard-icon-view">';
    echo '<div class="dashboard-icon-view-inner">';

    // Create icon buttons for collapsed view with tooltips
    foreach ($cards as $title => $info) {
        $icon = $info['icon'];
        $link = !empty($info['link'])
            ? esc_url(wc_get_account_endpoint_url($info['link']))
            : '';

        if ($link) {
            echo '<a href="' . $link . '" class="dashboard-icon-item">';
            echo '<i data-lucide="' . esc_attr($icon) . '"></i>';
            echo '<div class="icon-tooltip">' . esc_html($title) . '</div>';
            echo '</a>';
        } else {
            echo '<div class="dashboard-icon-item">';
            echo '<i data-lucide="' . esc_attr($icon) . '"></i>';
            echo '<div class="icon-tooltip">' . esc_html($title) . '</div>';
            echo '</div>';
        }
    }

    echo '</div>'; // End of dashboard-icon-view-inner
    echo '</div>'; // End of dashboard-icon-view

    // Toggle button
    echo '<div class="dashboard-toggle-container">';
    echo '<button id="master-dashboard-toggle" class="cta-button master-toggle-btn" aria-expanded="true" aria-controls="carspace-dashboard-content">';
    echo '<i data-lucide="eye" class="toggle-dashboard-icon"></i>';
    echo '<span class="toggle-text">' . esc_html__('Hide Dashboard', 'carspace-dashboard') . '</span>';
    echo '</button>';
    echo '</div>';

    echo '</div>'; // End of carspace-dashboard-header

    // Dashboard content container (toggleable)
    echo '<div id="carspace-dashboard-content">';

    // Original carspace-dashboard rendering
    echo '<div class="carspace-dashboard">';

    foreach ($cards as $title => $info) {
        $value = $info['value'];
        $icon  = $info['icon'];
        $link  = !empty($info['link'])
            ? esc_url(wc_get_account_endpoint_url($info['link']))
            : '';

        $is_html = strpos($value, '<span') !== false;

        echo '<div class="dashboard-box">';

        if ($link) {
            echo '<a href="' . $link . '" class="dashboard-box-link">';
        }

        echo '<div class="icon"><i data-lucide="' . esc_attr($icon) . '"></i></div>';
        echo '<h4>' . esc_html($title) . '</h4>';
        echo '<div class="value">' . ($is_html ? $value : esc_html($value)) . '</div>';

        if ($link) {
            echo '</a>';
        }

        echo '</div>';
    }

    echo '</div>'; // End of carspace-dashboard
    echo '</div>'; // End of carspace-dashboard-content

    // Add JavaScript for toggle functionality
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Lucide icons
        lucide.createIcons();

        // Setup master dashboard toggle
        const masterToggle = document.getElementById('master-dashboard-toggle');
        const dashboardContent = document.getElementById('carspace-dashboard-content');
        const userInfo = document.getElementById('dashboard-user-info');
        const iconView = document.getElementById('dashboard-icon-view');
        const toggleIcon = masterToggle.querySelector('.toggle-dashboard-icon');
        const toggleText = masterToggle.querySelector('.toggle-text');

        masterToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                // Hide dashboard and show icon view
                dashboardContent.style.display = 'none';
                userInfo.style.display = 'none';
                iconView.style.display = 'block';
                this.setAttribute('aria-expanded', 'false');
                toggleIcon.setAttribute('data-lucide', 'eye-off');
                toggleText.textContent = '<?php echo esc_js(__('Show Dashboard', 'carspace-dashboard')); ?>';
            } else {
                // Show dashboard and hide icon view
                dashboardContent.style.display = 'block';
                userInfo.style.display = 'flex';
                iconView.style.display = 'none';
                this.setAttribute('aria-expanded', 'true');
                toggleIcon.setAttribute('data-lucide', 'eye');
                toggleText.textContent = '<?php echo esc_js(__('Hide Dashboard', 'carspace-dashboard')); ?>';
            }

            // Refresh Lucide icons after changing the icon
            lucide.createIcons({
                elements: [toggleIcon]
            });
        });
    });
    </script>
    <?php
}

/**
 * Clear dashboard cards transient when relevant data changes.
 */
function carspace_clear_cards_cache_on_product_save($post_id) {
    if (get_post_type($post_id) !== 'product') {
        return;
    }
    $assigned_user = get_post_meta($post_id, 'assigned_user', true);
    if ($assigned_user) {
        delete_transient('carspace_cards_' . intval($assigned_user));
    }
}
add_action('save_post_product', 'carspace_clear_cards_cache_on_product_save', 20);

function carspace_clear_cards_cache_on_invoice_save($post_id, $post) {
    if ($post->post_type !== 'invoice') {
        return;
    }
    $owner = get_post_meta($post_id, 'invoice_owner', true);
    if ($owner) {
        delete_transient('carspace_cards_' . intval($owner));
    }
}
add_action('save_post_invoice', 'carspace_clear_cards_cache_on_invoice_save', 10, 2);
