<?php
/**
 * Assigned Cars Endpoint
 * 
 * Displays all cars assigned to the current user
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
 * Assigned Cars endpoint class
 */
class Carspace_Endpoint_Assigned_Cars extends Carspace_Endpoint {
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = 'assigned-cars';
        $this->title = __('My Cars', 'carspace-dashboard');
        $this->icon = 'car';
        $this->position = 10; // First item in menu
        
        parent::__construct();
    }
    
    /**
     * Render endpoint content
     */
    public function render_content() {
        echo '<h3>' . esc_html__('My Cars', 'carspace-dashboard') . '</h3>';
        
        // Get current user's cars - no filter needed for this endpoint
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            echo '<p>' . esc_html__('You must be logged in to view your cars.', 'carspace-dashboard') . '</p>';
            return;
        }
        
        $cars = carspace_get_user_assigned_cars($user_id);
        
        if (empty($cars)) {
            $this->render_empty_state();
            return;
        }
        
        // Display cars table
        carspace_render_car_table($cars);
        
        // Add current date and user info in a small footer
        echo '<div class="text-muted small mt-4 text-end">';
        printf(
            /* translators: %1$s: current date, %2$s: username */
            esc_html__('Last updated: %1$s ', 'carspace-dashboard'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')),
            wp_get_current_user()->user_login
        );
        echo '</div>';
    }
}

// Initialize the endpoint
new Carspace_Endpoint_Assigned_Cars();