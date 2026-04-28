<?php
/**
 * Notification Model
 *
 * Wraps carspace_notifications table.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Notification {

    /**
     * Create notification(s) — one row per recipient user.
     *
     * @param int|array $user_ids
     * @param string    $title
     * @param string    $message
     * @param string    $type    info|alert|warning
     * @param string    $link
     * @param string    $visible_until DateTime string or null.
     * @return int|WP_Error Number of rows inserted.
     */
    public static function create($user_ids, $title, $message, $type = 'info', $link = '', $visible_until = null) {
        global $wpdb;

        if (empty($user_ids) || empty($title)) {
            return new WP_Error('missing_data', 'User ID and title are required');
        }

        if (!is_array($user_ids)) {
            $user_ids = array($user_ids);
        }

        $count = 0;

        foreach ($user_ids as $uid) {
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'carspace_notifications',
                array(
                    'user_id'       => intval($uid),
                    'title'         => sanitize_text_field($title),
                    'message'       => wp_kses_post($message),
                    'type'          => sanitize_text_field($type),
                    'status'        => 'unread',
                    'link'          => $link ? esc_url_raw($link) : '',
                    'visible_until' => $visible_until ? sanitize_text_field($visible_until) : null,
                ),
                array('%d','%s','%s','%s','%s','%s','%s')
            );

            if ($inserted) {
                $count++;
            }
        }

        do_action('carspace_notification_created', $user_ids, $type, $count);

        return $count;
    }

    /**
     * Get notifications for a user.
     *
     * @param int  $user_id
     * @param bool $only_unread
     * @param int  $per_page
     * @param int  $page
     * @return array ['items' => [], 'total' => int]
     */
    public static function get_for_user($user_id, $only_unread = false, $per_page = 10, $page = 1) {
        global $wpdb;

        $table = $wpdb->prefix . 'carspace_notifications';

        $where = $wpdb->prepare("user_id = %d", intval($user_id));

        if ($only_unread) {
            $where .= " AND status = 'unread'";
        }

        // Exclude expired
        $where .= $wpdb->prepare(
            " AND (visible_until IS NULL OR visible_until = '' OR visible_until > %s)",
            current_time('mysql')
        );

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where}");

        $offset = max(0, ($page - 1) * $per_page);
        $items = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}"
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Count unread notifications for a user.
     *
     * @param int $user_id
     * @return int
     */
    public static function count_unread($user_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}carspace_notifications WHERE user_id = %d AND status = 'unread'",
            intval($user_id)
        ));
    }

    /**
     * Mark a single notification as read.
     *
     * @param int $id   Notification row ID.
     * @param int $user_id For ownership validation.
     * @return bool
     */
    public static function mark_read($id, $user_id) {
        global $wpdb;

        $affected = $wpdb->update(
            $wpdb->prefix . 'carspace_notifications',
            array('status' => 'read'),
            array('id' => intval($id), 'user_id' => intval($user_id)),
            array('%s'),
            array('%d', '%d')
        );

        if ($affected) {
            do_action('carspace_notification_marked_read', $id, $user_id);
        }

        return (bool) $affected;
    }

    /**
     * Mark all unread notifications as read for a user.
     *
     * @param int $user_id
     * @return int Number of rows updated.
     */
    public static function mark_all_read($user_id) {
        global $wpdb;

        $count = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}carspace_notifications SET status = 'read' WHERE user_id = %d AND status = 'unread'",
            intval($user_id)
        ));

        if ($count > 0) {
            do_action('carspace_all_notifications_marked_read', $user_id, $count);
        }

        return (int) $count;
    }

    /**
     * Delete a notification.
     *
     * @param int $id
     * @param int $user_id For ownership validation.
     * @return bool
     */
    public static function delete($id, $user_id) {
        global $wpdb;

        return (bool) $wpdb->delete(
            $wpdb->prefix . 'carspace_notifications',
            array('id' => intval($id), 'user_id' => intval($user_id)),
            array('%d', '%d')
        );
    }

    /**
     * Delete notifications older than $days.
     *
     * @param int $days
     * @return int Number deleted.
     */
    public static function cleanup_old($days = 60) {
        global $wpdb;

        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}carspace_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }

    /**
     * Get notifications created since a timestamp.
     *
     * @param int    $user_id
     * @param string $since MySQL datetime.
     * @param int    $limit
     * @return array
     */
    public static function get_since($user_id, $since, $limit = 5) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_notifications
             WHERE user_id = %d AND created_at > %s
             ORDER BY created_at DESC LIMIT %d",
            intval($user_id), $since, $limit
        ));
    }

    /**
     * Get all notifications (admin view) — grouped by title+message+type+created_at.
     *
     * @param int    $per_page
     * @param int    $page
     * @param string $search
     * @return array ['items' => [], 'total' => int]
     */
    public static function get_all_admin($per_page = 20, $page = 1, $search = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'carspace_notifications';

        $where = '1=1';
        if ($search) {
            $like  = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (title LIKE %s OR message LIKE %s)", $like, $like);
        }

        $total = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT title, type, created_at) FROM {$table} WHERE {$where}"
        );

        $offset = max(0, ($page - 1) * $per_page);
        $items  = $wpdb->get_results(
            "SELECT MIN(id) as id, title, message, type, link, created_at,
                    COUNT(*) as recipient_count,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
                    GROUP_CONCAT(id ORDER BY id) as all_ids
             FROM {$table}
             WHERE {$where}
             GROUP BY title, type, created_at
             ORDER BY created_at DESC
             LIMIT {$per_page} OFFSET {$offset}"
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Admin delete — no user_id check.
     *
     * @param int $id
     * @return bool
     */
    public static function admin_delete($id) {
        global $wpdb;

        return (bool) $wpdb->delete(
            $wpdb->prefix . 'carspace_notifications',
            array('id' => intval($id)),
            array('%d')
        );
    }

    /**
     * Bulk delete by IDs (admin).
     *
     * @param array $ids
     * @return int Number deleted.
     */
    public static function bulk_delete($ids) {
        global $wpdb;

        if (empty($ids)) return 0;

        $table       = $wpdb->prefix . 'carspace_notifications';
        $ids         = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        return (int) $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids)
        );
    }

    /**
     * Delete a grouped notification (all rows with same title+type+created_at).
     *
     * @param string $title
     * @param string $type
     * @param string $created_at
     * @return int Number deleted.
     */
    public static function delete_group($title, $type, $created_at) {
        global $wpdb;

        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}carspace_notifications WHERE title = %s AND type = %s AND created_at = %s",
            $title, $type, $created_at
        ));
    }
}
