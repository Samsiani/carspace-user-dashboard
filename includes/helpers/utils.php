<?php
/**
 * Utility functions for Carspace Dashboard
 *
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

/**
 * Get cars assigned to a specific user
 *
 * @param int $user_id User ID
 * @param array $args Additional query arguments
 * @return array Array of product posts
 */
function carspace_get_user_assigned_cars($user_id, $args = array()) {
    $default_args = array(
        'post_type' => 'product',
        'posts_per_page' => 500,
        'no_found_rows' => true,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'assigned_user',
                'value' => $user_id,
                'compare' => '='
            )
        ),
    );

    $args = wp_parse_args($args, $default_args);

    return get_posts($args);
}

/**
 * Get filtered car list based on condition
 *
 * @param int $user_id User ID
 * @param callable $filter_callback Function to filter cars
 * @return array Filtered car array
 */
function carspace_get_filtered_cars($user_id, $filter_callback) {
    $cars = carspace_get_user_assigned_cars($user_id);

    if (empty($cars)) {
        return array();
    }

    return array_filter($cars, $filter_callback);
}

/**
 * Get product attribute value by slug
 *
 * @param WC_Product $product WooCommerce product
 * @param string $slug Attribute slug
 * @param string $default Default value if attribute is empty
 * @return string
 */
function carspace_get_attribute_value($product, $slug, $default = "\xe2\x80\x94") {
    if (!$product) {
        return $default;
    }

    $value = $product->get_attribute($slug);
    return !empty($value) ? esc_html($value) : $default;
}

/**
 * Format date with consistent style
 *
 * @param string $date Date string
 * @param string $format PHP date format
 * @return string Formatted date or dash if empty
 */
function carspace_format_date($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return "\xe2\x80\x94";
    }

    $timestamp = strtotime($date);
    if (!$timestamp) {
        return "\xe2\x80\x94";
    }

    return date_i18n($format, $timestamp);
}

/**
 * Check if a car is delivered based on port images
 *
 * @param int $product_id Product ID
 * @return bool
 */
function carspace_is_car_delivered($product_id) {
    return Carspace_Port_Images::has_images($product_id);
}

/**
 * Check if a car is loaded into a container
 *
 * @param WC_Product $product WooCommerce product
 * @return bool
 */
function carspace_is_car_loaded($product) {
    if (!$product) {
        return false;
    }

    $container_number = $product->get_attribute('container-number');
    return !empty($container_number);
}

/**
 * Check if a car has a booking number
 *
 * @param WC_Product $product WooCommerce product
 * @return bool
 */
function carspace_has_booking_number($product) {
    if (!$product) {
        return false;
    }

    $booking_number = $product->get_attribute('booking-number');
    return !empty($booking_number);
}

/**
 * Get user invoices
 *
 * @param int $user_id User ID
 * @return array Array of invoice posts
 */
function carspace_get_user_invoices($user_id) {
    return get_posts(array(
        'post_type' => 'invoice',
        'post_status' => 'publish',
        'numberposts' => -1,
        'no_found_rows' => true,
        'meta_query' => array(
            array(
                'key' => 'invoice_owner',
                'value' => $user_id,
                'compare' => '=',
                'type' => 'NUMERIC'
            )
        )
    ));
}

/**
 * Get invoice total amount
 *
 * @param int $invoice_id Invoice ID
 * @return float Total amount
 */
function carspace_get_invoice_total($invoice_id) {
    return Carspace_Invoice::get_invoice_total($invoice_id);
}

/**
 * Check if an invoice is paid
 *
 * @param int $invoice_id Invoice ID
 * @return bool
 */
function carspace_is_invoice_paid($invoice_id) {
    $invoice = Carspace_Invoice::find($invoice_id);
    return $invoice && $invoice->status === 'paid';
}
