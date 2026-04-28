<?php
/**
 * Car Invoices Render Class
 *
 * Handles rendering of the car invoices frontend tables.
 * ACF-free: all data read from carspace_invoices / carspace_invoice_items tables.
 *
 * @package Carspace_Dashboard
 * @since 4.0.0
 *
 * Override Update (2025-08-24):
 * - Car Price (data-price) NO LONGER overridden by WooCommerce Regular Price. It now always uses the attribute "Price ($)" only.
 * - Transport Price (data-transport-price) is now overridden by WooCommerce REGULAR Price (if set). Sale Price is no longer used at all.
 * - If WC Regular Price empty, original attribute-based / dynamic suggestion logic remains unchanged.
 */

defined('ABSPATH') || exit;

/**
 * Car Invoices Render Class
 */
class Carspace_Car_Invoices_Render {

    /**
     * Render a simple invoice table without edit/action buttons
     *
     * @param array $invoices Array of invoice posts
     */
    public static function render_simple_invoice_table($invoices) {
        // Pagination settings
        $current_page = 1;
        $items_per_page = 15;
        $total_items = count($invoices);
        $total_pages = ceil($total_items / $items_per_page);

        if ($current_page < 1) $current_page = 1;
        if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

        $start = ($current_page - 1) * $items_per_page;
        $paged_invoices = array_slice($invoices, $start, $items_per_page);

        echo '<div class="car-table-wrapper">';
        echo '<table class="table table-bordered table-hover align-middle text-center invoice-table">';
        echo '<thead class="table-light">
                <tr>
                    <th class="sortable" data-sort="status">' . esc_html__('Status', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="invoice-number">' . esc_html__('Invoice Number', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="invoice-type">' . esc_html__('Invoice Type', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="customer-name">' . esc_html__('Customer Name', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="customer-id">' . esc_html__('Customer ID', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="date" data-type="date">' . esc_html__('Date', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="dealer-fee" data-type="number">' . esc_html__('Dealer Fee', 'carspace-dashboard') . '</th>
                    <th class="sortable" data-sort="amount" data-type="number">' . esc_html__('Amount', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('View', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('Receipt', 'carspace-dashboard') . '</th>
                </tr>
              </thead><tbody>';

        $row_count = 0;

        foreach ($paged_invoices as $invoice_post) {
            $invoice_id = $invoice_post->ID;

            // Load invoice data from custom table
            $invoice = Carspace_Invoice::find($invoice_id);

            // Invoice type
            $invoice_type = $invoice ? $invoice->invoice_type : '';
            if (empty($invoice_type)) {
                $invoice_type = __('Not specified', 'carspace-dashboard');
            }

            // Dealer fee
            $dealer_fee = $invoice ? floatval($invoice->dealer_fee) : 0;

            // Dealer fee note
            $dealer_fee_note = $invoice ? $invoice->dealer_fee_note : '';
            $dealer_fee_note_escaped = !empty($dealer_fee_note) ? esc_attr($dealer_fee_note) : '';

            // Product amounts from items
            $product_total = 0;
            if ($invoice && !empty($invoice->items) && is_array($invoice->items)) {
                foreach ($invoice->items as $item) {
                    $product_total += floatval($item->amount);
                }
            }

            // Extra commission
            $extra_commission = $invoice ? floatval($invoice->commission) : 0;

            // Invoice total
            $invoice_total = $product_total + $dealer_fee + $extra_commission;

            // Payment status
            $payment_status = $invoice ? $invoice->status : 'unpaid';
            if (empty($payment_status)) {
                $payment_status = 'unpaid';
            }

            // Customer details
            $customer_type = $invoice ? $invoice->customer_type : '';
            if ($customer_type === 'Company') {
                $customer_name = $invoice ? $invoice->customer_company_name : '';
                $customer_id_display = $invoice ? $invoice->company_ident_number : '';
            } else {
                $customer_name = $invoice ? $invoice->customer_name : '';
                $customer_id_display = $invoice ? $invoice->customer_personal_id : '';
            }

            // Status icon
            if ($payment_status === 'paid') {
                $status_text = __('Paid', 'carspace-dashboard');
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="status-icon"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
                $color_class = 'status-paid';
            } elseif ($payment_status === 'partly_paid' || $payment_status === 'partly-paid') {
                $status_text = __('Partly Paid', 'carspace-dashboard');
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="status-icon"><path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z"/><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/></svg>';
                $color_class = 'status-partly-paid';
            } else {
                $status_text = __('Unpaid', 'carspace-dashboard');
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="status-icon"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
                $color_class = 'status-unpaid';
            }

            $row_class = (++$row_count % 2 == 0) ? 'even' : 'odd';

            // Creation date
            $creation_date = get_the_date('d/m/Y', $invoice_id);

            // Receipt
            $receipt_url = get_post_meta($invoice_id, '_receipt_image', true);
            $has_receipt = !empty($receipt_url);

            echo '<tr class="' . esc_attr($row_class) . '" id="invoice-row-' . esc_attr($invoice_id) . '">';
            echo '<td class="' . esc_attr($color_class) . '" data-label="Status" id="status-cell-' . esc_attr($invoice_id) . '">' . $icon . ' ' . esc_html($status_text) . '</td>';
            echo '<td data-label="Invoice Number">' . esc_html__('Invoice', 'carspace-dashboard') . ' #' . esc_html($invoice_id) . '</td>';
            echo '<td data-label="Invoice Type">' . esc_html($invoice_type) . '</td>';
            echo '<td data-label="Customer Name">' . esc_html($customer_name ?: '-') . '</td>';
            echo '<td data-label="Customer ID">' . esc_html($customer_id_display ?: '-') . '</td>';
            echo '<td data-label="Date">' . esc_html($creation_date) . '</td>';

            echo '<td data-label="Dealer Fee" id="dealer-fee-cell-' . esc_attr($invoice_id) . '">';
            if ($dealer_fee > 0) {
                echo '<span class="dealer-fee-amount">' . wc_price($dealer_fee) . '</span>';
                echo '<button class="btn btn-sm btn-outline-secondary ms-2 edit-dealer-fee"
                      data-bs-toggle="modal"
                      data-bs-target="#dealerFeeModal"
                      data-invoice-id="' . esc_attr($invoice_id) . '"
                      data-dealer-fee="' . esc_attr($dealer_fee) . '"
                      data-dealer-fee-note="' . $dealer_fee_note_escaped . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                    </svg>
                </button>';
            } else {
                echo '<button class="btn btn-sm btn-outline-primary add-dealer-fee"
                      data-bs-toggle="modal"
                      data-bs-target="#dealerFeeModal"
                      data-invoice-id="' . esc_attr($invoice_id) . '"
                      data-dealer-fee="0"
                      data-dealer-fee-note="">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>
                    <span class="ms-1">' . esc_html__('Add Dealer Fee', 'carspace-dashboard') . '</span>
                </button>';
            }
            echo '</td>';

            echo '<td data-label="Amount" id="amount-cell-' . esc_attr($invoice_id) . '">' . wc_price($invoice_total) . '</td>';

            echo '<td data-label="View">
                <a href="' . esc_url(get_permalink($invoice_id)) . '" class="btn btn-sm btn-outline-primary" title="' . esc_attr__('View Invoice', 'carspace-dashboard') . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text-icon lucide-file-text">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                        <path d="M10 9H8"/>
                        <path d="M16 13H8"/>
                        <path d="M16 17H8"/>
                    </svg>
                </a>
            </td>';

            echo '<td data-label="Receipt" id="receipt-cell-' . esc_attr($invoice_id) . '">';
            if ($has_receipt) {
                echo '<a href="' . esc_url($receipt_url) . '" target="_blank" class="btn btn-sm btn-outline-success view-receipt" title="' . esc_attr__('View Receipt', 'carspace-dashboard') . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-icon lucide-eye">
                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span class="ms-1">' . esc_html__('View Receipt', 'carspace-dashboard') . '</span>
                </a>';
            } else {
                echo '<button class="btn btn-sm btn-outline-primary upload-receipt"
                      data-bs-toggle="modal"
                      data-bs-target="#receiptUploadModal"
                      data-invoice-id="' . esc_attr($invoice_id) . '"
                      title="' . esc_attr__('Upload Receipt', 'carspace-dashboard') . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload-icon lucide-upload">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" x2="12" y1="3" y2="15"/>
                    </svg>
                    <span class="ms-1">' . esc_html__('Add Receipt', 'carspace-dashboard') . '</span>
                </button>';
            }
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';

        if ($total_pages > 1) {
            Carspace_Car_Invoices_Helpers::render_simple_pagination($total_pages, $current_page);
        }

        self::enqueue_table_assets();
    }

    /**
     * Render invoice creation form
     */
    public static function render_invoice_form() {
        $user_id = get_current_user_id();
        $assigned_cars = carspace_get_user_assigned_cars($user_id);
        $vehicle_count = is_array($assigned_cars) ? count($assigned_cars) : 0;

        ?>
        <div class="create-invoice-section card mt-4">
            <div class="create-invoice card-header d-flex justify-content-between align-items-center">
                <span id="form-title"><?php esc_html_e('Create New Invoice', 'carspace-dashboard'); ?></span>
                <button type="button" class="btn btn-sm btn-primary" id="toggle-invoice-form">
                    <?php esc_html_e('Create Invoice', 'carspace-dashboard'); ?>
                </button>
            </div>
            <div class="card-body" id="invoice-form-container" style="display: none;">
                <?php if (empty($assigned_cars)): ?>
                <div class="alert alert-warning">
                    <?php esc_html_e('No vehicles are assigned to your account. You need at least one vehicle to create an invoice.', 'carspace-dashboard'); ?>
                </div>
                <?php else: ?>
                <form id="create-invoice-form" class="needs-validation" novalidate>
                    <?php wp_nonce_field('invoice_nonce_action', 'invoice_nonce'); ?>
                    <input type="hidden" name="invoice_owner" value="<?php echo esc_attr($user_id); ?>">

                    <h5 class="mb-3"><?php esc_html_e('Invoice Type', 'carspace-dashboard'); ?></h5>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type_for_what" id="invoice_type_transport" value="ტრანსპორტირების საფასური" checked>
                                <label class="form-check-label" for="invoice_type_transport">
                                    <?php esc_html_e('ტრანსპორტირების საფასური', 'carspace-dashboard'); ?>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type_for_what" id="invoice_type_purchase" value="ავტომობილის საფასური">
                                <label class="form-check-label" for="invoice_type_purchase">
                                    <?php esc_html_e('ავტომობილის საფასური', 'carspace-dashboard'); ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3"><?php esc_html_e('Customer Details', 'carspace-dashboard'); ?></h5>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="invoice_date" class="form-label">
                                <?php esc_html_e('Invoice Date', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" name="customer_details[invoice_date_picker]" id="invoice_date" required
                                value="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">
                                <?php esc_html_e('Customer Type', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="customer_details[customer_type_choose]" id="customer_type_individual" value="Individual" checked>
                                <label class="form-check-label" for="customer_type_individual">
                                    <?php esc_html_e('Individual', 'carspace-dashboard'); ?>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="customer_details[customer_type_choose]" id="customer_type_company" value="Company">
                                <label class="form-check-label" for="customer_type_company">
                                    <?php esc_html_e('Company', 'carspace-dashboard'); ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3" id="individual_fields">
                        <div class="col-md-9">
                            <label for="customer_name" class="form-label">
                                <?php esc_html_e('Customer Name', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="customer_details[customer_name]" id="customer_name" required>
                        </div>
                        <div class="col-md-3">
                            <label for="customer_id" class="form-label">
                                <?php esc_html_e('Customer ID', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="customer_details[customer_id_or_other_doc]" id="customer_id" required>
                        </div>
                    </div>

                    <div class="row mb-3" id="company_fields" style="display: none;">
                        <div class="col-md-9">
                            <label for="customer_company_name" class="form-label">
                                <?php esc_html_e('Company Name', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="customer_details[customer_company_name]" id="customer_company_name">
                        </div>
                        <div class="col-md-3">
                            <label for="company_ident_number" class="form-label">
                                <?php esc_html_e('Company Ident Number', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="customer_details[company_ident_number]" id="company_ident_number">
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3"><?php esc_html_e('Products', 'carspace-dashboard'); ?></h5>

                    <div id="product-items">
                        <div class="product-item card mb-3">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <?php esc_html_e('Vehicle', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select vehicle-select" name="vehicle_id_0" required>
                                            <option value=""><?php esc_html_e('Select Vehicle', 'carspace-dashboard'); ?></option>
                                            <?php
                                            if (!empty($assigned_cars)) {
                                                foreach ($assigned_cars as $car) {
                                                    $car_id = $car->ID;
                                                    $product = wc_get_product($car_id);

                                                    if ($product) {
                                                        $make = Carspace_Car_Invoices_Helpers::get_vehicle_attribute($car_id, 'Make');
                                                        $model = Carspace_Car_Invoices_Helpers::get_vehicle_attribute($car_id, 'Model');
                                                        $year = Carspace_Car_Invoices_Helpers::get_vehicle_attribute($car_id, 'Year');
                                                        $vin  = $product->get_sku();
                                                        $loc_id_raw = Carspace_Car_Invoices_Helpers::get_vehicle_attribute($car_id, 'Location ID');
                                                        $car_price_attr = Carspace_Car_Invoices_Helpers::get_vehicle_attribute($car_id, 'Price ($)');

                                                        // Car price uses ONLY attribute (no regular price override)
                                                        $effective_car_price = $car_price_attr;
                                                        // Transport override uses WooCommerce REGULAR price if set
                                                        $regular_price = $product->get_regular_price();

                                                        $display_text = '';
                                                        if ($year) $display_text .= $year . ' ';
                                                        if ($make) $display_text .= $make . ' ';
                                                        if ($model) $display_text .= $model;
                                                        if ($vin)  $display_text .= ' - VIN: ' . $vin;
                                                        if (empty(trim($display_text))) {
                                                            $display_text = $product->get_name();
                                                        }

                                                        echo '<option value="' . esc_attr($car_id) . '"
                                                            data-make="' . esc_attr($make) . '"
                                                            data-model="' . esc_attr($model) . '"
                                                            data-year="' . esc_attr($year) . '"
                                                            data-vin="' . esc_attr($vin) . '"
                                                            data-location-id="' . esc_attr($loc_id_raw) . '"
                                                            data-price="' . esc_attr($effective_car_price) . '"';

                                                        // Add transport override if regular price exists
                                                        if ($regular_price !== '') {
                                                            echo ' data-transport-price="' . esc_attr($regular_price) . '"';
                                                        }

                                                        echo '>' . esc_html($display_text) . '</option>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <?php esc_html_e('Sale Date', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" name="products[0][sale_date]" required
                                            value="<?php echo esc_attr(date('Y-m-d')); ?>">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label"><?php esc_html_e('Make', 'carspace-dashboard'); ?></label>
                                        <input type="text" class="form-control car-make" name="products[0][make]" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label"><?php esc_html_e('Model', 'carspace-dashboard'); ?></label>
                                        <input type="text" class="form-control car-model" name="products[0][model]" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label"><?php esc_html_e('Year', 'carspace-dashboard'); ?></label>
                                        <input type="text" class="form-control car-year" name="products[0][year]" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label"><?php esc_html_e('VIN', 'carspace-dashboard'); ?></label>
                                        <input type="text" class="form-control car-vin" name="products[0][vin]" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <?php esc_html_e('Amount ($)', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control product-amount" name="products[0][_amount_]" step="0.01" min="0" required>
                                            <button type="button" class="btn btn-outline-secondary suggest-transport-price" title="<?php echo esc_attr__('Suggest based on Location ID and your tier', 'carspace-dashboard'); ?>">
                                                <?php esc_html_e('Calculate', 'carspace-dashboard'); ?>
                                            </button>
                                        </div>
                                        <div class="form-text text-muted small suggest-feedback" style="display:none;"></div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-danger remove-product" disabled>
                                            <?php esc_html_e('Remove', 'carspace-dashboard'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-secondary" id="add-product">
                            <?php esc_html_e('Add Another Vehicle', 'carspace-dashboard'); ?>
                        </button>
                    </div>

                    <h5 class="mt-4 mb-3"><?php esc_html_e('Dealer Fee', 'carspace-dashboard'); ?></h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dealer_fee" class="form-label">
                                <?php esc_html_e('Dealer Fee ($)', 'carspace-dashboard'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="dealer_fee_price" id="dealer_fee" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="dealer_fee_note" class="form-label">
                                <?php esc_html_e('Dealer Fee Note', 'carspace-dashboard'); ?>
                            </label>
                            <textarea class="form-control" name="dealer_fee_note_save" id="dealer_fee_note" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title"><?php esc_html_e('Invoice Summary', 'carspace-dashboard'); ?></h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><?php esc_html_e('Products Total:', 'carspace-dashboard'); ?></td>
                                            <td class="text-end"><span id="products-total">$0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td><?php esc_html_e('Dealer Fee:', 'carspace-dashboard'); ?></td>
                                            <td class="text-end"><span id="dealer-fee-display">$0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td><?php esc_html_e('Dealer Commission:', 'carspace-dashboard'); ?></td>
                                            <td class="text-end"><span id="dealer-commission-display">$0.00</span></td>
                                        </tr>
                                        <tr class="fw-bold">
                                            <td><?php esc_html_e('Total:', 'carspace-dashboard'); ?></td>
                                            <td class="text-end"><span id="invoice-total">$0.00</span></td>
                                        </tr>
                                    </table>
                                    <input type="hidden" id="user_commission_type" value="<?php echo esc_attr(get_user_meta(get_current_user_id(), '_commission_type', true)); ?>">
                                    <input type="hidden" name="subtotal_with_dealer_fee" id="subtotal_field" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 mt-4">
                        <button type="submit" class="btn btn-primary" id="submit-button">
                            <?php esc_html_e('Create Invoice', 'carspace-dashboard'); ?>
                        </button>
                        <div id="form-feedback" class="mt-3"></div>
                    </div>
                </form>
                <?php endif; ?>

                <div class="mt-4 small text-muted">
                    <?php echo esc_html(sprintf(__('Available vehicles: %d', 'carspace-dashboard'), $vehicle_count)); ?>
                </div>
            </div>
        </div>
        <?php
        self::enqueue_form_assets();
    }

    /**
     * Enqueue table assets (CSS and JavaScript)
     */
    private static function enqueue_table_assets() {
        ?>
        <style>
        .status-icon { vertical-align: middle; margin-right: 5px; }
        .status-paid { font-weight: 500; color: #22c55e; }
        .status-unpaid { font-weight: 500; color: #f43f5e; }
        .status-partly-paid { font-weight: 600 !important; color: #f59e0b !important; }
        .invoice-table { text-align: left !important; }
        .invoice-table tr td, .invoice-table th.sortable { padding: 8px 15px !important; }
        .upload-receipt, .view-receipt, .add-dealer-fee, .edit-dealer-fee { white-space: nowrap; }
        .upload-receipt svg, .view-receipt svg, .add-dealer-fee svg, .edit-dealer-fee svg { width: 18px; height: 18px; vertical-align: middle; margin-top: -2px; }
        #imagePreview { max-width: 100%; max-height: 200px; margin-top: 10px; border: 1px solid #dee2e6; border-radius: 4px; display: none; }
        .lucide-file-text-icon { width: 18px; height: 18px; }
        .dealer-fee-amount { display: inline-block; margin-right: 5px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            window.modalInitialized = false;

            $('.invoice-table th.sortable').click(function() {
                const table = $(this).closest('table');
                const rows = table.find('tbody tr').toArray();
                const column = $(this).index();
                const dataType = $(this).data('type') || 'string';
                const direction = $(this).hasClass('asc') ? -1 : 1;

                $(this).toggleClass('asc desc');
                if($(this).hasClass('asc')) { $(this).removeClass('desc'); } else { $(this).addClass('desc').removeClass('asc'); }
                $(this).siblings().removeClass('asc desc');

                rows.sort((a, b) => {
                    const cellA = $(a).children('td').eq(column).text().trim();
                    const cellB = $(b).children('td').eq(column).text().trim();
                    if (dataType === 'number') {
                        return direction * (parseFloat(cellA) - parseFloat(cellB));
                    } else if (dataType === 'date') {
                        const dateA = cellA.split('/').reverse().join('');
                        const dateB = cellB.split('/').reverse().join('');
                        return direction * (dateA > dateB ? 1 : -1);
                    } else {
                        return direction * cellA.localeCompare(cellB);
                    }
                });

                $.each(rows, function(index, row) {
                    $(row).removeClass('even odd').addClass(index % 2 ? 'even' : 'odd');
                    table.children('tbody').append(row);
                });
            });

            function resetModalState() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            }

            $(document).on('click', '.upload-receipt', function() {
                resetModalState();
                const invoiceId = $(this).data('invoice-id');
                $('#receipt_invoice_id').val(invoiceId);
                $('#receiptUploadForm')[0]?.reset();
                $('#imagePreview').hide();
                $('#uploadFeedback').html('').hide();
                $('#uploadReceiptBtn').prop('disabled', false).html('<?php echo esc_js(__('Upload Receipt', 'carspace-dashboard')); ?>');
            });

            $(document).on('click', '.add-dealer-fee, .edit-dealer-fee', function() {
                resetModalState();
                const invoiceId = $(this).data('invoice-id');
                const dealerFee = $(this).data('dealer-fee') || '';
                const dealerFeeNote = $(this).data('dealer-fee-note') || '';
                $('#dealerFeeForm')[0]?.reset();
                $('#dealerFeeFeedback').html('').hide();
                $('#dealer_fee_invoice_id').val(invoiceId);
                $('#dealer_fee_amount').val(dealerFee);
                $('#dealer_fee_note').val(dealerFeeNote);
                const modalTitle = dealerFee > 0 ? '<?php echo esc_js(__('Edit Dealer Fee', 'carspace-dashboard')); ?>' : '<?php echo esc_js(__('Add Dealer Fee', 'carspace-dashboard')); ?>';
                $('#dealerFeeModalLabel').text(modalTitle);
                $('#saveDealerFeeBtn').prop('disabled', false).html('<?php echo esc_js(__('Save Dealer Fee', 'carspace-dashboard')); ?>');
            });

            $(document).on('hidden.bs.modal', '.modal', function() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
                const form = $(this).find('form');
                if (form.length) { form[0].reset(); }
                $(this).find('.alert').hide();
                if ($(this).attr('id') === 'dealerFeeModal') {
                    $('#saveDealerFeeBtn').prop('disabled', false)
                        .html('<?php echo esc_js(__('Save Dealer Fee', 'carspace-dashboard')); ?>');
                } else if ($(this).attr('id') === 'receiptUploadModal') {
                    $('#uploadReceiptBtn').prop('disabled', false)
                        .html('<?php echo esc_js(__('Upload Receipt', 'carspace-dashboard')); ?>');
                }
                $('#imagePreview').hide();
            });

            if (!window.modalInitialized) {
                $('#saveDealerFeeBtn').data('original-text', $('#saveDealerFeeBtn').html());
                $('#uploadReceiptBtn').data('original-text', $('#uploadReceiptBtn').html());
                window.modalInitialized = true;
            }
        });
        </script>
        <?php
    }

    /**
     * Enqueue form assets (CSS and JavaScript)
     */
    private static function enqueue_form_assets() {
        ?>
        <style>
        .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.25rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 12px; padding-left: 12px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .select2-dropdown { border: 1px solid #ced4da; }
        .select2-search--dropdown .select2-search__field { padding: 6px; border: 1px solid #ced4da; }
        .select2-container--default .select2-results__option { padding: 6px 12px; line-height: 1.4; }
        .action-buttons .btn { margin-right: 5px; }
        .action-buttons .btn:last-child { margin-right: 0; }
        .suggest-feedback { margin-top: 6px; }
        .suggest-feedback.alert { padding: 6px 10px; margin-bottom: 0; }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {

        function getSelectedInvoiceType() {
            const f = document.querySelector('input[name="invoice_type_for_what"]:checked');
            return f ? f.value : '';
        }

        function toggleSuggestButtonsByType() {
            const isTransport = getSelectedInvoiceType() === 'ტრანსპორტირების საფასური';
            jQuery('.product-item').each(function() {
                const row = jQuery(this);
                const btn = row.find('.suggest-transport-price');
                if (isTransport) { btn.removeClass('d-none'); } else { btn.addClass('d-none'); }
            });
        }

        function parsePrice(val) {
            if (val === undefined || val === null) return null;
            let s = String(val).trim();
            s = s.replace(/[^0-9.,-]/g, '');
            if (s.indexOf('.') !== -1 && s.indexOf(',') !== -1) {
                s = s.replace(/,/g, '');
            } else if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) {
                s = s.replace(',', '.');
            }
            const num = parseFloat(s);
            return isNaN(num) ? null : num;
        }

        // Car Price: attribute only (data-price)
        function fillAmountFromCarPrice(row) {
            const vehicleSelect = row.find('.vehicle-select');
            const selectedOption = vehicleSelect.find(':selected');
            const amountInput = row.find('.product-amount');
            const priceRaw = selectedOption.data('price');
            const priceNum = parsePrice(priceRaw);
            if (priceNum !== null) {
                amountInput.val(priceNum.toFixed(2)).trigger('input');
            } else {
                amountInput.val('').trigger('input');
            }
        }

        // Transport Price override: WC Regular Price if present (data-transport-price)
        function fillAmountFromTransportOverride(row) {
            const vehicleSelect = row.find('.vehicle-select');
            const selectedOption = vehicleSelect.find(':selected');
            const amountInput = row.find('.product-amount');
            const transportOverride = selectedOption.data('transport-price');
            const priceNum = parsePrice(transportOverride);
            if (priceNum !== null) {
                amountInput.val(priceNum.toFixed(2)).trigger('input');
                return true;
            }
            return false;
        }

        function requestSuggest(row, btn) {
            const $ = jQuery;
            const vehicleSelect = row.find('.vehicle-select');
            const selectedOption = vehicleSelect.find(':selected');
            const amountInput = row.find('.product-amount');
            const feedback = row.find('.suggest-feedback');

            feedback.removeClass('alert alert-danger alert-warning alert-success').hide().text('');

            const vehicleId = vehicleSelect.val() || '';
            const locationIdRaw = selectedOption.data('location-id') || '';

            if (!vehicleId) {
                feedback.addClass('alert alert-warning').text('<?php echo esc_js(__('Please select a vehicle first.', 'carspace-dashboard')); ?>').show();
                return;
            }

            // If regular price override exists for transport invoice, use it first
            if (getSelectedInvoiceType() === 'ტრანსპორტირების საფასური') {
                if (fillAmountFromTransportOverride(row)) {
                    feedback.addClass('alert alert-success').text('<?php echo esc_js(__('Transport price overridden by Regular Price.', 'carspace-dashboard')); ?>').show();
                    return;
                }
            }

            const formData = new FormData();
            formData.append('action', 'suggest_transport_price');
            formData.append('invoice_nonce', jQuery('#invoice_nonce').val());
            formData.append('vehicle_id', vehicleId);
            if (locationIdRaw) formData.append('location_id', locationIdRaw);

            let originalHtml = '';
            if (btn && btn.length) {
                originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span><?php echo esc_js(__('Suggesting...', 'carspace-dashboard')); ?>');
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp) {
                    if (resp && resp.success && resp.data && typeof resp.data.amount !== 'undefined') {
                        const amount = parseFloat(resp.data.amount) || 0;
                        amountInput.val(amount.toFixed(2)).trigger('input');
                        feedback.addClass('alert alert-success').text('<?php echo esc_js(__('Suggested price applied. You can edit it if needed.', 'carspace-dashboard')); ?>').show();
                    } else {
                        const msg = resp && resp.data ? resp.data : '<?php echo esc_js(__('Unable to suggest a price for this vehicle.', 'carspace-dashboard')); ?>';
                        feedback.addClass('alert alert-danger').text(msg).show();
                    }
                },
                error: function() {
                    feedback.addClass('alert alert-danger').text('<?php echo esc_js(__('An error occurred while suggesting price.', 'carspace-dashboard')); ?>').show();
                },
                complete: function() {
                    if (btn && btn.length) {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                }
            });
        }

        function calculateTotals() {
            let productsTotal = 0;
            let dealerCommission = 0;

            const userTypeField = document.getElementById('user_commission_type');
            const userType = userTypeField ? userTypeField.value : 'custom';

            const invoiceTypeField = document.querySelector('input[name="invoice_type_for_what"]:checked');
            const invoiceType = invoiceTypeField ? invoiceTypeField.value : '';
            const isTransportInvoice = invoiceType === 'ტრანსპორტირების საფასური';

            document.querySelectorAll('.product-amount').forEach(function(input) {
                const amount = parseFloat(input.value) || 0;
                productsTotal += amount;

                if (!isTransportInvoice && userType === 'default') {
                    dealerCommission += amount <= 15000 ? 35 : +(amount * 0.004).toFixed(2);
                }
            });

            if (!isTransportInvoice && userType !== 'default') {
                dealerCommission = productsTotal <= 15000 ? 35 : +(productsTotal * 0.004).toFixed(2);
            }

            const dealerFee = parseFloat(document.getElementById('dealer_fee').value) || 0;
            const total = productsTotal + dealerFee + dealerCommission;

            document.getElementById('products-total').textContent = '$' + productsTotal.toFixed(2);
            document.getElementById('dealer-fee-display').textContent = '$' + dealerFee.toFixed(2);
            document.getElementById('dealer-commission-display').textContent = '$' + dealerCommission.toFixed(2);
            document.getElementById('invoice-total').textContent = '$' + total.toFixed(2);
            document.getElementById('subtotal_field').value = total.toFixed(2);
        }

        window.calculateTotals = calculateTotals;
        window.requestSuggest = requestSuggest;

        document.querySelectorAll('input[name="invoice_type_for_what"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const isTransport = this.value === 'ტრანსპორტირების საფასური';
                toggleSuggestButtonsByType();

                jQuery('.product-item').each(function() {
                    const row = jQuery(this);
                    const amountInput = row.find('.product-amount');
                    amountInput.val('');

                    const vehicleSelected = !!row.find('.vehicle-select').val();
                    if (!vehicleSelected) return;

                    if (isTransport) {
                        if (!fillAmountFromTransportOverride(row)) {
                            requestSuggest(row);
                        }
                    } else {
                        fillAmountFromCarPrice(row);
                    }
                });

                calculateTotals();
            });
        });

        function setupTotalCalculation() {
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('product-amount') || e.target.id === 'dealer_fee') {
                    calculateTotals();
                }
            });
        }

        function setupCustomerTypeToggle() {
            const customerTypeRadios = document.querySelectorAll('input[name="customer_details[customer_type_choose]"]');
            if (customerTypeRadios.length) {
                customerTypeRadios.forEach(function(radio) {
                    radio.addEventListener('change', toggleCustomerFields);
                });
            }
        }

        function toggleCustomerFields() {
            const checkedRadio = document.querySelector('input[name="customer_details[customer_type_choose]"]:checked');
            const customerType = checkedRadio ? checkedRadio.value : 'Individual';
            const individualFields = document.getElementById('individual_fields');
            const companyFields = document.getElementById('company_fields');
            if (!individualFields || !companyFields) return;
            if (customerType === 'Individual') {
                individualFields.style.display = 'flex';
                companyFields.style.display = 'none';
                if (document.getElementById('customer_name')) {
                    document.getElementById('customer_name').required = true;
                    document.getElementById('customer_id').required = true;
                    document.getElementById('customer_company_name').required = false;
                    document.getElementById('company_ident_number').required = false;
                }
            } else {
                individualFields.style.display = 'none';
                companyFields.style.display = 'flex';
                if (document.getElementById('customer_name')) {
                    document.getElementById('customer_name').required = false;
                    document.getElementById('customer_id').required = false;
                    document.getElementById('customer_company_name').required = true;
                    document.getElementById('company_ident_number').required = true;
                }
            }
        }

        document.getElementById('toggle-invoice-form')?.addEventListener('click', function() {
            const formContainer = document.getElementById('invoice-form-container');
            const isVisible = formContainer.style.display !== 'none';
            formContainer.style.display = isVisible ? 'none' : 'block';
            this.textContent = isVisible
                ? '<?php echo esc_js(__('Create Invoice', 'carspace-dashboard')); ?>'
                : '<?php echo esc_js(__('Cancel', 'carspace-dashboard')); ?>';

            if (!isVisible) {
                setTimeout(function() {
                    initSelect2();
                    setupCustomerTypeToggle();
                    toggleCustomerFields();
                    setupTotalCalculation();
                    toggleSuggestButtonsByType();
                    calculateTotals();
                }, 200);
            }
        });

        jQuery(document).on('change', '.vehicle-select', function() {
            const selectedOption = jQuery(this).find(':selected');
            const container = jQuery(this).closest('.product-item');

            if (selectedOption.val()) {
                container.find('.car-make').val(selectedOption.data('make') || '');
                container.find('.car-model').val(selectedOption.data('model') || '');
                container.find('.car-year').val(selectedOption.data('year') || '');
                container.find('.car-vin').val(selectedOption.data('vin') || '');

                const invoiceType = getSelectedInvoiceType();
                const amountInput = container.find('.product-amount');
                amountInput.val('');

                if (invoiceType === 'ტრანსპორტირების საფასური') {
                    if (!fillAmountFromTransportOverride(container)) {
                        requestSuggest(container);
                    }
                } else if (invoiceType === 'ავტომობილის საფასური') {
                    fillAmountFromCarPrice(container);
                }
            } else {
                container.find('.car-make').val('');
                container.find('.car-model').val('');
                container.find('.car-year').val('');
                container.find('.car-vin').val('');
            }
        });

        jQuery(document).on('click', '.suggest-transport-price', function() {
            const btn = jQuery(this);
            const container = btn.closest('.product-item');
            requestSuggest(container, btn);
        });

        let productCount = 1;
        document.getElementById('add-product')?.addEventListener('click', function() {
            const productItems = document.getElementById('product-items');
            const template = document.querySelector('.product-item').cloneNode(true);

            template.querySelectorAll('input, select').forEach(function(field) {
                if (field.name) {
                    if (field.name.includes('vehicle_id_')) {
                        field.name = 'vehicle_id_' + productCount;
                    } else if (field.name.includes('products[0]')) {
                        field.name = field.name.replace(/products\[0\]/, 'products[' + productCount + ']');
                    }
                }
            });

            template.querySelectorAll('input[type="text"], input[type="number"]').forEach(function(input) {
                input.value = '';
            });
            const fb = template.querySelector('.suggest-feedback');
            if (fb) {
                fb.className = 'form-text text-muted small suggest-feedback';
                fb.style.display = 'none';
                fb.textContent = '';
            }
            const oldSelect = template.querySelector('.vehicle-select');
            if (jQuery(oldSelect).data('select2')) {
                jQuery(oldSelect).select2('destroy');
            }
            oldSelect.selectedIndex = 0;
            template.querySelector('.remove-product').disabled = false;
            template.querySelector('.remove-product').addEventListener('click', function() {
                template.remove();
                calculateTotals();
            });

            productItems.appendChild(template);
            try {
                jQuery(template.querySelector('.vehicle-select')).select2({
                    placeholder: '<?php echo esc_js(__('Search by vehicle name or VIN', 'carspace-dashboard')); ?>',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: jQuery('#invoice-form-container'),
                    matcher: function(params, data) {
                        if (jQuery.trim(params.term) === '') return data;
                        const term = params.term.toLowerCase();
                        const text = data.text.toLowerCase();
                        const $option = jQuery(data.element);
                        const vin = $option.data('vin') ? $option.data('vin').toLowerCase() : '';
                        if (text.indexOf(term) > -1 || vin.indexOf(term) > -1) return data;
                        return null;
                    }
                });
            } catch (e) {
                console.error("Error initializing Select2 for new row:", e);
            }

            productCount++;
            toggleSuggestButtonsByType();
            calculateTotals();
        });

        document.getElementById('create-invoice-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            const submitBtn = document.getElementById('submit-button');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                '<?php echo esc_js(__('Processing...', 'carspace-dashboard')); ?>';

            const feedback = document.getElementById('form-feedback');
            feedback.className = 'mt-3 alert alert-info';
            feedback.textContent = '<?php echo esc_js(__('Processing, please wait...', 'carspace-dashboard')); ?>';

            const formData = new FormData(this);
            const products = [];
            document.querySelectorAll('.product-item').forEach(function(item) {
                const vehicleSelect = item.querySelector('.vehicle-select');
                const vehicleId = vehicleSelect ? vehicleSelect.value : '';
                if (vehicleId) {
                    const make = item.querySelector('.car-make').value;
                    const model = item.querySelector('.car-model').value;
                    const year = item.querySelector('.car-year').value;
                    const vin = item.querySelector('.car-vin').value;
                    const amount = item.querySelector('input[name^="products["][name$="][_amount_]"]').value;
                    const saleDate = item.querySelector('input[name^="products["][name$="][sale_date]"]').value;
                    products.push({ vehicle_id: vehicleId, make, model, year, vin, _amount_: amount, sale_date: saleDate });
                }
            });
            formData.append('products_json', JSON.stringify(products));
            formData.append('action', 'create_invoice');

            fetch(ajaxurl, { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
                if (data.success) {
                    feedback.className = 'mt-3 alert alert-success';
                    feedback.innerHTML = data.data + ' <a href="' + data.redirect + '"><?php echo esc_js(__('View Invoice', 'carspace-dashboard')); ?></a>';
                    resetForm();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    feedback.className = 'mt-3 alert alert-danger';
                    feedback.textContent = data.data || '<?php echo esc_js(__('Error processing invoice', 'carspace-dashboard')); ?>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
                feedback.className = 'mt-3 alert alert-danger';
                feedback.textContent = '<?php echo esc_js(__('Error processing invoice. Please try again.', 'carspace-dashboard')); ?>';
            });
        });

        function initSelect2() {
            try {
                jQuery('.vehicle-select').select2({
                    placeholder: '<?php echo esc_js(__('Search by vehicle name or VIN', 'carspace-dashboard')); ?>',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: jQuery('#invoice-form-container'),
                    matcher: function(params, data) {
                        if (jQuery.trim(params.term) === '') return data;
                        const term = params.term.toLowerCase();
                        const text = data.text.toLowerCase();
                        const $option = jQuery(data.element);
                        const vin = $option.data('vin') ? $option.data('vin').toLowerCase() : '';
                        if (text.indexOf(term) > -1 || vin.indexOf(term) > -1) return data;
                        return null;
                    }
                });
            } catch (e) {
                console.error("Error initializing Select2:", e);
            }
        }

        function resetForm() {
            const form = document.getElementById('create-invoice-form');
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('form-feedback').className = '';
            document.getElementById('form-feedback').textContent = '';
            document.getElementById('customer_type_individual').checked = true;
            toggleCustomerFields();
            jQuery('.vehicle-select').val(null).trigger('change');
            const productItems = document.getElementById('product-items');
            while (productItems.children.length > 1) {
                productItems.removeChild(productItems.lastChild);
            }
            productCount = 1;
            document.getElementById('dealer_fee').value = 0;
            document.getElementById('dealer_fee_note').value = '';
            toggleSuggestButtonsByType();
            calculateTotals();
        }

        toggleSuggestButtonsByType();
        });
        </script>
        <?php
    }
}
