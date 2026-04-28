<?php
/**
 * Car Invoices Modals
 * 
 * Handles rendering of modals for receipt upload and dealer fee.
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last Updated: 2025-05-12 15:48:50 by Samsiani
 */

defined('ABSPATH') || exit;

/**
 * Car Invoices Modals Class
 */
class Carspace_Car_Invoices_Modals {
    
    /**
     * Render receipt upload modal
     */
    public static function render_receipt_upload_modal() {
        ?>
        <div class="modal fade" id="receiptUploadModal" tabindex="-1" aria-labelledby="receiptUploadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receiptUploadModalLabel"><?php esc_html_e('Upload Receipt', 'carspace-dashboard'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Modal content will be replaced dynamically -->
                        <div class="text-center">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Reset modal state before showing
            $('#receiptUploadModal').on('show.bs.modal', function(e) {
                const modal = $(this);
                const button = $(e.relatedTarget);
                const invoiceId = button.data('invoice-id');
                
                // Set loading state
                modal.find('.modal-body').html('<div class="text-center">Loading...</div>');
                
                // Load receipt upload form
                setTimeout(function() {
                    const formHtml = `
                        <form id="receiptUploadForm" enctype="multipart/form-data">
                            <?php wp_nonce_field('receipt_upload_nonce', 'receipt_nonce'); ?>
                            <input type="hidden" id="receipt_invoice_id" name="invoice_id" value="${invoiceId}">
                            
                            <div class="mb-3">
                                <label for="receipt_file" class="form-label">
                                    <?php esc_html_e('Select Receipt Image', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="receipt_file" name="receipt_file" accept="image/*" required>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('Allowed formats: JPG, PNG, GIF. Max size: 5MB', 'carspace-dashboard'); ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <img id="imagePreview" class="img-fluid" alt="Receipt preview" style="display:none;">
                            </div>
                            
                            <div id="uploadFeedback" class="mb-3" style="display:none;"></div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                    <?php esc_html_e('Cancel', 'carspace-dashboard'); ?>
                                </button>
                                <button type="submit" class="btn btn-primary" id="uploadReceiptBtn">
                                    <?php esc_html_e('Upload Receipt', 'carspace-dashboard'); ?>
                                </button>
                            </div>
                        </form>
                    `;
                    
                    modal.find('.modal-body').html(formHtml);
                    
                    // Initialize file preview functionality
                    $('#receipt_file').on('change', function() {
                        const file = this.files[0];
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        const feedback = $('#uploadFeedback');
                        
                        // Reset feedback
                        feedback.html('').hide();
                        
                        if (file) {
                            // Check file type
                            if (!file.type.match('image.*')) {
                                feedback.html('<div class="alert alert-danger">Please select an image file (JPEG, PNG, GIF).</div>').show();
                                this.value = '';
                                $('#imagePreview').hide();
                                return;
                            }
                            
                            // Check file size
                            if (file.size > maxSize) {
                                feedback.html('<div class="alert alert-danger">File is too large. Maximum size is 5MB.</div>').show();
                                this.value = '';
                                $('#imagePreview').hide();
                                return;
                            }
                            
                            // Show preview
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $('#imagePreview').attr('src', e.target.result).show();
                            };
                            reader.readAsDataURL(file);
                        } else {
                            $('#imagePreview').hide();
                        }
                    });
                    
                    // Handle form submission
                    $('#receiptUploadForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        const form = $(this);
                        const file = $('#receipt_file')[0].files[0];
                        const invoiceId = $('#receipt_invoice_id').val();
                        const feedback = $('#uploadFeedback');
                        
                        // Validate file
                        if (!file) {
                            feedback.html('<div class="alert alert-danger">Please select a file to upload.</div>').show();
                            return;
                        }
                        
                        // Create FormData
                        const formData = new FormData();
                        formData.append('action', 'upload_receipt');
                        formData.append('receipt_file', file);
                        formData.append('invoice_id', invoiceId);
                        formData.append('receipt_nonce', $('#receipt_nonce').val());
                        
                        // Show loading state
                        const submitBtn = $('#uploadReceiptBtn');
                        const originalText = submitBtn.html();
                        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...').prop('disabled', true);
                        
                        // Send AJAX request
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                // Always reset button state first
                                submitBtn.html(originalText).prop('disabled', false);
                                
                                if (response.success) {
                                    // Show success message
                                    feedback.html('<div class="alert alert-success">' + response.data.message + '</div>').show();
                                    
                                    // Update the button in the table
                                    const receiptCell = $('#receipt-cell-' + invoiceId);
                                    const viewButton = '<a href="' + response.data.receipt_url + '" target="_blank" class="btn btn-sm btn-outline-success view-receipt" title="View Receipt">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-icon lucide-eye">' +
                                        '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>' +
                                        '<circle cx="12" cy="12" r="3"/>' +
                                        '</svg>' +
                                        '<span class="ms-1">View Receipt</span>' +
                                        '</a>';
                                    receiptCell.html(viewButton);
                                    
                                    // Close modal after short delay
                                    setTimeout(function() {
                                        $('#receiptUploadModal').modal('hide');
                                    }, 1500);
                                } else {
                                    feedback.html('<div class="alert alert-danger">' + response.data + '</div>').show();
                                }
                            },
                            error: function() {
                                submitBtn.html(originalText).prop('disabled', false);
                                feedback.html('<div class="alert alert-danger">Error uploading file. Please try again.</div>').show();
                            }
                        });
                    });
                }, 200); // Small delay to ensure modal is fully rendered
            });
            
            // Clean up when modal is hidden
            $('#receiptUploadModal').on('hidden.bs.modal', function() {
                // Remove modal backdrop and reset body class
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render dealer fee modal
     */
    public static function render_dealer_fee_modal() {
        ?>
        <div class="modal fade" id="dealerFeeModal" tabindex="-1" aria-labelledby="dealerFeeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dealerFeeModalLabel"><?php esc_html_e('Add Dealer Fee', 'carspace-dashboard'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Modal content will be replaced dynamically -->
                        <div class="text-center">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Reset modal state before showing
            $('#dealerFeeModal').on('show.bs.modal', function(e) {
                const modal = $(this);
                const button = $(e.relatedTarget);
                const invoiceId = button.data('invoice-id');
                const dealerFee = button.data('dealer-fee') || '';
                const dealerFeeNote = button.data('dealer-fee-note') || '';
                
                // Set loading state
                modal.find('.modal-body').html('<div class="text-center">Loading...</div>');
                
                // Update modal title based on if adding or editing
                const modalTitle = dealerFee > 0 ? 
                    '<?php echo esc_js(__('Edit Dealer Fee', 'carspace-dashboard')); ?>' : 
                    '<?php echo esc_js(__('Add Dealer Fee', 'carspace-dashboard')); ?>';
                modal.find('.modal-title').text(modalTitle);
                
                console.log('Modal opened with note:', dealerFeeNote);
                
                // Load form content
                setTimeout(function() {
                    const formHtml = `
                        <form id="dealerFeeForm">
                            <?php wp_nonce_field('dealer_fee_nonce_action', 'dealer_fee_nonce'); ?>
                            <input type="hidden" id="dealer_fee_invoice_id" name="invoice_id" value="${invoiceId}">
                            
                            <div class="mb-3">
                                <label for="dealer_fee_amount" class="form-label">
                                    <?php esc_html_e('Dealer Fee Amount ($)', 'carspace-dashboard'); ?> <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="dealer_fee_amount" name="dealer_fee" step="0.01" min="0" value="${dealerFee}" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dealer_fee_note" class="form-label">
                                    <?php esc_html_e('Note (Optional)', 'carspace-dashboard'); ?>
                                </label>
                                <textarea class="form-control" id="dealer_fee_note" name="dealer_fee_note" rows="3">${dealerFeeNote}</textarea>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('Add any additional information about this dealer fee.', 'carspace-dashboard'); ?>
                                </small>
                            </div>
                            
                            <div id="dealerFeeFeedback" class="mb-3" style="display:none;"></div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                    <?php esc_html_e('Cancel', 'carspace-dashboard'); ?>
                                </button>
                                <button type="submit" class="btn btn-primary" id="saveDealerFeeBtn">
                                    <?php esc_html_e('Save Dealer Fee', 'carspace-dashboard'); ?>
                                </button>
                            </div>
                        </form>
                    `;
                    
                    modal.find('.modal-body').html(formHtml);
                    
                    // Handle dealer fee form submission
                    $('#dealerFeeForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        const form = $(this);
                        const invoiceId = $('#dealer_fee_invoice_id').val();
                        const dealerFee = $('#dealer_fee_amount').val();
                        const dealerFeeNote = $('#dealer_fee_note').val();
                        const feedback = $('#dealerFeeFeedback');
                        
                        // Validate dealer fee
                        if (dealerFee === '') {
                            feedback.html('<div class="alert alert-danger">Please enter a dealer fee amount.</div>').show();
                            return;
                        }
                        
                        // Create form data
                        const formData = new FormData();
                        formData.append('action', 'update_dealer_fee');
                        formData.append('invoice_id', invoiceId);
                        formData.append('dealer_fee', dealerFee);
                        formData.append('dealer_fee_note', dealerFeeNote);
                        formData.append('dealer_fee_nonce', $('#dealer_fee_nonce').val());
                        
                        console.log('Sending dealer fee note:', dealerFeeNote);
                        
                        // Show loading state
                        const submitBtn = $('#saveDealerFeeBtn');
                        const originalText = submitBtn.html();
                        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...').prop('disabled', true);
                        
                        // Send AJAX request
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                // Always reset button state first
                                submitBtn.html(originalText).prop('disabled', false);
                                
                                if (response.success) {
                                    // Show success message
                                    feedback.html('<div class="alert alert-success">' + response.data.message + '</div>').show();
                                    
                                    // Update dealer fee cell and amount cell
                                    const dealerFeeCell = $('#dealer-fee-cell-' + invoiceId);
                                    const amountCell = $('#amount-cell-' + invoiceId);
                                    
                                    console.log('Received response note:', response.data.dealer_fee_note);
                                    console.log('Received escaped note:', response.data.dealer_fee_note_escaped);
                                    
                                    // Store all data including the note in the button for later retrieval
                                    dealerFeeCell.html(
                                        '<span class="dealer-fee-amount">' + response.data.dealer_fee_formatted + '</span>' +
                                        '<button class="btn btn-sm btn-outline-secondary ms-2 edit-dealer-fee" ' +
                                        'data-bs-toggle="modal" ' +
                                        'data-bs-target="#dealerFeeModal" ' +
                                        'data-invoice-id="' + invoiceId + '" ' +
                                        'data-dealer-fee="' + response.data.dealer_fee + '" ' +
                                        'data-dealer-fee-note="' + response.data.dealer_fee_note_escaped + '">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                                        '<path d="M12 20h9"/>' +
                                        '<path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>' +
                                        '</svg>' +
                                        '</button>'
                                    );
                                    
                                    amountCell.html(response.data.total_formatted);
                                    
                                    // Close modal after short delay
                                    setTimeout(function() {
                                        $('#dealerFeeModal').modal('hide');
                                    }, 1500);
                                } else {
                                    feedback.html('<div class="alert alert-danger">' + response.data + '</div>').show();
                                }
                            },
                            error: function() {
                                submitBtn.html(originalText).prop('disabled', false);
                                feedback.html('<div class="alert alert-danger">Error updating dealer fee. Please try again.</div>').show();
                            }
                        });
                    });
                }, 200); // Small delay to ensure modal is fully rendered
            });
            
            // Clean up when modal is hidden
            $('#dealerFeeModal').on('hidden.bs.modal', function() {
                // Remove modal backdrop and reset body class
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            });
        });
        </script>
        <?php
    }
}