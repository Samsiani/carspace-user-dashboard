<?php
/**
 * Car Delivered Notification
 *
 * Notification sent when a car is marked as delivered (port images added).
 * Hooks into the custom 'carspace_port_images_saved' action fired by Carspace_Port_Images::save().
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

/**
 * Hook into port images save action.
 */
add_action('carspace_port_images_saved', 'carspace_check_car_delivery_status_notify', 10, 3);

/**
 * Check if port images were added for the first time and notify assigned user.
 *
 * @param int   $product_id     The WC product ID.
 * @param array $attachment_ids The newly saved attachment IDs.
 * @param bool  $had_before     Whether the product had port images before this save.
 */
function carspace_check_car_delivery_status_notify($product_id, $attachment_ids, $had_before) {
    // Only notify when images are added for the first time
    if ($had_before || empty($attachment_ids)) {
        return;
    }

    // Only proceed if this is a product
    if (get_post_type($product_id) !== 'product') {
        return;
    }

    $assigned_user_id = get_post_meta($product_id, 'assigned_user', true);

    if (!$assigned_user_id) {
        return;
    }

    $product = wc_get_product($product_id);

    if (!$product) {
        return;
    }

    $car_name = $product->get_name();
    $car_sku  = $product->get_sku();

    $title = sprintf(
        __('Car Delivered: %s', 'carspace-dashboard'),
        $car_name
    );

    $message = sprintf(
        __('Good news! Your car %1$s (VIN: %2$s) has been delivered. Port images are now available in your dashboard.', 'carspace-dashboard'),
        $car_name,
        $car_sku
    );

    carspace_create_notification(
        $assigned_user_id,
        $title,
        $message,
        'car_delivered',
        carspace_get_dashboard_url()
    );
}
