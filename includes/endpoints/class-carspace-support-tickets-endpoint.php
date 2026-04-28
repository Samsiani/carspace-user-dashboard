<?php
/**
 * Dealer Support Tickets Endpoint
 * 
 * Integrates the Dealer Support Ticket system into CarSpace Dashboard
 * Allows users to view, create and manage support tickets
 * 
 * @package Carspace_Dashboard
 * @since 3.4.0
 * Last Updated: 2025-04-29 14:13:23 by Samsiani
 */

defined('ABSPATH') || exit;

// Include base endpoint class if not already included
if (!class_exists('Carspace_Endpoint')) {
    require_once CARSPACE_PATH . 'includes/endpoints/class-carspace-endpoint.php';
}

/**
 * Support Tickets endpoint class
 */
class Carspace_Endpoint_Support_Tickets extends Carspace_Endpoint {
    /**
     * Sub-endpoints
     */
    private $sub_endpoints = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = 'support-tickets';
        $this->title = __('Support Tickets', 'carspace-dashboard');
        $this->icon = 'help-circle';
        $this->position = 75; // Position after invoices (70)
        
        // Define sub-endpoints (they won't appear in menu)
        // We'll still keep view-ticket as a sub-endpoint
        $this->sub_endpoints = array(
            'view-ticket' => __('View Ticket', 'carspace-dashboard')
        );
        
        parent::__construct();
        
        // Add late initialization
        add_action('init', array($this, 'late_init'), 20);
        
        // Filter tickets to only show user's tickets
        add_filter('dst_get_tickets_query_args', array($this, 'filter_tickets_query'), 10, 1);
        
