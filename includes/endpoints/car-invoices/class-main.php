<?php
/**
 * Car Invoices Main Class
 * 
 * Main class for Car Invoices endpoint.
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

// Include base endpoint class if not already included
if (!class_exists('Carspace_Endpoint')) {
    require_once CARSPACE_PATH . 'includes/endpoints/class-carspace-endpoint.php';
}

/**
 * Car Invoices endpoint class
 */
class Carspace_Endpoint_Car_Invoices extends Carspace_Endpoint {
    /**
     * AJAX handlers instance
     */
    private $ajax_handlers;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = 'car-invoices';
        $this->title = __('Invoices', 'carspace-dashboard');
        $this->icon = 'file-text';
        $this->position = 70;
        
        parent::__construct();
        
        // Initialize AJAX handlers
        $this->ajax_handlers = new Carspace_Car_Invoices_AJAX_Handlers();
        
        // Enqueue Select2 assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_select2_assets'));
    }
    
    /**
     * Enqueue Select2 assets
     */
    public function enqueue_select2_assets() {
        // Only load on our endpoint
        if (!is_wc_endpoint_url($this->endpoint)) {
            return;
        }
        
        // Enqueue Select2 CSS
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            array(),
            '4.1.0-rc.0'
        );
        
        // Enqueue Select2 JS
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            '4.1.0-rc.0',
            true
        );
    }
    
    /**
     * Render endpoint content
     */
    public function render_content() {
        echo '<h3>' . esc_html__('Your Invoices', 'carspace-dashboard') . '</h3>';
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            echo '<p>' . esc_html__('You must be logged in to view your invoices.', 'carspace-dashboard') . '</p>';
            return;
        }
        
        // Define ajaxurl for frontend use
        ?>
        <script>
        var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        </script>
        <?php
        
        $invoices = carspace_get_user_invoices($user_id);
        
        if (empty($invoices)) {
            echo '<p class="alert alert-info">' . esc_html__('No invoices found.', 'carspace-dashboard') . '</p>';
        } else {
            // Render our own invoice table without action buttons
            Carspace_Car_Invoices_Render::render_simple_invoice_table($invoices);
        }
        
        // Render invoice form (create only)
        Carspace_Car_Invoices_Render::render_invoice_form();
        
        // Render receipt upload modal
        Carspace_Car_Invoices_Modals::render_receipt_upload_modal();
        
        // Render dealer fee modal
        Carspace_Car_Invoices_Modals::render_dealer_fee_modal();
    }
}