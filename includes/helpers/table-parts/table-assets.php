<?php
/**
 * Table Assets Management
 *
 * Functions for handling scripts and styles for tables
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last updated: 2025-05-12 11:22:23 by Samsiani
 * Modification (2025-08-24): Added Car Title filter logic (reads #filter_title & data-title). All existing styles, icons, and code kept identical otherwise.
 */

defined('ABSPATH') || exit;

/**
 * Enqueue all assets required for the tables
 */
function carspace_enqueue_table_assets() {
    // Include GLightbox resources
    wp_enqueue_style('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', array(), '3.2.0');
    wp_enqueue_script('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', array('jquery'), '3.2.0', true);

    // Enqueue clipboard.js
    wp_enqueue_script('clipboard-js', 'https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js', array(), '2.0.8', true);
    
    // DateRangePicker resources
    wp_enqueue_style('daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', array(), '3.1.0');
    wp_enqueue_script('moment-js', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js', array(), '2.29.4', true);
    wp_enqueue_script('daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', array('jquery', 'moment-js'), '3.1.0', true);
    
    // Bootstrap JS is already enqueued by carspace-dashboard.php (5.3.3) — no need to enqueue again

    // Enqueue the car table JS (AJAX pagination, filtering, clipboard)
    wp_enqueue_script('carspace-table-js', CARSPACE_URL . 'includes/helpers/assets/js/carspace-table.js', array('jquery', 'clipboard-js', 'daterangepicker'), CARSPACE_VERSION, true);

    // Pass translations to scripts
    wp_localize_script('jquery', 'carspaceDashboardL10n', array(
        'noResults' => __('No cars match your filter criteria.', 'carspace-dashboard'),
        'copySuccess' => __('VIN copied to clipboard:', 'carspace-dashboard'),
        'loadingImages' => __('Loading images...', 'carspace-dashboard'),
        'loadingShipping' => __('Loading shipping info...', 'carspace-dashboard'),
        'loadingInvoices' => __('Loading invoices...', 'carspace-dashboard'),
        'noPhotos' => __('No photos available', 'carspace-dashboard'),
        'currentPage' => __('Current page', 'carspace-dashboard'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'ajaxNonce' => wp_create_nonce('carspace_security_nonce')
    ));
}

/**
 * Add CSS customizations for GLightbox directly in the page
 */
add_action('wp_footer', 'carspace_add_glightbox_custom_css');

/**
 * Add custom CSS for GLightbox
 */
function carspace_add_glightbox_custom_css() {
    if (!is_account_page()) {
        return;
    }
    ?>
    <style>
    /* GLightbox customizations */
    .gslide-image img {
        max-height: 80vh;
        object-fit: contain;
    }

    .gslide-desc {
        background: rgba(0, 0, 0, 0.7);
        padding: 8px;
        border-radius: 4px;
    }

    .glightbox-clean .gslide-description {
        background: transparent;
    }

    .gslide-title {
        color: #fff;
        font-weight: 600 !important;
        font-size: 15px;
    }

    .glightbox-container .gclose {
        width: 40px;
        height: 40px;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
    }

    .glightbox-container .gnext, 
    .glightbox-container .gprev {
        background: rgba(0, 0, 0, 0.5);
        width: 50px;
        height: 50px;
        border-radius: 50%;
    }
    
    /* Make thumbnails in modal appear clickable */
    .modal .img-thumbnail {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 3px solid transparent;
    }

    .modal .img-thumbnail:hover {
        border-color: #0d6efd;
        transform: scale(1.05);
    }

    /* Invoice modal styles */
    .invoice-modal-table {
        margin-bottom: 0;
    }
    
    .invoice-modal-table th {
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    
    .invoice-modal-table td,
    .invoice-modal-table th {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .invoice-modal-table tr:hover {
        background-color: rgba(13, 110, 253, 0.04);
    }
    
    .invoice-modal-table a {
        color: #0d6efd;
        text-decoration: none;
        font-weight: 500;
    }
    
    .invoice-modal-table a:hover {
        text-decoration: underline;
    }
    
    /* Badge styling */
    .badge {
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 4px;
    }
    
    /* Image gallery categories */
    .gallery-category-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 1.5rem 0 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    /* ensure GLightbox sits above Bootstrap's backdrop */
    .glightbox-overlay,
    .glightbox-container,
    .glightbox-desc,
    .glightbox-clean .gcontainer {
        z-index: 2000 !important;
    }

    /* Make thumbnails appear clickable (duplicate intentionally kept) */
    .modal .img-thumbnail {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 3px solid transparent;
    }

    .modal .img-thumbnail:hover {
        border-color: #0d6efd;
        transform: scale(1.05);
    }

    /* Custom dropdown styling */
    .custom-dropdown {
        position: relative;
        display: inline-block;
    }

    .custom-dropdown-toggle {
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .custom-dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        min-width: 160px;
        z-index: 1000;
        display: none;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.15);
        border-radius: 0.25rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
    }

    .custom-dropdown-menu.show {
        display: block;
    }

    .custom-dropdown-item {
        display: block;
        width: 100%;
        padding: 0.25rem 1.5rem;
        clear: both;
        text-align: left;
        background-color: transparent;
        border: 0;
        color: #212529;
        font-weight: 400;
        text-decoration: none;
        cursor: pointer;
    }

    .custom-dropdown-item:hover {
        color: #16181b;
        background-color: #f8f9fa;
    }
    </style>
    <?php
}

/**
 * Add a mini dashboard at the top of the page
 */
add_action('woocommerce_before_account_content', 'carspace_add_account_mini_dashboard');

/**
 * Add mini dashboard with timestamp and stats
 */
function carspace_add_account_mini_dashboard() {
    if (!is_user_logged_in() || !is_account_page()) {
        return;
    }
    
    // Don't show on the main dashboard page
    if (is_wc_endpoint_url('dashboard')) {
        return;
    }
    
    // Current user info
    $current_user = wp_get_current_user();
    $timestamp = date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
    ?>
    <div class="account-timestamp-info mb-4">
        <div class="alert alert-light d-flex justify-content-between align-items-center">
            <span>
                <i class="fa fa-user me-2"></i> <?php echo esc_html($current_user->display_name); ?>
            </span>
            <span>
                <i class="fa fa-clock me-2"></i> <?php echo esc_html($timestamp); ?>
            </span>
        </div>
    </div>
    <?php
}

/**
 * Add inline scripts and styles for car table
 */
function carspace_add_table_scripts_and_styles() {
    // Create a nonce for AJAX requests
    $ajax_nonce = wp_create_nonce('carspace_security_nonce');
    
    // Define ajaxurl for frontend if not already defined
    ?>
    <script>
    // Define ajaxurl for frontend if not already defined
    if (typeof ajaxurl === 'undefined') {
        var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    }
    </script>

    <style>
    /* Filter section styling */
    .filter-section {
        margin-top: 1rem;
    }
    
    .filter-toggle {
        padding: 0.25rem 0.5rem;
        color: #495057;
    }
    
    .filter-toggle:hover {
        color: #0d6efd;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
    
    /* Table sorting indicators */
    .car-table th.sortable,
    .invoice-table th.sortable {
        cursor: pointer;
        position: relative;
        padding-right: 20px; /* Space for sort icon */
    }
    
    .car-table th.sortable::after,
    .invoice-table th.sortable::after {
        content: "↕";
        position: absolute;
        right: 8px;
        opacity: 0.4;
    }
    
    .car-table th.sortable.asc::after,
    .invoice-table th.sortable.asc::after {
        content: "↑";
        opacity: 0.8;
    }
    
    .car-table th.sortable.desc::after,
    .invoice-table th.sortable.desc::after {
        content: "↓";
        opacity: 0.8;
    }
    
    /* Table styling */
    .car-table, .invoice-table {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .car-table tbody tr, .invoice-table tbody tr {
        transition: all 0.2s ease-in-out;
    }
    
    /* Row hover effect */
    .car-table tbody tr.row-hover, .invoice-table tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.04);
    }
    
    /* Row status indicators */
    .car-table tbody tr.border-warning {
        border-left: 4px solid #ffc107; /* Yellow for transit */
    }
    
    .car-table tbody tr.border-success {
        border-left: 4px solid #198754; /* Green for delivered */
    }
    
    .car-table tbody tr.border-primary {
        border-left: 4px solid #0d6efd; /* Blue for pending */
    }
    
    .car-table tbody tr.border-danger {
        border-left: 4px solid #dc3545; /* Red for problem */
    }
    
    /* Car info cell styling */
    .car-info-cell {
        padding: 16px 20px;
    }
    
    /* Car title styling */
    .car-title {
        display: block;
        font-size: 14px;
        font-weight: 600 !important;
        color: #212529;
        text-decoration: none;
        margin-bottom: 5px;
        transition: color 0.2s;
    }
    
    .car-title:hover {
        color: #0d6efd;
        text-decoration: underline;
    }
    
    /* VIN container styling */
    .vin-container {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: #6c757d;
    }
    
    .vin-code {
        font-family: monospace;
    }
    
    .copy-vin {
        border: none;
        background: transparent;
        color: #6c757d;
        padding: 2px 6px;
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 4px;
    }
    
    .copy-vin:hover {
        color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    /* Photo and shipping buttons styling */
    .btn.view-images,
    .btn.shipping-info,
    .btn.invoice-info {
        transition: all 0.2s ease;
        /* Fix for shaking during hover */
        transform-origin: center center;
        backface-visibility: hidden;
    }
    
    .btn.view-images:hover,
    .btn.shipping-info:hover,
    .btn.invoice-info:hover {
        /* Using box-shadow instead of transform to prevent shaking */
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
    }
    
    /* Purchase date styling */
    .purchase-date-container {
        display: flex;
        align-items: center;
    }
    
    /* Copy success animation */
    .copy-vin.copy-success {
        animation: pulse 1s;
        color: #198754;
    }
    
    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
        }
    }
    
    /* Add spacing between rows */
    .car-table tbody tr, .invoice-table tbody tr {
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Toast notification styling */
    .vin-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        min-width: 300px;
        z-index: 9999;
    }
    
    /* DateRangePicker customizations */
    .daterangepicker {
        font-family: inherit;
    }
    
    .daterangepicker td.active, 
    .daterangepicker td.active:hover {
        background-color: #0d6efd;
    }
    
    /* Invoice status styling */
    .status-paid {
        color: #198754;
        font-weight: 500;
    }
    
    .status-unpaid {
        color: #dc3545;
        font-weight: 500;
    }
    
    /* Pagination styling */
    .pagination {
        margin-bottom: 2rem;
    }
    
    .page-link {
        color: #0d6efd;
        border-color: #dee2e6;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .page-link:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
        color: #0a58ca;
    }
    
    .page-item.ellipsis .page-link {
        pointer-events: none;
        background-color: #fff;
        color: #6c757d;
    }
    
    /* Accessibility improvements */
    .btn:focus, .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    td.car-info-cell.text-start {
        padding: 5px 2em;
    }
    
    /* Filtered rows */
    .filtered-out {
        display: none !important;
    }
    
    /* Table pagination info */
    .pagination-info {
        text-align: center;
        color: #6c757d;
        margin-top: -1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    
    /* Custom dropdown styling */
    .custom-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .custom-dropdown-toggle {
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .custom-dropdown-toggle:hover {
        background-color: #6c757d;
        color: #fff;
    }
    
    .custom-dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        min-width: 160px;
        z-index: 1050;
        display: none;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.15);
        border-radius: 0.25rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
    }
    
    .custom-dropdown-menu.show {
        display: block;
    }
    
    .custom-dropdown-item {
        display: block;
        width: 100%;
        padding: 0.25rem 1.5rem;
        clear: both;
        text-align: left;
        background-color: transparent;
        border: 0;
        color: #212529;
        font-weight: 400;
        text-decoration: none;
        cursor: pointer;
    }
    
    .custom-dropdown-item:hover {
        color: #16181b;
        background-color: #f8f9fa;
    }
    
    /* ensure GLightbox sits above Bootstrap's backdrop */
    .glightbox-overlay,
    .glightbox-container,
    .glightbox-desc,
    .glightbox-clean .gcontainer {
      z-index: 2000 !important;
    }

    a.tracking-button {
    color: #fff;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .car-info-cell {
            padding: 12px;
        }
        
        .car-title {
            font-size: 16px;
            line-height: 1.3;
        }
        
        .vin-container {
            flex-wrap: wrap;
            margin-top: 8px;
            gap: 6px;
        }
        
        .copy-vin {
            padding: 4px 8px; /* Larger touch target on mobile */
        }
        
        .car-table, .invoice-table {
            font-size: 14px;
        }
        
        .pagination .page-link {
            padding: 0.375rem 0.5rem;
        }
        
        /* Simplify pagination on mobile */
        .page-item.ellipsis {
            display: none;
        }
        
        /* Ensure dropdown is visible on mobile */
        .custom-dropdown-menu {
            right: auto;
            left: 0;
        }
    }


    /* Invoice summary styling */
.invoice-summary .card-header {
    font-size: 16px;
}

.invoice-summary .table {
    margin-bottom: 0;
}

.invoice-summary .table td {
    padding: 5px;
    border: none;
}

.invoice-summary .text-success {
    color: #198754;
}

.invoice-summary .text-danger {
    color: #dc3545;
}
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Setup translations for loading text
        var loadingInvoicesText = '<?php echo esc_js(__('Loading invoices...', 'carspace-dashboard')); ?>';
        var loadingShippingText = '<?php echo esc_js(__('Loading shipping info...', 'carspace-dashboard')); ?>';
        var loadingImagesText = '<?php echo esc_js(__('Loading images...', 'carspace-dashboard')); ?>';

        // Setup AJAX variables
        var ajax_nonce = '<?php echo esc_js($ajax_nonce); ?>';

        // Turn off Bootstrap's auto data-api (use event delegation for dynamically loaded rows)
        $(document).on('click', '.view-images, .shipping-info, .invoice-info, .dealer-fee-btn, .dealer-note-btn', function(e) {
            e.stopPropagation();
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.modal').modal('hide');
                $('.custom-dropdown-menu').removeClass('show');
            }
        });

        // Fix accessibility issue with aria-hidden
        $('.modal').on('shown.bs.modal', function() {
            $(this).removeAttr('aria-hidden');
            $(this).find('.btn-close').focus();
        });

        $('.modal').on('hidden.bs.modal', function() {
            $(this).attr('aria-hidden', 'true');
        });

        // Custom dropdown toggle (event delegation for dynamic rows)
        $(document).on('click', '.custom-dropdown-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = $(this).siblings('.custom-dropdown-menu');
            $('.custom-dropdown-menu.show').not(dropdown).removeClass('show');
            dropdown.toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.custom-dropdown').length) {
                $('.custom-dropdown-menu').removeClass('show');
            }
        });

        // SHIPPING MODAL (event delegation for dynamic rows)
        $(document).on('click', '.shipping-info', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            const modalEl = document.getElementById('shippingInfoModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            
            // STEP 1: Completely reset the modal before showing
            const $modalBody = $('#shipping-info-body');
            const $modalTitle = $('#shippingInfoModalLabel');
            $modalTitle.text('Shipping Info');  // Reset title to default
            
            // STEP 2: Show loading spinner
            $modalBody.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-3 mb-0">${loadingShippingText}</p>
                </div>
            `);
            
            // STEP 3: Show the modal with loading state ONLY
            modal.show();
            
            // STEP 4: Make AJAX call with cache busting
            const timestamp = new Date().getTime(); // Add timestamp to prevent caching
            
            // Use a direct XMLHttpRequest instead of jQuery for more control
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Replace content completely
                                $modalBody.html(response.data);
                            } else {
                                $modalBody.html('<div class="alert alert-danger">' + (response.data || 'Unknown error') + '</div>');
                            }
                        } catch(e) {
                            $modalBody.html('<div class="alert alert-danger">Error parsing response</div>');
                        }
                    } else {
                        $modalBody.html('<div class="alert alert-danger">Error loading shipping information. Please try again.</div>');
                    }
                }
            };
            xhr.send('action=get_shipping_info&product_id=' + productId + '&nonce=' + ajax_nonce + '&_=' + timestamp);
        });
        
        // IMAGE GALLERY MODAL (event delegation for dynamic rows)
        $(document).on('click', '.view-images', function(e) {
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const $body = $('#image-gallery-body');
            
            const modalEl = document.getElementById('imageGalleryModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            
            $body.html('<div class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">'+loadingImagesText+'</p></div>');
            modal.show();
            
            setTimeout(function() {
                $.post(ajaxurl, {
                    action: 'get_car_images',
                    product_id: productId,
                    nonce: ajax_nonce
                })
                .done(function(response) {
                    if (!response.success) {
                        return $body.html('<div class="alert alert-danger">'+response.data+'</div>');
                    }
                    
                    const data = response.data;
                    let html = '';
                    
                    if (data.car_title) {
                        $('#imageGalleryModalLabel').text(data.car_title + ' - Images');
                    }
                    
                    if (data.gallery_images?.length) {
                        html += '<h3 class="gallery-category-title">Gallery Images</h3><div class="row g-3">';
                        data.gallery_images.forEach(img => {
                            html += `
                                <div class="col-md-2 col-6">
                                    <a href="${img.full}" class="glightbox" data-gallery="car-gallery" data-title="${img.title}">
                                        <img src="${img.thumb}" alt="${img.title}" class="img-thumbnail">
                                    </a>
                                </div>`;
                        });
                        html += '</div>';
                    }
                    
                    if (data.port_images?.length) {
                        html += '<h3 class="gallery-category-title">Port Images</h3><div class="row g-3">';
                        data.port_images.forEach(img => {
                            html += `
                                <div class="col-md-2 col-6">
                                    <a href="${img.full}" class="glightbox" data-gallery="car-gallery" data-title="${img.title}">
                                        <img src="${img.thumb}" alt="${img.title}" class="img-thumbnail">
                                    </a>
                                </div>`;
                        });
                        html += '</div>';
                    }
                    
                    if (!html) {
                        html = '<div class="alert alert-info">No images found for this vehicle.</div>';
                    }
                    
                    $body.fadeOut(100, function() {
                        $(this).html(html).fadeIn(100, function() {
                            if (window.galleryLightbox) window.galleryLightbox.destroy();
                            window.galleryLightbox = GLightbox({
                                selector: '#image-gallery-body a.glightbox',
                                touchNavigation: true,
                                loop: true
                            });
                        });
                    });
                })
                .fail(function() {
                    $body.html('<div class="alert alert-danger">Error loading images. Try again.</div>');
                });
            }, 300);
        });

// Handle receiver modal
$(document).on('click', '.add-receiver', function(e) {
    e.preventDefault();
    
    const productId = $(this).data('product-id');
    const modalEl = document.getElementById('receiverModal');
    
    $('#mimgebi_piri').val('');
    $('#mimgebis_piradi_nomeri').val('');
    $('#receiver_product_id').val(productId);
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
});

// Handle save button click
$(document).on('click', '#saveReceiverInfo', function() {
    const $submitBtn = $(this);
    const productId = $('#receiver_product_id').val();
    const receiverName = $('#mimgebi_piri').val().trim();
    const receiverId = $('#mimgebis_piradi_nomeri').val().trim();
    const $cell = $('.add-receiver[data-product-id="' + productId + '"]').closest('td');
    
    if (!receiverName || !receiverId) {
        alert('Please fill in all required fields');
        return;
    }
    
    $submitBtn.prop('disabled', true)
             .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
    
    const formData = new FormData();
    formData.append('action', 'save_receiver_info');
    formData.append('receiver_nonce', $('#receiver_nonce').val());
    formData.append('product_id', productId);
    formData.append('mimgebi_piri', receiverName);
    formData.append('mimgebis_piradi_nomeri', receiverId);
    
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                const updatedHtml = `
                    <div class="receiver-info">
                        <strong>${receiverName}</strong>
                        <br>
                        <small class="text-muted">${receiverId}</small>
                        <br>
                        <small class="text-muted">
                            <em>Updated: ${response.data.timestamp || '2025-06-09 21:17:48'}</em>
                            <br>
                            <em>By: ${response.data.user || ''}</em>
                        </small>
                    </div>`;
                
                $cell.html(updatedHtml);
                
                const toast = new bootstrap.Toast($(`
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i> Receiver information saved successfully
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `).appendTo('#vinCopiedToast')[0]);
                
                toast.show();
                
                const modalEl = document.getElementById('receiverModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            } else {
                alert(response.data || 'Error saving receiver information.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Error:', textStatus, errorThrown);
            alert('Error saving receiver information. Please try again.');
        },
        complete: function() {
            $submitBtn.prop('disabled', false).html('Save');
        }
    });
});

// Handle modal hidden event
$(document).on('hidden.bs.modal', '#receiverModal', function() {
    $('#mimgebi_piri').val('');
    $('#mimgebis_piradi_nomeri').val('');
    $('#receiver_product_id').val('');
    const modal = bootstrap.Modal.getInstance(this);
    if (modal) {
        modal.dispose();
    }
});

// Handle modal show event
$(document).on('show.bs.modal', '#receiverModal', function() {
    $('#mimgebi_piri').val('');
    $('#mimgebis_piradi_nomeri').val('');
});
        
        // INVOICE MODAL (event delegation for dynamic rows)
        $(document).on('click', '.invoice-info', function(e) {
            e.preventDefault();
            
            const vin = $(this).data('vin');
            const $body = $('#invoice-info-body');
            const $title = $('#invoiceInfoModalLabel');
            
            const modalEl = document.getElementById('invoiceInfoModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            
            $title.text('Invoices for ' + vin);
            $body.html('<div class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">'+loadingInvoicesText+'</p></div>');
            modal.show();
            
            setTimeout(function() {
                $.post(ajaxurl, {
                    action: 'get_invoices_by_vin',
                    vin: vin,
                    nonce: ajax_nonce
                })
                .done(function(r) {
                    if (r.success) {
                        $body.fadeOut(100, function() {
                            $(this).html(r.data).fadeIn(100);
                        });
                    } else {
                        $body.html('<div class="alert alert-danger">'+(r.data || 'Unknown error')+'</div>');
                    }
                })
                .fail(function() {
                    $body.html('<div class="alert alert-danger">Error loading invoices. Please try again.</div>');
                });
            }, 300);
        });
        
        // DEALER BUTTONS (event delegation for dynamic rows)
        $(document).on('click', '.dealer-fee-btn', function(e) {
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const vin = $(this).data('vin');
            
            window.currentDealerModalData = {
                product_id: productId,
                vin: vin
            };
            
            const modalEl = document.getElementById('dealerFeeModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            
            $(this).closest('.custom-dropdown-menu').removeClass('show');
        });
        
        $(document).on('click', '.dealer-note-btn', function(e) {
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const vin = $(this).data('vin');
            
            window.currentDealerModalData = {
                product_id: productId,
                vin: vin
            };
            
            const modalEl = document.getElementById('dealerNoteModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            
            $(this).closest('.custom-dropdown-menu').removeClass('show');
        });
        
        $('#dealerFeeModal, #dealerNoteModal').on('shown.bs.modal', function() {
            const modalId = $(this).attr('id');
            const $modal = $(this);
            
            if (window.currentDealerModalData && window.currentDealerModalData.vin) {
                const vin = window.currentDealerModalData.vin;
                
                setTimeout(function() {
                    $modal.find('input[name="vin"]').val(vin);
                    
                    if (typeof acf !== 'undefined') {
                        acf.doAction('append', $modal);
                    }
                }, 100);
            }
        });
        
        // Always clear out and reset titles/bodies when closed
        $('.modal').on('hidden.bs.modal', function() {
            const modalId = this.id;
            
            if (modalId === 'dealerFeeModal' || modalId === 'dealerNoteModal') {
                return;
            }
            
            if (modalId === 'invoiceInfoModal') {
                $('#invoice-info-body').empty();
                $('#invoiceInfoModalLabel').text('Invoices');
            } else if (modalId === 'shippingInfoModal') {
                $('#shipping-info-body').empty();
            } else if (modalId === 'imageGalleryModal') {
                $('#image-gallery-body').empty();
                $('#imageGalleryModalLabel').text('Car Images');
                
                if (window.galleryLightbox) {
                    window.galleryLightbox.destroy();
                    window.galleryLightbox = null;
                }
            }
        });
    });
    </script>
    <?php
}