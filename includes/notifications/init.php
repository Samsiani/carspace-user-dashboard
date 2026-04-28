<?php
/**
 * Notifications System Initialization
 *
 * Loads notification components and initializes the notification system.
 * No longer registers a CPT — all notifications live in carspace_notifications table.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

// Load notification type files
require_once __DIR__ . '/car-assigned.php';
require_once __DIR__ . '/car-delivered.php';
require_once __DIR__ . '/invoice-created.php';

/**
 * Initialize notifications system
 */
function carspace_initialize_notifications() {
    // Register notification cleanup cron job
    if (!wp_next_scheduled('carspace_cleanup_old_notifications')) {
        wp_schedule_event(time(), 'daily', 'carspace_cleanup_old_notifications');
    }
}
add_action('init', 'carspace_initialize_notifications');

/**
 * Clean up old notifications
 * Deletes notifications older than configured months (default 5)
 */
function carspace_cleanup_old_notifications() {
    $months = (int) get_option('carspace_notification_cleanup_months', 5);
    if ($months <= 0) return; // 0 = disabled
    Carspace_Notification::cleanup_old($months * 30);
}
add_action('carspace_cleanup_old_notifications', 'carspace_cleanup_old_notifications');

/**
 * Add notification counts to admin bar
 */
function carspace_add_notification_count_to_admin_bar($wp_admin_bar) {
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $count   = Carspace_Notification::count_unread($user_id);

    if ($count > 0) {
        $page_id = get_option('carspace_dashboard_page_id', 0);
        $href = $page_id ? get_permalink($page_id) : home_url('/');

        $wp_admin_bar->add_node(array(
            'id'    => 'carspace-notifications',
            'title' => sprintf(
                '<span class="ab-icon dashicons dashicons-bell"></span> <span class="count">%d</span>',
                $count
            ),
            'href'  => $href,
            'meta'  => array(
                'title' => sprintf(
                    _n(
                        'You have %d unread notification',
                        'You have %d unread notifications',
                        $count,
                        'carspace-dashboard'
                    ),
                    $count
                ),
            ),
        ));
    }
}
add_action('admin_bar_menu', 'carspace_add_notification_count_to_admin_bar', 90);
