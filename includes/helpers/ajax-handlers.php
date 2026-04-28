<?php
/**
 * AJAX Handlers
 *
 * All AJAX handlers for the dashboard
 *
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

/**
 * Disable caching for all AJAX requests
 */
add_action('init', function() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // No-cache headers
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
});

/**
 * AJAX: Load shipping information modal content
 */
add_action('wp_ajax_load_shipping_info_popup', 'carspace_ajax_load_shipping_info');

/**
 * Handler for shipping info popup AJAX request
 */
function carspace_ajax_load_shipping_info() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(array('message' => __('Invalid product ID', 'carspace-dashboard')));
    }

    $product = wc_get_product($product_id);

    if (!$product) {
        wp_send_json_error(array('message' => __('Product not found', 'carspace-dashboard')));
    }

    // Get shipping information fields
    $booking = carspace_get_attribute_value($product, 'booking-number');
    $container = carspace_get_attribute_value($product, 'container-number');

    ob_start();
    ?>
    <table class="table table-striped">
        <tr>
            <th><?php esc_html_e('Pickup Date', 'carspace-dashboard'); ?></th>
            <td><?php echo carspace_get_attribute_value($product, 'pickup-date'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Delivery Date', 'carspace-dashboard'); ?></th>
            <td><?php echo carspace_get_attribute_value($product, 'delivery-date'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Loading Date', 'carspace-dashboard'); ?></th>
            <td><?php echo carspace_get_attribute_value($product, 'loading-date'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Departure Date', 'carspace-dashboard'); ?></th>
            <td><?php echo carspace_get_attribute_value($product, 'departure-date'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Arrival Date', 'carspace-dashboard'); ?></th>
            <td><?php echo carspace_get_attribute_value($product, 'arrival-date'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Booking Number', 'carspace-dashboard'); ?></th>
            <td><?php echo esc_html($booking); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Container Number', 'carspace-dashboard'); ?></th>
            <td><?php echo esc_html($container); ?></td>
        </tr>
        <!-- Add this new row for shipline name -->
        <tr>
            <th><?php esc_html_e('Shipline Name', 'carspace-dashboard'); ?></th>
            <td><?php echo carspace_get_attribute_value($product, 'shipline-name'); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Tracking URL', 'carspace-dashboard'); ?></th>
            <td class="table_traking_link">
                <?php
                $tracking_url = trim(carspace_get_attribute_value($product, 'tracking-link'));
                if (!empty($tracking_url) && filter_var($tracking_url, FILTER_VALIDATE_URL)) {
                    printf(
                        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                        esc_url($tracking_url),
                        esc_html__('თრექინგი', 'carspace-dashboard')
                    );
                } else {
                    echo esc_html__('არ არის ხელმისაწვდომი', 'carspace-dashboard');
                }
                ?>
            </td>
        </tr>


    </table>

    <?php

    wp_send_json_success(array('html' => ob_get_clean()));
}

/**
 * AJAX: Load car images for gallery modal
 */
add_action('wp_ajax_load_car_images_popup', 'carspace_ajax_load_car_images');

/**
 * Handler for car images gallery AJAX request
 */
function carspace_ajax_load_car_images() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(array('message' => __('Invalid product ID', 'carspace-dashboard')));
    }

    $product = wc_get_product($product_id);

    if (!$product) {
        wp_send_json_error(array('message' => __('Product not found', 'carspace-dashboard')));
    }

    $gallery = $product->get_gallery_image_ids();
    $port_image_ids = Carspace_Port_Images::get($product_id);
    $unique_gallery_id = 'gallery-' . $product_id . '-' . time();

    ob_start();
    ?>
    <div class="row g-2">
        <!-- Container Gallery -->
        <div class="col-12"><h6><?php esc_html_e('Container Gallery', 'carspace-dashboard'); ?></h6></div>
        <?php if (!empty($gallery)) : ?>
            <?php foreach ($gallery as $img_id):
                $thumb = wp_get_attachment_image_url($img_id, 'thumbnail');
                $full = wp_get_attachment_image_url($img_id, 'full');
                $title = get_the_title($img_id);

                if (!$thumb || !$full) continue;
            ?>
                <div class="col-2">
                    <a href="<?php echo esc_url($full); ?>" class="glightbox" data-gallery="<?php echo esc_attr($unique_gallery_id); ?>" data-title="<?php echo esc_attr($title); ?>">
                        <img src="<?php echo esc_url($thumb); ?>" class="img-thumbnail w-100" alt="<?php echo esc_attr($title); ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12 text-muted"><?php esc_html_e('No Images', 'carspace-dashboard'); ?></div>
        <?php endif; ?>

        <!-- Port Images -->
        <div class="col-12 pt-3"><h6><?php esc_html_e('Port Images', 'carspace-dashboard'); ?></h6></div>
        <?php if (!empty($port_image_ids) && is_array($port_image_ids)) : ?>
            <?php foreach ($port_image_ids as $img_id):
                $thumb = wp_get_attachment_image_url($img_id, 'thumbnail');
                $full = wp_get_attachment_image_url($img_id, 'full');
                $title = get_the_title($img_id);

                if (!$thumb || !$full) continue;
            ?>
                <div class="col-2">
                    <a href="<?php echo esc_url($full); ?>" class="glightbox" data-gallery="<?php echo esc_attr($unique_gallery_id); ?>" data-title="<?php echo esc_attr($title ?: $product->get_name() . ' - Port Image'); ?>">
                        <img src="<?php echo esc_url($thumb); ?>" class="img-thumbnail w-100" alt="<?php echo esc_attr($title ?: $product->get_name() . ' - Port Image'); ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12 text-muted"><?php esc_html_e('No Images', 'carspace-dashboard'); ?></div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof GLightbox === 'function') {
                const lightbox = GLightbox({
                    selector: '.glightbox',
                    touchNavigation: true,
                    loop: true,
                    autoplayVideos: false,
                    openEffect: 'zoom',
                    closeEffect: 'fade',
                    cssEfects: {
                        fade: { in: 'fadeIn', out: 'fadeOut' },
                        zoom: { in: 'zoomIn', out: 'zoomOut' }
                    },
                    draggable: true,
                    touchFollowAxis: true
                });
            }
        });
    </script>
    <div class="text-muted small mt-3 text-end">
        <?php
        printf(
            /* translators: %1$s: current date, %2$s: username */
            esc_html__('Images loaded: %1$s by %2$s', 'carspace-dashboard'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')),
            esc_html(wp_get_current_user()->user_login)
        );
        ?>
    </div>
    <?php

    wp_send_json_success(array('html' => ob_get_clean()));
}

