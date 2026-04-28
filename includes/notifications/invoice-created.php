<?php
/**
 * Invoice Created Notification
 *
 * Notification sent when a new invoice is created for a user.
 * Reads invoice data from Carspace_Invoice model instead of ACF.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

/**
 * Hook into invoice creation
 */
add_action('save_post_invoice', 'carspace_check_new_invoice', 10, 3);

/**
 * Check if a new invoice was created and notify user
 *
 * @param int     $post_id The post ID
 * @param WP_Post $post    The post object
 * @param bool    $update  Whether this is an existing post being updated
 */
function carspace_check_new_invoice($post_id, $post, $update) {
    // Only proceed if this is a new invoice
    if ($update || $post->post_status !== 'publish') {
        return;
    }

    // Get invoice owner
    $invoice_owner = get_post_meta($post_id, 'invoice_owner', true);

    if (!$invoice_owner) {
        return;
    }

    // Get invoice from model
    $invoice = Carspace_Invoice::find($post_id);

    if (!$invoice) {
        return;
    }

    // Read customer name and invoice date from model
    $customer_name  = !empty($invoice->customer_name) ? $invoice->customer_name : '';
    $invoice_number = $post->post_title;
    $invoice_date   = !empty($invoice->invoice_date) ? $invoice->invoice_date : '';

    // Calculate total from items
    $invoice_total = 0;
    if (!empty($invoice->items)) {
        foreach ($invoice->items as $item) {
            $invoice_total += floatval($item->amount);
        }
    }

    // Check paid status from model
    $is_paid     = (strtolower($invoice->status) === 'paid');
    $status_text = $is_paid ? __('paid', 'carspace-dashboard') : __('unpaid', 'carspace-dashboard');

    // Create notification
    $title = sprintf(
        __('New Invoice: #%s', 'carspace-dashboard'),
        $invoice_number
    );

    $message = sprintf(
        __('A new %1$s invoice #%2$s for %3$s has been created on %4$s. View it in your dashboard.', 'carspace-dashboard'),
        $status_text,
        $invoice_number,
        wc_price($invoice_total),
        $invoice_date
    );

    carspace_create_notification(
        $invoice_owner,
        $title,
        $message,
        'invoice_paid',
        carspace_get_dashboard_url()
    );
}
