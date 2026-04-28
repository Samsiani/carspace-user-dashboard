<?php
/**
 * Car Invoices AJAX Handlers
 *
 * Handles AJAX requests for Car Invoices.
 * ACF-free: all data stored in carspace_invoices / carspace_invoice_items tables.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 */

defined('ABSPATH') || exit;

/**
 * Car Invoices AJAX Handlers Class
 */
class Carspace_Car_Invoices_AJAX_Handlers {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_create_invoice', array($this, 'handle_create_invoice'));
        add_action('wp_ajax_upload_receipt', array($this, 'handle_receipt_upload'));
        add_action('wp_ajax_update_dealer_fee', array($this, 'handle_update_dealer_fee'));
        add_action('wp_ajax_suggest_transport_price', array($this, 'handle_suggest_transport_price'));
    }

    /**
     * Suggest transport price for a vehicle based on Location ID and user tier.
     * - Requires: logged-in user
     * - Params: invoice_nonce, vehicle_id (optional), location_id (optional)
     * - Behavior: If location_id missing, derive from vehicle's "Location ID" attribute.
     * - Returns: { amount: number }
     */
    public function handle_suggest_transport_price() {
        // Security: must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'carspace-dashboard'));
            exit;
        }

        // Verify nonce if present (coming from the invoice form)
        if (isset($_POST['invoice_nonce']) && !wp_verify_nonce($_POST['invoice_nonce'], 'invoice_nonce_action')) {
            wp_send_json_error(__('Security check failed.', 'carspace-dashboard'));
            exit;
        }

        $vehicle_id  = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        $location_id = 0;

        // Prefer explicit location_id if provided
        if (isset($_POST['location_id'])) {
            $location_id = intval(preg_replace('/\D+/', '', (string) $_POST['location_id']));
        }

        // If no explicit location_id, try to read from the vehicle product attribute
        if ($location_id <= 0 && $vehicle_id > 0) {
            $product = wc_get_product($vehicle_id);
            if ($product) {
                // Try both custom attribute and taxonomy style
                $raw = $product->get_attribute('Location ID');
                if (empty($raw)) {
                    $raw = $product->get_attribute('pa_location-id');
                }
                if (!empty($raw)) {
                    $location_id = intval(preg_replace('/\D+/', '', (string) $raw));
                }
            }
        }

        if ($location_id <= 0) {
            wp_send_json_error(__('No Location ID found for the selected vehicle.', 'carspace-dashboard'));
            exit;
        }

        // Determine user's tier key (base_price or price1..price10)
        $tier_obj  = new TPC_User_Tier();
        $tier_key  = $tier_obj->get_user_tier(get_current_user_id());
        if (empty($tier_key)) {
            $tier_key = 'base_price';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tpc_prices';
        $row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $location_id));

        if (!$row) {
            wp_send_json_error(__('Price not found for the provided Location ID.', 'carspace-dashboard'));
            exit;
        }

        // Pick tier column, fallback to base_price
        $amount = null;
        if (isset($row->$tier_key) && $row->$tier_key !== '' && $row->$tier_key !== null) {
            $amount = floatval($row->$tier_key);
        } else {
            $amount = isset($row->base_price) ? floatval($row->base_price) : null;
        }

        if ($amount === null) {
            wp_send_json_error(__('No valid price available for this Location ID.', 'carspace-dashboard'));
            exit;
        }

        wp_send_json_success(array(
            'amount' => $amount,
        ));
        exit;
    }

    /**
     * Handle receipt upload AJAX request
     *
     * @return void
     */
    public function handle_receipt_upload() {
        // Check nonce security
        if (!isset($_POST['receipt_nonce']) || !wp_verify_nonce($_POST['receipt_nonce'], 'receipt_upload_nonce')) {
            wp_send_json_error(__('Security check failed', 'carspace-dashboard'));
            exit;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to upload receipts', 'carspace-dashboard'));
            exit;
        }

        // Check for invoice ID
        if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
            wp_send_json_error(__('Missing invoice ID', 'carspace-dashboard'));
            exit;
        }

        $invoice_id = intval($_POST['invoice_id']);

        // Check if invoice exists
        $invoice = get_post($invoice_id);
        if (!$invoice || $invoice->post_type !== 'invoice') {
            wp_send_json_error(__('Invalid invoice ID', 'carspace-dashboard'));
            exit;
        }

        // Check if file was uploaded
        if (!isset($_FILES['receipt_file']) || empty($_FILES['receipt_file']['name'])) {
            wp_send_json_error(__('No file was uploaded', 'carspace-dashboard'));
            exit;
        }

        // Handle file upload
        $file = $_FILES['receipt_file'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_error_strings = array(
                UPLOAD_ERR_INI_SIZE   => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'carspace-dashboard'),
                UPLOAD_ERR_FORM_SIZE  => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'carspace-dashboard'),
                UPLOAD_ERR_PARTIAL    => __('The uploaded file was only partially uploaded.', 'carspace-dashboard'),
                UPLOAD_ERR_NO_FILE    => __('No file was uploaded.', 'carspace-dashboard'),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.', 'carspace-dashboard'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'carspace-dashboard'),
                UPLOAD_ERR_EXTENSION  => __('A PHP extension stopped the file upload.', 'carspace-dashboard'),
            );
            $error_message = isset($upload_error_strings[$file['error']]) ? $upload_error_strings[$file['error']] : __('Unknown upload error', 'carspace-dashboard');
            wp_send_json_error($error_message);
            exit;
        }

        // Check file type
        $allowed_types = array(
            'image/jpeg',
            'image/png',
            'image/gif'
        );

        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'carspace-dashboard'));
            exit;
        }

        // Check file size (limit to 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            wp_send_json_error(__('File is too large. Maximum size is 5MB.', 'carspace-dashboard'));
            exit;
        }

        // Include required file for media handling
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Upload the file to WordPress media library
        $attachment_id = media_handle_upload('receipt_file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
            exit;
        }

        // Get attachment URL
        $attachment_url = wp_get_attachment_url($attachment_id);

        // Save to custom table
        Carspace_Invoice::update($invoice_id, array(
            'receipt_image_id'  => $attachment_id,
            'receipt_image_url' => $attachment_url,
        ));

        // Save attachment info to post meta for backward compat
        update_post_meta($invoice_id, '_receipt_image_id', $attachment_id);
        update_post_meta($invoice_id, '_receipt_image', $attachment_url);

        // Add timestamp info to receipt meta
        update_post_meta($invoice_id, '_receipt_uploaded_by', get_current_user_id());
        update_post_meta($invoice_id, '_receipt_uploaded_at', current_time('mysql'));

        // Return success with receipt URL
        wp_send_json_success(array(
            'message' => __('Receipt uploaded successfully!', 'carspace-dashboard'),
            'receipt_url' => $attachment_url
        ));
        exit;
    }

    /**
     * Handle dealer fee update AJAX request
     *
     * @return void
     */
    public function handle_update_dealer_fee() {
        // Check nonce security
        if (!isset($_POST['dealer_fee_nonce']) || !wp_verify_nonce($_POST['dealer_fee_nonce'], 'dealer_fee_nonce_action')) {
            wp_send_json_error(__('Security check failed', 'carspace-dashboard'));
            exit;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to update dealer fees', 'carspace-dashboard'));
            exit;
        }

        // Check for invoice ID
        if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
            wp_send_json_error(__('Missing invoice ID', 'carspace-dashboard'));
            exit;
        }

        $invoice_id = intval($_POST['invoice_id']);

        // Check if invoice exists
        $invoice = get_post($invoice_id);
        if (!$invoice || $invoice->post_type !== 'invoice') {
            wp_send_json_error(__('Invalid invoice ID', 'carspace-dashboard'));
            exit;
        }

        // Get dealer fee amount
        $dealer_fee = isset($_POST['dealer_fee']) ? floatval($_POST['dealer_fee']) : 0;

        // Get dealer fee note
        $dealer_fee_note = '';
        if (isset($_POST['dealer_fee_note'])) {
            $dealer_fee_note = wp_kses_post($_POST['dealer_fee_note']);
        }

        // Update dealer fee and note in custom table
        Carspace_Invoice::update($invoice_id, array(
            'dealer_fee'      => $dealer_fee,
            'dealer_fee_note' => $dealer_fee_note,
        ));

        // Recalculate subtotal from items + dealer_fee + commission
        $total = Carspace_Invoice::recalculate_subtotal($invoice_id);

        // Add timestamp info
        update_post_meta($invoice_id, '_dealer_fee_updated_by', get_current_user_id());
        update_post_meta($invoice_id, '_dealer_fee_updated_at', current_time('mysql'));

        // Format the dealer fee and total with proper currency format
        $dealer_fee_formatted = strip_tags(wc_price($dealer_fee));
        $total_formatted = strip_tags(wc_price($total));

        // Properly escape the dealer fee note for JavaScript
        $dealer_fee_note_escaped = esc_attr($dealer_fee_note);

        // Return success with formatted values and the properly escaped note
        wp_send_json_success(array(
            'message' => __('Dealer fee updated successfully!', 'carspace-dashboard'),
            'dealer_fee' => $dealer_fee,
            'dealer_fee_formatted' => $dealer_fee_formatted,
            'dealer_fee_note' => $dealer_fee_note,
            'dealer_fee_note_escaped' => $dealer_fee_note_escaped,
            'total' => $total,
            'total_formatted' => $total_formatted
        ));
        exit;
    }

    /**
     * Handle invoice creation AJAX request
     *
     * @return void
     */
    public function handle_create_invoice() {
        // Check for nonce security
        if (!isset($_POST['invoice_nonce']) || !wp_verify_nonce($_POST['invoice_nonce'], 'invoice_nonce_action')) {
            wp_send_json_error(__('Security check failed', 'carspace-dashboard'));
            exit;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to create invoices', 'carspace-dashboard'));
            exit;
        }

        // Create invoice CPT post with temporary title
        $invoice_id = wp_insert_post([
            'post_title'    => 'temp-invoice-' . time(),
            'post_status'   => 'publish',
            'post_type'     => 'invoice',
        ]);

        if (is_wp_error($invoice_id)) {
            wp_send_json_error($invoice_id->get_error_message());
            exit;
        }

        // Collect all model data
        $current_user_id = get_current_user_id();
        $invoice_type = isset($_POST['invoice_type_for_what']) ? sanitize_text_field($_POST['invoice_type_for_what']) : '';
        $dealer_fee = isset($_POST['dealer_fee_price']) ? floatval($_POST['dealer_fee_price']) : 0;
        $dealer_fee_note = isset($_POST['dealer_fee_note_save']) ? sanitize_textarea_field($_POST['dealer_fee_note_save']) : '';

        // Customer details
        $customer_type = '';
        $customer_name = '';
        $customer_company_name = '';
        $customer_personal_id = '';
        $company_ident_number = '';
        $invoice_date = null;

        if (!empty($_POST['customer_details'])) {
            $customer_type = isset($_POST['customer_details']['customer_type_choose']) ?
                sanitize_text_field($_POST['customer_details']['customer_type_choose']) : 'Individual';

            $customer_name = isset($_POST['customer_details']['customer_name']) ?
                sanitize_text_field($_POST['customer_details']['customer_name']) : '';

            $customer_company_name = isset($_POST['customer_details']['customer_company_name']) ?
                sanitize_text_field($_POST['customer_details']['customer_company_name']) : '';

            $customer_personal_id = isset($_POST['customer_details']['customer_id_or_other_doc']) ?
                sanitize_text_field($_POST['customer_details']['customer_id_or_other_doc']) : '';

            $company_ident_number = isset($_POST['customer_details']['company_ident_number']) ?
                sanitize_text_field($_POST['customer_details']['company_ident_number']) : '';

            $invoice_date = isset($_POST['customer_details']['invoice_date_picker']) ?
                sanitize_text_field($_POST['customer_details']['invoice_date_picker']) : null;
        }

        // Determine display name for post title
        $display_name = '';
        if ($customer_type === 'Company') {
            $display_name = $customer_company_name;
        } else {
            $display_name = $customer_name;
        }

        // Save display name to post meta for backward compat searches
        if (!empty($display_name)) {
            update_post_meta($invoice_id, 'buyer_name', $display_name);
            update_post_meta($invoice_id, '_customer_name', $display_name);
            update_post_meta($invoice_id, '_invoice_customer', $display_name);
        }

        // Parse products
        $vin_code = '';
        $product_total = 0;
        $items = array();

        // Check if we have the products JSON data
        if (!empty($_POST['products_json'])) {
            $products_data = json_decode(stripslashes($_POST['products_json']), true);
            if (is_array($products_data)) {
                foreach ($products_data as $product) {
                    $product_amount = floatval($product['_amount_']);
                    $product_total += $product_amount;

                    $items[] = array(
                        'sale_date' => sanitize_text_field($product['sale_date']),
                        'make'      => sanitize_text_field($product['make']),
                        'model'     => sanitize_text_field($product['model']),
                        'year'      => sanitize_text_field($product['year']),
                        'vin'       => sanitize_text_field($product['vin']),
                        'amount'    => $product_amount,
                    );

                    // Get the VIN of the first vehicle
                    if (empty($vin_code) && !empty($product['vin'])) {
                        $vin_code = sanitize_text_field($product['vin']);
                    }
                }
            }
        }
        // Fallback to traditional products array
        else if (!empty($_POST['products'])) {
            foreach ($_POST['products'] as $product) {
                if (!empty($product['make']) || !empty($product['model']) || !empty($product['vin'])) {
                    $product_amount = floatval($product['_amount_']);
                    $product_total += $product_amount;

                    $items[] = array(
                        'sale_date' => sanitize_text_field($product['sale_date']),
                        'make'      => sanitize_text_field($product['make']),
                        'model'     => sanitize_text_field($product['model']),
                        'year'      => sanitize_text_field($product['year']),
                        'vin'       => sanitize_text_field($product['vin']),
                        'amount'    => $product_amount,
                    );

                    if (empty($vin_code) && !empty($product['vin'])) {
                        $vin_code = sanitize_text_field($product['vin']);
                    }
                }
            }
        }

        // Store VIN in post_meta for backward compat searches
        if (!empty($vin_code)) {
            update_post_meta($invoice_id, 'vin', $vin_code);
        }

        // Calculate subtotal
        $invoice_total = $product_total + $dealer_fee;

        // Calculate extra commission for dealer
        $extra_commission = 0;
        $user_type = get_user_meta($current_user_id, '_commission_type', true);
        $is_transport_invoice = ($invoice_type === "\xe1\x83\xa2\xe1\x83\xa0\xe1\x83\x90\xe1\x83\x9c\xe1\x83\xa1\xe1\x83\x9e\xe1\x83\x9d\xe1\x83\xa0\xe1\x83\xa2\xe1\x83\x98\xe1\x83\xa0\xe1\x83\x94\xe1\x83\x91\xe1\x83\x98\xe1\x83\xa1 \xe1\x83\xa1\xe1\x83\x90\xe1\x83\xa4\xe1\x83\x90\xe1\x83\xa1\xe1\x83\xa3\xe1\x83\xa0\xe1\x83\x98");

        if (!$is_transport_invoice && !empty($items)) {
            if ($user_type === 'default') {
                foreach ($items as $item) {
                    $amount = isset($item['amount']) ? floatval($item['amount']) : 0;
                    if ($amount <= 15000) {
                        $extra_commission += 35;
                    } else {
                        $extra_commission += round($amount * 0.004, 2);
                    }
                }
            } else {
                if ($product_total <= 15000) {
                    $extra_commission = 35;
                } else {
                    $extra_commission = round($product_total * 0.004, 2);
                }
            }
        }

        // Create invoice record in custom table (single call)
        Carspace_Invoice::create(
            array(
                'post_id'               => $invoice_id,
                'invoice_type'          => $invoice_type,
                'status'                => 'unpaid',
                'customer_type'         => $customer_type,
                'customer_name'         => $customer_name,
                'customer_company_name' => $customer_company_name,
                'customer_personal_id'  => $customer_personal_id,
                'company_ident_number'  => $company_ident_number,
                'invoice_date'          => $invoice_date,
                'dealer_fee'            => $dealer_fee,
                'dealer_fee_note'       => $dealer_fee_note,
                'commission'            => $extra_commission,
                'subtotal'              => $invoice_total + $extra_commission,
                'owner_user_id'         => $current_user_id,
            ),
            $items
        );

        // Update the post title with {post_id}-INV-{vin_code} format
        $new_title = $invoice_id . '-INV-' . $vin_code;

        // Also include customer name in the title for better identification
        if (!empty($display_name)) {
            $new_title = $invoice_id . '-INV-' . $vin_code . ' (' . $display_name . ')';
        }

        wp_update_post([
            'ID'          => $invoice_id,
            'post_title'  => $new_title,
            'post_name'   => sanitize_title($new_title),
        ]);

        // Return success with redirect
        $view_url = get_permalink($invoice_id);
        wp_send_json_success(
            __('Invoice created successfully!', 'carspace-dashboard'),
            array(
                'redirect' => $view_url,
            )
        );
        exit;
    }
}