/**
 * AJAX: Refresh security nonce
 */
add_action('wp_ajax_refresh_carspace_nonce', 'carspace_ajax_refresh_nonce');

/**
 * Handler for refreshing security nonce
 */
function carspace_ajax_refresh_nonce() {
    $new_nonce = wp_create_nonce('carspace_security_nonce');

    wp_send_json_success(array(
        'nonce' => $new_nonce,
        'expires' => time() + (DAY_IN_SECONDS / 2), // Nonce expires in 12 hours
        'current_time' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')),
        'user' => wp_get_current_user()->user_login
    ));
}

/**
 * AJAX: Check car delivery status
 */
add_action('wp_ajax_check_car_delivery_status', 'carspace_ajax_check_car_delivery_status');

/**
 * Handler for checking car delivery status
 */
function carspace_ajax_check_car_delivery_status() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(array('message' => __('Invalid product ID', 'carspace-dashboard')));
    }

    $is_delivered = carspace_is_car_delivered($product_id);
    $product = wc_get_product($product_id);

    if (!$product) {
        wp_send_json_error(array('message' => __('Product not found', 'carspace-dashboard')));
    }

    // Get important shipping details
    $car_name = $product->get_name();
    $pickup_date = carspace_get_attribute_value($product, 'pickup-date');
    $delivery_date = carspace_get_attribute_value($product, 'delivery-date');
    $arrival_date = carspace_get_attribute_value($product, 'arrival-date');

    wp_send_json_success(array(
        'is_delivered' => $is_delivered,
        'car_name' => $car_name,
        'details' => array(
            'pickup_date' => $pickup_date,
            'delivery_date' => $delivery_date,
            'arrival_date' => $arrival_date,
        ),
        'last_checked' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')),
        'checked_by' => wp_get_current_user()->user_login
    ));
}

/**
 * AJAX: Check for new notifications
 */
add_action('wp_ajax_get_notification_count', 'carspace_ajax_get_notification_count');

/**
 * Handler for getting new notification count
 */
function carspace_ajax_get_notification_count() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    $user_id = get_current_user_id();
    $last_check = isset($_POST['last_check']) ? intval($_POST['last_check']) / 1000 : 0;

    if (!$user_id) {
        wp_send_json_error(array('message' => __('User not logged in', 'carspace-dashboard')));
        return;
    }

    // Get unread notification count
    $count = carspace_get_unread_notification_count($user_id);

    // Get notifications since last check if provided
    $new_notifications = array();
    if ($last_check > 0) {
        $since_datetime = gmdate('Y-m-d H:i:s', (int) $last_check);
        $recent = Carspace_Notification::get_since($user_id, $since_datetime);

        // Limit to 5 most recent
        $recent = array_slice($recent, 0, 5);

        foreach ($recent as $note) {
            $new_notifications[] = array(
                'id'    => $note->id,
                'title' => $note->title,
                'date'  => $note->created_at,
                'type'  => $note->type ?: 'info',
            );
        }
    }

    wp_send_json_success(array(
        'count' => $count,
        'has_notifications' => ($count > 0),
        'new_notifications' => $new_notifications,
        'last_checked' => date_i18n('Y-m-d H:i:s', current_time('timestamp')),
        'user' => wp_get_current_user()->user_login
    ));
}

/**
 * AJAX: Delete notification
 */
add_action('wp_ajax_delete_notification', 'carspace_ajax_delete_notification');

/**
 * Handler for deleting a notification
 */
function carspace_ajax_delete_notification() {
    check_ajax_referer('carspace_security_nonce', 'nonce');

    $notification_id = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;
    $user_id = get_current_user_id();

    if (!$notification_id) {
        wp_send_json_error(array('message' => __('Invalid notification ID', 'carspace-dashboard')));
    }

    if (!$user_id) {
        wp_send_json_error(array('message' => __('User not logged in', 'carspace-dashboard')));
    }

    // Model handles ownership check + deletion
    $result = Carspace_Notification::delete($notification_id, $user_id);

    if ($result) {
        wp_send_json_success(array(
            'message' => __('Notification deleted successfully', 'carspace-dashboard'),
            'deleted_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')),
            'user' => wp_get_current_user()->user_login
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to delete notification', 'carspace-dashboard')));
    }
}

// Note: mark_notification_as_read and mark_all_notifications_as_read handlers
// are registered in notifications.php — do not duplicate here.
