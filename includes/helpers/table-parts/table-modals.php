<?php
/**
 * Table Modal Templates
 *
 * Functions for rendering modal dialogs
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last updated: 2026-03-06 by Samsiani
 */

defined('ABSPATH') || exit;

/**
 * Render dealer fee modal
 */
function carspace_render_dealer_fee_modal() {
    ?>
    <div class="modal fade" id="dealerFeeModal" tabindex="-1" aria-labelledby="dealerFeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dealerFeeModalLabel"><?php esc_html_e('Add Dealer Fee', 'carspace-dashboard'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dealer-fee-body">
                    <?php echo do_shortcode('[dealer_fee_form]'); ?>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <?php 
                        printf(
                            /* translators: %1$s: current date, %2$s: username */
                            esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
                            esc_html(current_time('Y-m-d H:i:s')),
                            esc_html(wp_get_current_user()->user_login)
                        );
                        ?>
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'carspace-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render dealer note modal
 */
function carspace_render_dealer_note_modal() {
    ?>
    <div class="modal fade" id="dealerNoteModal" tabindex="-1" aria-labelledby="dealerNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dealerNoteModalLabel"><?php esc_html_e('Add Dealer Note', 'carspace-dashboard'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dealer-note-body">
                    <?php echo do_shortcode('[dealer_note_form]'); ?>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <?php 
                        printf(
                            /* translators: %1$s: current date, %2$s: username */
                            esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
                            esc_html(current_time('Y-m-d H:i:s')),
                            esc_html(wp_get_current_user()->user_login)
                        );
                        ?>
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'carspace-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render a single reusable invoice modal
 */
function carspace_render_invoice_modal() {
    ?>
    <div class="modal fade" id="invoiceInfoModal" tabindex="-1" 
         aria-labelledby="invoiceInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceInfoModalLabel">
                        <?php echo esc_html__('Invoices', 'carspace-dashboard'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoice-info-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden"><?php esc_html_e('Loading...', 'carspace-dashboard'); ?></span>
                        </div>
                        <p class="mt-2 text-muted"><?php esc_html_e('Loading invoices...', 'carspace-dashboard'); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <?php 
                        printf(
                            /* translators: %1$s: current date, %2$s: username */
                            esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
                            esc_html(current_time('Y-m-d H:i:s')),
                            esc_html(wp_get_current_user()->user_login)
                        );
                        ?>
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo esc_html__('Close', 'carspace-dashboard'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the shipping info modal
 */
function carspace_render_shipping_modal() {
    ?>
    <div class="modal fade" id="shippingInfoModal" tabindex="-1" aria-labelledby="shippingInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shippingInfoModalLabel"><?php esc_html_e('Shipping Info', 'carspace-dashboard'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="shipping-info-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden"><?php esc_html_e('Loading...', 'carspace-dashboard'); ?></span>
                        </div>
                        <p class="mt-2 text-muted"><?php esc_html_e('Loading shipping info...', 'carspace-dashboard'); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <?php 
                        printf(
                            /* translators: %1$s: current date, %2$s: username */
                            esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
                            esc_html(current_time('Y-m-d H:i:s')),
                            esc_html(wp_get_current_user()->user_login)
                        );
                        ?>
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'carspace-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the image gallery modal
 */
function carspace_render_gallery_modal() {
    ?>
    <div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-labelledby="imageGalleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageGalleryModalLabel"><?php esc_html_e('Car Images', 'carspace-dashboard'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="image-gallery-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden"><?php esc_html_e('Loading...', 'carspace-dashboard'); ?></span>
                        </div>
                        <p class="mt-2 text-muted"><?php esc_html_e('Loading images...', 'carspace-dashboard'); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <?php 
                        printf(
                            /* translators: %1$s: current date, %2$s: username */
                            esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
                            esc_html(current_time('Y-m-d H:i:s')),
                            esc_html(wp_get_current_user()->user_login)
                        );
                        ?>
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'carspace-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render toast notification for VIN copied
 */
function carspace_render_toast_notification() {
    ?>
    <div class="toast vin-toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" id="vinCopiedToast" data-bs-delay="2000">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-check-circle me-2"></i> 
                <?php esc_html_e('VIN copied to clipboard:', 'carspace-dashboard'); ?>
                <strong id="vinToastText"></strong>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <?php
}

/**
 * Render photos cell for a product
 * 
 * @param int $product_id Product ID
 */
function carspace_render_photos_cell($product_id, $product = null) {
    if (!$product) {
        $product = wc_get_product($product_id);
    }
    $gallery_ids = $product->get_gallery_image_ids();
    $woo_gallery_count = count($gallery_ids);
    
    // Get port images count from custom table
    $port_images_count = count(Carspace_Port_Images::get($product_id));
    
    $total_images = $woo_gallery_count + $port_images_count;
    
    echo '<td data-label="Photos" class="text-center">';
    
    if ($total_images > 0) {
        echo '<button type="button" class="btn btn-outline-primary btn-sm position-relative view-images" 
                data-product-id="' . esc_attr($product_id) . '" 
                aria-label="' . esc_attr__('View Images', 'carspace-dashboard') . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="camera-icon" style="width: 20px; height: 20px;">
                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                    <circle cx="12" cy="13" r="3"></circle>
                </svg>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    ' . esc_html($total_images) . '
                </span>
              </button>';
    } else {
        echo '<button type="button" class="btn btn-outline-secondary btn-sm" disabled 
                aria-label="' . esc_attr__('No photos available', 'carspace-dashboard') . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="camera-off-icon" style="width: 20px; height: 20px;">
                    <line x1="2" x2="22" y1="2" y2="22"></line>
                    <path d="M7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16"></path>
                    <path d="M9.5 4h5L17 7h3a2 2 0 0 1 2 2v7.5"></path>
                    <path d="M14.121 15.121A3 3 0 1 1 9.88 10.88"></path>
                </svg>
              </button>';
    }
    
    echo '</td>';
}

function carspace_render_receiver_modal() {
    ?>
    <div class="modal fade" id="receiverModal" tabindex="-1" aria-labelledby="receiverModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiverModalLabel"><?php esc_html_e('Add Receiver Information', 'carspace-dashboard'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="receiverForm">
                        <?php 
                        // Add nonce field
                        wp_nonce_field('carspace_receiver_nonce', 'receiver_nonce');
                        ?>
                        <input type="hidden" id="receiver_product_id" name="product_id" value="">
                        
                        <div class="mb-3">
                            <label for="mimgebi_piri" class="form-label"><?php esc_html_e('Receiver Name', 'carspace-dashboard'); ?></label>
                            <input type="text" class="form-control" id="mimgebi_piri" name="mimgebi_piri" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mimgebis_piradi_nomeri" class="form-label"><?php esc_html_e('Receiver Personal ID', 'carspace-dashboard'); ?></label>
                            <input type="text" class="form-control" id="mimgebis_piradi_nomeri" name="mimgebis_piradi_nomeri" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        <?php 
                        printf(
                            /* translators: %1$s: current date, %2$s: username */
                            esc_html__('Updated: %1$s by %2$s', 'carspace-dashboard'),
                            esc_html(current_time('Y-m-d H:i:s')),
                            esc_html(wp_get_current_user()->user_login)
                        );
                        ?>
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'carspace-dashboard'); ?></button>
                    <button type="button" class="btn btn-primary" id="saveReceiverInfo"><?php esc_html_e('Save', 'carspace-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}