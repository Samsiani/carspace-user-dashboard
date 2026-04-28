<?php
/**
 * Notifications Helper Functions
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

/**
 * Get the SPA dashboard URL (for notification links)
 */
function carspace_get_dashboard_url() {
    $page_id = get_option('carspace_dashboard_page_id', 0);
    return $page_id ? get_permalink($page_id) : home_url('/');
}

/**
 * Create a new notification for one or more users
 */
function carspace_create_notification($user_ids, $title, $message, $type = 'info', $link = '', $visible_until = null) {
    if (empty($user_ids) || empty($title)) {
        return new WP_Error('missing_data', 'User ID and title are required');
    }

    if (!is_array($user_ids)) {
        $user_ids = array($user_ids);
    }

    return Carspace_Notification::create(
        $user_ids,
        sanitize_text_field($title),
        wp_kses_post($message),
        sanitize_text_field($type),
        !empty($link) ? esc_url_raw($link) : '',
        $visible_until ? sanitize_text_field($visible_until) : null
    );
}

/**
 * Get notifications for a specific user
 */
function carspace_get_user_notifications($user_id, $only_unread = false) {
    if (!$user_id) {
        return array();
    }
    return Carspace_Notification::get_for_user($user_id, $only_unread);
}

/**
 * Get count of unread notifications for a user
 */
function carspace_get_unread_notification_count($user_id) {
    return Carspace_Notification::count_unread($user_id);
}

/**
 * Mark a notification as read
 */
function carspace_mark_notification_as_read($notification_id, $user_id) {
    if (!$notification_id || !$user_id) {
        return false;
    }
    return Carspace_Notification::mark_read($notification_id, $user_id);
}

/**
 * Mark all notifications as read for a user
 */
function carspace_mark_all_notifications_as_read($user_id) {
    if (!$user_id) {
        return 0;
    }
    return Carspace_Notification::mark_all_read($user_id);
}
