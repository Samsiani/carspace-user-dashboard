<?php
/**
 * Car Assignment Notification
 *
 * Handles notifications when cars are assigned to users.
 * Hooks into post meta updates instead of ACF save hooks.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

/**
 * Hook into post meta changes to detect car assignments.
 */
add_action('updated_post_meta', 'carspace_check_car_assignment', 10, 4);
add_action('added_post_meta', 'carspace_check_car_assignment', 10, 4);

/**
 * Check if a car has been assigned to a user through meta update
 *
 * @param int    $meta_id    Meta row ID.
 * @param int    $post_id    The post ID being updated.
 * @param string $meta_key   The meta key.
 * @param mixed  $meta_value The new meta value.
 */
function carspace_check_car_assignment($meta_id, $post_id, $meta_key, $meta_value) {
    // Only react to the assigned_user meta key
    if ($meta_key !== 'assigned_user') {
        return;
    }

    // Only proceed if this is a product
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    // The new user ID comes directly from the hook parameter
    $new_user_id = intval($meta_value);

    // If no user is assigned, exit
    if (empty($new_user_id)) {
        return;
    }

    // Get the previous notification-tracking value to compare
    $prev_user_id = get_post_meta($post_id, '_assigned_user_notification', true);

    // Check if this is a new assignment (not just an update of the same user)
    if ($prev_user_id == $new_user_id) {
        return;
    }

    // User has been newly assigned — create notification
    $product = wc_get_product($post_id);

    if (!$product) {
        return;
    }

    $user_data = get_userdata($new_user_id);
    if (!$user_data) {
        return;
    }

    // Get car details
    $car_name = $product->get_name();
    $car_sku  = $product->get_sku();
    $make     = $product->get_attribute('make') ?: '';
    $model    = $product->get_attribute('model') ?: '';
    $year     = $product->get_attribute('year') ?: '';

    // Format car details
    $car_details = trim("$year $make $model");
    if (empty($car_details)) {
        $car_details = $car_name;
    }

    // Create notification
    $notification_title = sprintf(
        __('New Car Assigned: %s', 'carspace-dashboard'),
        $car_details
    );

    $notification_message = sprintf(
        __('A new vehicle has been assigned to you: %1$s (VIN: %2$s). View details in your dashboard.', 'carspace-dashboard'),
        $car_details,
        $car_sku
    );

    Carspace_Notification::create(
        $new_user_id,
        $notification_title,
        $notification_message,
        'assignment',
        carspace_get_dashboard_url()
    );

    // Track assigned user to prevent duplicate notifications
    update_post_meta($post_id, '_assigned_user_notification', $new_user_id);
}
