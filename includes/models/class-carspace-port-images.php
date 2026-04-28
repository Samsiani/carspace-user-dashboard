<?php
/**
 * Port Images Model
 *
 * Wraps carspace_port_images table.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Port_Images {

    /**
     * Get attachment IDs for a product.
     *
     * @param int $product_id
     * @return array Attachment IDs sorted by sort_order.
     */
    public static function get($product_id) {
        global $wpdb;

        return $wpdb->get_col($wpdb->prepare(
            "SELECT attachment_id FROM {$wpdb->prefix}carspace_port_images WHERE product_id = %d ORDER BY sort_order ASC, id ASC",
            intval($product_id)
        ));
    }

    /**
     * Check if a product has port images (= delivered).
     *
     * @param int $product_id
     * @return bool
     */
    public static function has_images($product_id) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM {$wpdb->prefix}carspace_port_images WHERE product_id = %d LIMIT 1",
            intval($product_id)
        ));
    }

    /**
     * Save port images — DELETE + batch INSERT.
     * Fires 'carspace_port_images_saved' action.
     *
     * @param int   $product_id
     * @param array $attachment_ids
     */
    public static function save($product_id, $attachment_ids) {
        global $wpdb;

        $product_id = intval($product_id);

        // Check previous state for notification trigger
        $had_before = self::has_images($product_id);

        $wpdb->delete(
            $wpdb->prefix . 'carspace_port_images',
            array('product_id' => $product_id),
            array('%d')
        );

        if (!empty($attachment_ids) && is_array($attachment_ids)) {
            $table = $wpdb->prefix . 'carspace_port_images';
            $values = array();
            $placeholders = array();

            foreach ($attachment_ids as $i => $att_id) {
                $placeholders[] = '(%d, %d, %d)';
                $values[] = $product_id;
                $values[] = intval($att_id);
                $values[] = $i;
            }

            $sql = "INSERT INTO {$table} (product_id, attachment_id, sort_order) VALUES "
                 . implode(', ', $placeholders);
            $wpdb->query($wpdb->prepare($sql, $values));
        }

        do_action('carspace_port_images_saved', $product_id, $attachment_ids, $had_before);
    }

    /**
     * Batch check which products have port images.
     *
     * @param array $product_ids
     * @return array [product_id => bool]
     */
    public static function batch_check($product_ids) {
        global $wpdb;

        $result = array();

        if (empty($product_ids)) {
            return $result;
        }

        $ids = array_map('intval', $product_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT product_id FROM {$wpdb->prefix}carspace_port_images WHERE product_id IN ({$placeholders})",
            $ids
        ));

        $has_set = array_flip($rows);

        foreach ($ids as $pid) {
            $result[$pid] = isset($has_set[$pid]);
        }

        return $result;
    }
}
