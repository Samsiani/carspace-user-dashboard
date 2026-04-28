<?php
/**
 * Ticket Model
 *
 * Wraps carspace_tickets + carspace_ticket_messages tables.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Ticket {

    /**
     * List tickets with pagination.
     *
     * @param array $args {
     *   @type int    $author_id  Filter by author (0 = all).
     *   @type string $status     Filter by status (empty = all).
     *   @type string $search     Search in subject.
     *   @type int    $per_page
     *   @type int    $page
     * }
     * @return array ['items' => [], 'total' => int, 'page' => int, 'total_pages' => int]
     */
    public static function list($args = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'carspace_tickets';

        $defaults = array(
            'author_id' => 0,
            'status'    => '',
            'search'    => '',
            'per_page'  => 20,
            'page'      => 1,
        );
        $args = wp_parse_args($args, $defaults);

        $where = '1=1';
        $params = array();

        if ($args['author_id']) {
            $where .= ' AND t.author_id = %d';
            $params[] = intval($args['author_id']);
        }

        if ($args['status']) {
            $where .= ' AND t.status = %s';
            $params[] = sanitize_text_field($args['status']);
        }

        if ($args['search']) {
            $where .= ' AND t.subject LIKE %s';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $count_sql = "SELECT COUNT(*) FROM {$table} t WHERE {$where}";
        $total = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_sql, ...$params)) : $wpdb->get_var($count_sql));

        $per_page = max(1, intval($args['per_page']));
        $page = max(1, intval($args['page']));
        $offset = ($page - 1) * $per_page;

        $sql = "SELECT t.* FROM {$table} t WHERE {$where} ORDER BY t.updated_at DESC LIMIT %d OFFSET %d";
        $all_params = array_merge($params, array($per_page, $offset));
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$all_params));

        return array(
            'items'       => $rows ?: array(),
            'total'       => $total,
            'page'        => $page,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
        );
    }

    /**
     * Get a single ticket by ID.
     *
     * @param int $id
     * @return object|null
     */
    public static function find($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_tickets WHERE id = %d",
            intval($id)
        ));
    }

    /**
     * Create a ticket.
     *
     * @param array $data
     * @return int|false Ticket ID or false.
     */
    public static function create($data) {
        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'carspace_tickets',
            array(
                'subject'     => sanitize_text_field($data['subject']),
                'status'      => 'opened',
                'priority'    => sanitize_text_field($data['priority'] ?? 'medium'),
                'category'    => sanitize_text_field($data['category'] ?? 'general'),
                'author_id'   => intval($data['author_id']),
                'assigned_to' => !empty($data['assigned_to']) ? intval($data['assigned_to']) : null,
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Update ticket fields.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        global $wpdb;

        $allowed = array('status', 'priority', 'assigned_to', 'subject', 'category');
        $update = array();
        $formats = array();

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'assigned_to') {
                    $update[$field] = $data[$field] ? intval($data[$field]) : null;
                    $formats[] = '%d';
                } else {
                    $update[$field] = sanitize_text_field($data[$field]);
                    $formats[] = '%s';
                }
            }
        }

        if (empty($update)) return false;

        return (bool) $wpdb->update(
            $wpdb->prefix . 'carspace_tickets',
            $update,
            array('id' => intval($id)),
            $formats,
            array('%d')
        );
    }

    /**
     * Delete a ticket and its messages.
     *
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;

        $id = intval($id);
        $wpdb->delete($wpdb->prefix . 'carspace_ticket_messages', array('ticket_id' => $id), array('%d'));
        return (bool) $wpdb->delete($wpdb->prefix . 'carspace_tickets', array('id' => $id), array('%d'));
    }

    /**
     * Get messages for a ticket.
     *
     * @param int $ticket_id
     * @return array
     */
    public static function get_messages($ticket_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}carspace_ticket_messages WHERE ticket_id = %d ORDER BY created_at ASC",
            intval($ticket_id)
        )) ?: array();
    }

    /**
     * Add a message to a ticket.
     *
     * @param int    $ticket_id
     * @param int    $author_id
     * @param string $content
     * @return int|false Message ID or false.
     */
    public static function add_message($ticket_id, $author_id, $content) {
        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'carspace_ticket_messages',
            array(
                'ticket_id' => intval($ticket_id),
                'author_id' => intval($author_id),
                'content'   => wp_kses_post($content),
            ),
            array('%d', '%d', '%s')
        );

        if ($inserted) {
            // Touch ticket updated_at
            $wpdb->update(
                $wpdb->prefix . 'carspace_tickets',
                array('updated_at' => current_time('mysql')),
                array('id' => intval($ticket_id)),
                array('%s'),
                array('%d')
            );
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Count tickets by status for a user (or all if user_id=0).
     *
     * @param int $user_id 0 for all users.
     * @return array ['opened' => int, 'answered' => int, 'waiting' => int, 'closed' => int]
     */
    public static function count_by_status($user_id = 0) {
        global $wpdb;

        $table = $wpdb->prefix . 'carspace_tickets';
        $where = $user_id ? $wpdb->prepare('WHERE author_id = %d', intval($user_id)) : '';

        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) as cnt FROM {$table} {$where} GROUP BY status"
        );

        $counts = array('opened' => 0, 'answered' => 0, 'waiting' => 0, 'closed' => 0);
        foreach ($rows as $row) {
            $counts[$row->status] = (int) $row->cnt;
        }
        return $counts;
    }
}