        // Override ticket URL to use our endpoint
        add_filter('dst_ticket_view_url', array($this, 'get_ticket_view_url'), 10, 2);
    }
    
    /**
     * Late initialization - runs after WordPress init
     */
    public function late_init() {
        $this->register_sub_endpoints();
    }
    
    /**
     * Register sub-endpoints
     */
    private function register_sub_endpoints() {
        // Only register if we're not in an AJAX call
        if (wp_doing_ajax()) {
            return;
        }
        
        // Register all sub-endpoints
        foreach ($this->sub_endpoints as $slug => $title) {
            add_rewrite_endpoint($slug, EP_ROOT | EP_PAGES);
            
            // Add the endpoint content action
            add_action('carspace_dashboard_endpoint_' . $slug . '_content', array($this, $slug . '_content'));
        }
        
        // Add the query vars filter
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Add query vars for sub-endpoints
     */
    public function add_query_vars($vars) {
        foreach ($this->sub_endpoints as $slug => $title) {
            $vars[] = $slug;
        }
        return $vars;
    }
    
    /**
     * Render endpoint content - Main tickets list AND form (combined on one page)
     */
    public function render_content() {
        echo '<h3>' . esc_html__('Support Tickets', 'carspace-dashboard') . '</h3>';
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<p class="alert alert-warning">' . 
                 esc_html__('You must be logged in to view support tickets.', 'carspace-dashboard') . 
                 '</p>';
            return;
        }
        
        // Track if we're showing the form initially or not
        $show_form = isset($_GET['new-ticket']) && $_GET['new-ticket'] == '1';
        
        // Create an action button to toggle form visibility
        echo '<div class="support-tickets-actions mb-4">';
        echo '<button type="button" id="toggle-ticket-form" class="btn ' . ($show_form ? 'btn-outline-secondary' : 'btn-primary') . '">';
        
        // Show appropriate icon
        if ($show_form) {
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-list me-2" style="width: 18px; height: 18px;"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>';
            echo esc_html__('Show Ticket List', 'carspace-dashboard');
        } else {
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle me-2" style="width: 18px; height: 18px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>';
            echo esc_html__('Open New Ticket', 'carspace-dashboard');
        }
        
        echo '</button>';
        echo '</div>';
        
        // Container for ticket list (initially visible if not showing form)
        echo '<div id="ticket-list-container" class="' . ($show_form ? 'd-none' : '') . '">';
        
        // Show tickets list using the plugin's shortcode
        if (shortcode_exists('dealer_ticket_list')) {
            echo do_shortcode('[dealer_ticket_list]');
        } else {
            $this->render_fallback_ticket_list();
        }
        
        echo '</div>';
        
        // Container for ticket form (initially visible if showing form)
        echo '<div id="ticket-form-container" class="' . ($show_form ? '' : 'd-none') . '">';
        
        // echo '<h4>' . esc_html__('Open New Support Ticket', 'carspace-dashboard') . '</h4>';
        
        // Show ticket form using the plugin's shortcode
        if (shortcode_exists('dealer_ticket_form')) {
            echo do_shortcode('[dealer_ticket_form]');
        } else {
            echo '<div class="alert alert-warning">';
            echo esc_html__('Ticket form is not available. Please contact the administrator.', 'carspace-dashboard');
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add JavaScript to toggle between list and form
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#toggle-ticket-form').on('click', function() {
                const $button = $(this);
                const $listContainer = $('#ticket-list-container');
                const $formContainer = $('#ticket-form-container');
                
                const isShowingList = $listContainer.is(':visible');
                
                if (isShowingList) {
                    // Switch to form view
                    $listContainer.addClass('d-none');
                    $formContainer.removeClass('d-none');
                    
                    // Update button
                    $button.removeClass('btn-primary').addClass('btn-outline-secondary');
                    $button.html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-list me-2" style="width: 18px; height: 18px;"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg> <?php echo esc_js(__('Show Ticket List', 'carspace-dashboard')); ?>');
                } else {
                    // Switch to list view
                    $formContainer.addClass('d-none');
                    $listContainer.removeClass('d-none');
                    
                    // Update button
                    $button.removeClass('btn-outline-secondary').addClass('btn-primary');
                    $button.html('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle me-2" style="width: 18px; height: 18px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg> <?php echo esc_js(__('Open New Ticket', 'carspace-dashboard')); ?>');
                }
                
                // Update URL without reloading the page
                const baseUrl = window.location.href.split('?')[0];
                const newUrl = isShowingList ? baseUrl + '?new-ticket=1' : baseUrl;
                history.pushState({}, '', newUrl);
            });
        });
        </script>
        <?php
        
        // Add custom styling for better integration with CarSpace Dashboard
        $this->add_custom_styles();
    }
    
    /**
     * Render content for view-ticket endpoint
     */
    public function view_ticket_content() {
        $ticket_id = get_query_var('view-ticket');
        
        if (!$ticket_id) {
            echo '<div class="alert alert-danger">';
            echo esc_html__('Invalid ticket ID. Please select a ticket to view.', 'carspace-dashboard');
            echo '</div>';
            
            echo '<div class="support-tickets-actions mt-3">';
            echo '<a href="' . esc_url(wc_get_account_endpoint_url('support-tickets')) . '" class="btn btn-outline-secondary">';
            echo esc_html__('Back to Tickets', 'carspace-dashboard');
            echo '</a>';
            echo '</div>';
            return;
        }
        
        echo '<h3>' . esc_html__('Ticket Details', 'carspace-dashboard') . ' #' . esc_html($ticket_id) . '</h3>';
        
        // Back button
        echo '<div class="support-tickets-actions mb-4">';
        echo '<a href="' . esc_url(wc_get_account_endpoint_url('support-tickets')) . '" class="btn btn-outline-secondary btn-sm">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left me-2" style="width: 16px; height: 16px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>';
        echo esc_html__('Back to Tickets', 'carspace-dashboard');
        echo '</a>';
        echo '</div>';
        
        // Show ticket details using the plugin's function or shortcode
        if (shortcode_exists('dealer_ticket_view')) {
            echo do_shortcode('[dealer_ticket_view]');
        } else {
            // Fallback to direct ticket display if function exists
            if (function_exists('dst_get_ticket_details')) {
                $this->render_fallback_ticket_details($ticket_id);
            } else {
                echo '<div class="alert alert-warning">';
                echo esc_html__('Ticket view is not available. Please contact the administrator.', 'carspace-dashboard');
                echo '</div>';
            }
        }
        
        // Add custom styling for better integration
        $this->add_custom_styles();
    }
    
    /**
     * Render fallback ticket list when shortcode is not available
     */
    private function render_fallback_ticket_list() {
        if (!function_exists('dst_get_user_tickets')) {
            echo '<div class="alert alert-warning">';
            echo esc_html__('Support ticket system is not properly installed. Please contact the administrator.', 'carspace-dashboard');
            echo '</div>';
            return;
        }
        
        $user_id = get_current_user_id();
        $tickets = dst_get_user_tickets($user_id);
        
        if (empty($tickets)) {
            echo '<div class="alert alert-info">';
            echo esc_html__('You have no support tickets.', 'carspace-dashboard');
            echo '</div>';
            return;
        }
        
        echo '<div class="ticket-table-wrapper">';
        echo '<table class="table table-bordered table-hover align-middle ticket-table">';
        echo '<thead class="table-light">
                <tr>
                    <th>' . esc_html__('Ticket', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('Issue', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('Product', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('Status', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('Date', 'carspace-dashboard') . '</th>
                    <th>' . esc_html__('Actions', 'carspace-dashboard') . '</th>
                </tr>
              </thead><tbody>';
        
        foreach ($tickets as $ticket) {
            // Set status class
            $status_class = 'status-' . $ticket['status'];
            
            echo '<tr>';
            echo '<td>' . esc_html($ticket['title']) . '</td>';
            echo '<td>' . esc_html($ticket['issue']) . '</td>';
            
            // Product column (SKU with link)
            echo '<td>';
            if (!empty($ticket['product']['sku'])) {
                if (!empty($ticket['product']['link'])) {
                    echo '<a href="' . esc_url($ticket['product']['link']) . '" target="_blank">';
                    echo esc_html($ticket['product']['sku']);
                    echo '</a>';
                } else {
                    echo esc_html($ticket['product']['sku']);
                }
            } else {
                echo '-';
            }
            echo '</td>';
            
            // Status column
            echo '<td><span class="ticket-status ' . esc_attr($status_class) . '">' . 
                 ucfirst(esc_html($ticket['status'])) . '</span></td>';
            
            // Date column
            echo '<td>' . esc_html($ticket['date']) . '</td>';
            
            // Actions column
            echo '<td class="ticket-actions">';
            
            // View button
            echo '<a href="' . esc_url($this->get_ticket_view_url('', $ticket['id'])) . '" class="btn btn-sm btn-outline-primary me-2">';
            echo esc_html__('View', 'carspace-dashboard');
            echo '</a>';
            
            // Close button (if not already closed)
            if ($ticket['status'] !== 'closed') {
                echo '<button type="button" class="btn btn-sm btn-outline-danger close-ticket-btn" data-ticket-id="' . esc_attr($ticket['id']) . '">';
                echo esc_html__('Close', 'carspace-dashboard');
                echo '</button>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        // Add JavaScript to handle closing tickets
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.close-ticket-btn').on('click', function() {
                if (!confirm('<?php echo esc_js(__('Are you sure you want to close this ticket?', 'carspace-dashboard')); ?>')) {
                    return;
                }
                
                var ticketId = $(this).data('ticket-id');
                var button = $(this);
                
                button.prop('disabled', true).text('<?php echo esc_js(__('Closing...', 'carspace-dashboard')); ?>');
                
                $.ajax({
                    url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'dst_close_ticket',
                        ticket_id: ticketId,
                        nonce: '<?php echo wp_create_nonce('dst_ticket_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update UI
                            var row = button.closest('tr');
                            row.find('.ticket-status')
                               .removeClass()
                               .addClass('ticket-status status-closed')
                               .text('Closed');
                            button.remove();
                        } else {
                            alert(response.data.message);
                            button.prop('disabled', false).text('<?php echo esc_js(__('Close', 'carspace-dashboard')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('An error occurred. Please try again.', 'carspace-dashboard')); ?>');
                        button.prop('disabled', false).text('<?php echo esc_js(__('Close', 'carspace-dashboard')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render fallback ticket details when shortcode is not available
     * 
     * @param int $ticket_id Ticket ID
     */
    private function render_fallback_ticket_details($ticket_id) {
        // Function implementation remains unchanged
        $ticket = dst_get_ticket_details($ticket_id);
        
        if (!$ticket) {
            echo '<div class="alert alert-danger">';
            echo esc_html__('You do not have permission to view this ticket or the ticket does not exist.', 'carspace-dashboard');
            echo '</div>';
            return;
        }
        
        echo '<div class="ticket-details card">';
        echo '<div class="card-header">';
        echo '<div class="d-flex justify-content-between align-items-center">';
        echo '<h5 class="mb-0">' . esc_html($ticket['title']) . '</h5>';
        echo '<span class="ticket-status status-' . esc_attr($ticket['status']) . '">' . ucfirst(esc_html($ticket['status'])) . '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="card-body">';
        
        // Ticket metadata
        echo '<div class="ticket-meta mb-4">';
        echo '<div class="row">';
        
        // Issue
        echo '<div class="col-md-4 mb-2">';
        echo '<strong>' . esc_html__('Issue:', 'carspace-dashboard') . '</strong> ';
        echo esc_html($ticket['issue']);
        echo '</div>';
        
        // Date
        echo '<div class="col-md-4 mb-2">';
        echo '<strong>' . esc_html__('Date:', 'carspace-dashboard') . '</strong> ';
        echo esc_html($ticket['date']);
        echo '</div>';
        
        // Product
        if (!empty($ticket['product']['sku'])) {
            echo '<div class="col-md-4 mb-2">';
            echo '<strong>' . esc_html__('Product:', 'carspace-dashboard') . '</strong> ';
            
            if (!empty($ticket['product']['link'])) {
                echo '<a href="' . esc_url($ticket['product']['link']) . '" target="_blank">';
                echo esc_html($ticket['product']['sku']);
                echo '</a>';
            } else {
                echo esc_html($ticket['product']['sku']);
            }
            
            echo '</div>';
        }
        
        echo '</div>'; // End .row
        echo '</div>'; // End .ticket-meta
        
        // Initial message
        echo '<div class="ticket-message mb-4">';
        echo '<h5>' . esc_html__('Initial Message', 'carspace-dashboard') . '</h5>';
        echo '<div class="message-content p-3 bg-light rounded">';
        echo nl2br(esc_html($ticket['initial_message']));
        echo '</div>';
        
        // Initial attachments
        if (!empty($ticket['initial_attachments'])) {
            echo '<div class="message-attachments mt-3">';
            echo '<h6>' . esc_html__('Attachments', 'carspace-dashboard') . ':</h6>';
            echo '<div class="attachment-grid">';
            
            foreach ($ticket['initial_attachments'] as $attachment) {
                echo '<div class="attachment-item">';
                echo '<a href="' . esc_url($attachment['url']) . '" target="_blank" class="attachment-preview">';
                echo '<img src="' . esc_url($attachment['thumb']) . '" alt="' . esc_attr($attachment['name']) . '">';
                echo '</a>';
                echo '<div class="attachment-name">' . esc_html($attachment['name']) . '</div>';
                echo '</div>';
            }
            
            echo '</div>'; // End .attachment-grid
            echo '</div>'; // End .message-attachments
        }
        
        echo '</div>'; // End .ticket-message
        
        // Replies
        if (!empty($ticket['replies'])) {
            echo '<h5 class="mb-3">' . esc_html__('Replies', 'carspace-dashboard') . '</h5>';
            echo '<div class="ticket-replies">';
            
            foreach ($ticket['replies'] as $reply) {
                $reply_class = $reply['is_admin'] ? 'admin-reply' : 'user-reply';
                $author = $reply['is_admin'] ? __('Admin', 'carspace-dashboard') : __('You', 'carspace-dashboard');
                
                echo '<div class="ticket-reply ' . esc_attr($reply_class) . ' mb-3">';
                echo '<div class="reply-header mb-2">';
                echo '<strong>' . esc_html($author) . '</strong>';
                echo '<span class="reply-date ms-2 text-muted">' . esc_html($reply['date']) . '</span>';
                echo '</div>';
                
                echo '<div class="reply-content p-3 rounded">';
                echo nl2br(esc_html($reply['message']));
                echo '</div>';
                
                // Reply attachments
                if (!empty($reply['attachments'])) {
                    echo '<div class="reply-attachments mt-2">';
                    echo '<h6>' . esc_html__('Attachments', 'carspace-dashboard') . ':</h6>';
                    echo '<div class="attachment-grid">';
                    
                    foreach ($reply['attachments'] as $attachment) {
                        echo '<div class="attachment-item">';
                        echo '<a href="' . esc_url($attachment['url']) . '" target="_blank" class="attachment-preview">';
                        echo '<img src="' . esc_url($attachment['thumb']) . '" alt="' . esc_attr($attachment['name']) . '">';
                        echo '</a>';
                        echo '<div class="attachment-name">' . esc_html($attachment['name']) . '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // End .attachment-grid
                    echo '</div>'; // End .reply-attachments
                }
                
                echo '</div>'; // End .ticket-reply
            }
            
            echo '</div>'; // End .ticket-replies
        }
        
        // Reply form - if ticket is not closed
        if ($ticket['can_reply']) {
            echo '<div class="reply-form mt-4">';
            echo '<h5>' . esc_html__('Add Reply', 'carspace-dashboard') . '</h5>';
            
            echo '<form id="ticket-reply-form" class="needs-validation" novalidate>';
            echo '<input type="hidden" name="ticket_id" value="' . esc_attr($ticket_id) . '">';
            echo '<div class="mb-3">';
            echo '<label for="reply-message" class="form-label">' . esc_html__('Your Reply', 'carspace-dashboard') . '</label>';
            echo '<textarea id="reply-message" name="message" class="form-control" rows="4" required></textarea>';
            echo '</div>';
            
            // File upload
            echo '<div class="mb-3">';
            echo '<label for="reply-attachments" class="form-label">' . esc_html__('Attachments (Optional)', 'carspace-dashboard') . '</label>';
            echo '<div class="file-upload-wrapper">';
            echo '<input type="file" id="reply-attachments" name="attachments[]" class="form-control" multiple accept="image/*">';
            echo '<small class="form-text text-muted">' . esc_html__('Max 10 images (JPG, PNG), 2MB each', 'carspace-dashboard') . '</small>';
            echo '</div>';
            echo '<div id="attachment-preview" class="mt-2"></div>';
            echo '</div>';
            
            echo '<div id="reply-feedback"></div>';
            echo '<button type="submit" class="btn btn-primary" id="reply-submit-btn">' . esc_html__('Submit Reply', 'carspace-dashboard') . '</button>';
            echo '</form>';
            echo '</div>'; // End .reply-form
            
            // Add JavaScript for uploading attachments and submitting reply
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Initialize file upload preview
                $('#reply-attachments').on('change', function() {
                    const files = this.files;
                    const maxFiles = 10;
                    const maxSize = 2 * 1024 * 1024; // 2MB
                    const $preview = $('#attachment-preview');
                    
                    // Clear previous preview
                    $preview.empty();
                    
                    // Check if files were selected
                    if (!files || files.length === 0) {
                        return;
                    }
                    
                    // Check number of files
                    if (files.length > maxFiles) {
                        alert('<?php echo esc_js(__('Maximum 10 files allowed', 'carspace-dashboard')); ?>');
                        this.value = '';
                        return;
                    }
                    
                    // Preview each file
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        
                        // Check file type
                        if (!file.type.match('image.*')) {
                            alert('<?php echo esc_js(__('Only image files allowed', 'carspace-dashboard')); ?>');
                            this.value = '';
                            $preview.empty();
                            return;
                        }
                        
                        // Check file size
                        if (file.size > maxSize) {
                            alert('<?php echo esc_js(__('File size exceeds 2MB limit', 'carspace-dashboard')); ?>');
                            this.value = '';
                            $preview.empty();
                            return;
                        }
                        
                        // Create preview element
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $preview.append(
                                '<div class="attachment-preview-item">' +
                                '<img src="' + e.target.result + '" alt="' + file.name + '">' +
                                '<div class="attachment-preview-name">' + file.name + '</div>' +
                                '</div>'
                            );
                        };
                        reader.readAsDataURL(file);
                    }
                });
                
                // Handle form submission
                $('#ticket-reply-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    const $form = $(this);
                    const $submitBtn = $('#reply-submit-btn');
                    const $feedback = $('#reply-feedback');
                    
                    // Validate form
                    if (!$form[0].checkValidity()) {
                        e.stopPropagation();
                        $form.addClass('was-validated');
                        return;
                    }
                    
                    // Disable submit button and show loading state
                    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo esc_js(__('Sending...', 'carspace-dashboard')); ?>');
                    
                    // Create FormData object for file uploads
                    const formData = new FormData($form[0]);
                    formData.append('action', 'dst_add_reply');
                    formData.append('nonce', '<?php echo wp_create_nonce('dst_ticket_nonce'); ?>');
                    
                    // Send AJAX request
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            if (response.success) {
                                $feedback.html('<div class="alert alert-success"><?php echo esc_js(__('Reply added successfully', 'carspace-dashboard')); ?></div>');
                                $form[0].reset();
                                $('#attachment-preview').empty();
                                
                                // Reload page after short delay to show new reply
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                $feedback.html('<div class="alert alert-danger">' + response.data.message + '</div>');
                                $submitBtn.prop('disabled', false).text('<?php echo esc_js(__('Submit Reply', 'carspace-dashboard')); ?>');
                            }
                        },
                        error: function() {
                            $feedback.html('<div class="alert alert-danger"><?php echo esc_js(__('An error occurred. Please try again.', 'carspace-dashboard')); ?></div>');
                            $submitBtn.prop('disabled', false).text('<?php echo esc_js(__('Submit Reply', 'carspace-dashboard')); ?>');
                        }
                    });
                });
            });
            </script>
            <?php
        }
        
        // Ticket actions - close button
        if ($ticket['can_close'] && $ticket['status'] !== 'closed') {
            echo '<div class="ticket-actions mt-4">';
            echo '<button type="button" class="btn btn-danger close-ticket-btn" data-ticket-id="' . esc_attr($ticket_id) . '">';
            echo esc_html__('Close Ticket', 'carspace-dashboard');
            echo '</button>';
            echo '</div>';
            
            // Add JavaScript to handle closing ticket
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('.close-ticket-btn').on('click', function() {
                    if (!confirm('<?php echo esc_js(__('Are you sure you want to close this ticket?', 'carspace-dashboard')); ?>')) {
                        return;
                    }
                    
                    var ticketId = $(this).data('ticket-id');
                    var button = $(this);
                    
                    button.prop('disabled', true).text('<?php echo esc_js(__('Closing...', 'carspace-dashboard')); ?>');
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'dst_close_ticket',
                            ticket_id: ticketId,
                            nonce: '<?php echo wp_create_nonce('dst_ticket_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Reload the page to show updated status
                                window.location.reload();
                            } else {
                                alert(response.data.message);
                                button.prop('disabled', false).text('<?php echo esc_js(__('Close Ticket', 'carspace-dashboard')); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php echo esc_js(__('An error occurred. Please try again.', 'carspace-dashboard')); ?>');
                            button.prop('disabled', false).text('<?php echo esc_js(__('Close Ticket', 'carspace-dashboard')); ?>');
                        }
                    });
                });
            });
            </script>
            <?php
        }
        
        echo '</div>'; // End .card-body
        echo '</div>'; // End .ticket-details
    }
    
    /**
     * Add custom styles for better integration with CarSpace Dashboard
     */
    private function add_custom_styles() {
        ?>
        <style>
        /* Ticket Status Colors */
        .dst-status, .ticket-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .dst-status-opened, .status-opened {
            background-color: #e7f3fd;
            color: #0073aa;
        }
        
        .dst-status-answered, .status-answered {
            background-color: #e6ffed;
            color: #22863a;
        }
        
        .dst-status-waiting, .status-waiting {
            background-color: #fff5cc;
            color: #735c0f;
        }
        
        .dst-status-closed, .status-closed {
            background-color: #f5f5f5;
            color: #666666;
        }
        
        /* Modal fixes */
        .dst-modal {
            z-index: 1060; /* Above Bootstrap modals */
        }
        
        /* Ticket replies styling */
        .ticket-reply {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .user-reply, .dst-user-reply {
            background-color: #f8f9fa;
            border-left: 3px solid #0d6efd;
        }
        
        .admin-reply, .dst-admin-reply {
            background-color: #f0f7fb;
            border-left: 3px solid #198754;
        }
        
        /* Attachment grid */
        .attachment-grid, .dst-attachment-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .attachment-item, .dst-attachment-item {
            width: 100px;
            text-align: center;
        }
        
        .attachment-preview, .dst-attachment-thumb {
            display: block;
            padding: 3px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            margin-bottom: 5px;
        }
        
        .attachment-preview img, .dst-attachment-thumb img {
            max-width: 100%;
            height: auto;
        }
        
        .attachment-name, .dst-attachment-name {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Attachment preview */
        .attachment-preview-item {
            display: inline-block;
            width: 100px;
            margin-right: 10px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .attachment-preview-item img {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        
        .attachment-preview-name {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 3px;
        }
        
        /* Bootstrap-like styles for DST elements */
        .dst-form {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,.125);
            border-radius: 0.375rem;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        
        .dst-form-row {
            margin-bottom: 1rem;
        }
        
        .dst-form-row label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .dst-submit-btn {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 0.375rem;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out;
            border: 1px solid transparent;
            cursor: pointer;
        }
        
        .dst-submit-btn:hover {
            color: #fff;
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        
        /* Table styling */
        .dst-ticket-table, .ticket-table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            vertical-align: top;
            border-color: #dee2e6;
        }
        
        /* Form/List toggle animation */
        #ticket-list-container, #ticket-form-container {
            transition: opacity 0.3s ease-in-out;
        }
        
        /* Responsive fixes */
        @media (max-width: 768px) {
            .dst-attachment-item, .attachment-item {
                width: calc(33.333% - 10px);
            }
            
            .attachment-preview-item {
                width: calc(33.333% - 10px);
            }
        }
        </style>
        <?php
    }
    
    /**
     * Filter tickets query to only show current user's tickets
     * 
     * @param array $args Query arguments
     * @return array Modified query arguments
     */
    public function filter_tickets_query($args) {
        if (is_user_logged_in()) {
            $args['author'] = get_current_user_id();
        }
        return $args;
    }
    
    /**
     * Get ticket view URL for CarSpace Dashboard
     * 
     * @param string $url Original URL
     * @param int $ticket_id Ticket ID
     * @return string Modified URL
     */
    public function get_ticket_view_url($url, $ticket_id) {
        return wc_get_account_endpoint_url('view-ticket/' . $ticket_id);
    }
}

// Initialize the endpoint - delay initialization with action
function init_carspace_support_tickets_endpoint() {
    new Carspace_Endpoint_Support_Tickets();
}
add_action('plugins_loaded', 'init_carspace_support_tickets_endpoint');