<?php
/**
 * Loaded Container Endpoint
 * 
 * Displays cars that have been loaded into containers for the current user
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
 * Loaded Container endpoint class
 */
class Carspace_Endpoint_Loaded_Container extends Carspace_Endpoint {
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = 'loaded-container';
        $this->title = __('Loaded Containers', 'carspace-dashboard');
        $this->icon = 'container';
        $this->position = 50;
        
        parent::__construct();
    }
    
    /**
     * Filter function for cars
     * Include only cars that have container numbers (are loaded) but not delivered
     * 
     * @param object $car Car post object
     * @return bool Whether to include this car
     */
    protected function filter_car($car) {
        $product = wc_get_product($car->ID);
        if (!$product) {
            return false;
        }
        
        // Check if car is loaded AND not delivered
        return carspace_is_car_loaded($product) && !carspace_is_car_delivered($car->ID);
    }
    
    /**
     * Render empty state message when no cars found
     */
    protected function render_empty_state() {
        echo '<p class="alert alert-info">' . esc_html__('No loaded containers found.', 'carspace-dashboard') . '</p>';
    }
    
    /**
     * Render endpoint content
     */
    public function render_content() {
        echo '<h3>' . esc_html__('Cars in Loaded Containers', 'carspace-dashboard') . '</h3>';
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            echo '<p>' . esc_html__('You must be logged in to view your cars.', 'carspace-dashboard') . '</p>';
            return;
        }
        
        $filtered_cars = $this->get_filtered_cars($user_id);
        
        if (empty($filtered_cars)) {
            $this->render_empty_state();
            return;
        }
        
        carspace_render_car_table($filtered_cars);
        
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
new Carspace_Endpoint_Loaded_Container();