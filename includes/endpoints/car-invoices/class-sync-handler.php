<?php
/**
 * Sync Handler
 *
 * Recalculates invoice totals when admin updates data via backend.
 * ACF-free: reads/writes carspace_invoices table via Carspace_Invoice model.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

add_action('save_post_invoice', 'carspace_recalculate_invoice_totals_on_admin_edit', 20, 3);

/**
 * Recalculate invoice subtotal when an invoice CPT is saved in the admin backend.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 */
function carspace_recalculate_invoice_totals_on_admin_edit($post_id, $post, $update) {
    // Only trigger on admin edit, not frontend AJAX submission
    if (defined('DOING_AJAX') && DOING_AJAX && !current_user_can('administrator')) {
        return;
    }

    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Only proceed if invoice exists in custom table
    if (!Carspace_Invoice::exists($post_id)) {
        return;
    }

    // Recalculate subtotal from items + dealer_fee + commission
    Carspace_Invoice::recalculate_subtotal($post_id);
}
