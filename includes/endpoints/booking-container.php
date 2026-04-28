<?php
/**
 * Booking Container Endpoint
 * 
 * Displays cars that have booking numbers but are not yet loaded into containers
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
 * Booking Container (Not Loaded) endpoint class
 */
class Carspace_Endpoint_Booking_Container extends Carspace_Endpoint {
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = 'booking-container';
        $this->title = __('Booked but Not Loaded', 'carspace-dashboard');
        $this->icon = 'package-check';
        $this->position = 40;
        
        parent::__construct();
    }
    
    /**
     * Filter function for cars
     * Include only cars that have a booking number but are not loaded into containers
     * 
     * @param object $car Car post object
     * @return bool Whether to include this car
     */
    protected function filter_car($car) {
        $product = wc_get_product($car->ID);
        if (!$product) {
            return false;
        }

        // Include only if car has a booking number AND is NOT loaded
        return carspace_has_booking_number($product) && !carspace_is_car_loaded($product);
    }
    
    /**
     * Render empty state message when no cars found
     */
    protected function render_empty_state() {
        echo '<p class="alert alert-info">' . esc_html__('No booked but not loaded cars found.', 'carspace-dashboard') . '</p>';
    }
    
    /**
     * Render endpoint content
     */
    public function render_content() {
        echo '<h3>' . esc_html__('Booked but Not Loaded Cars', 'carspace-dashboard') . '</h3>';
        
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
            esc_html__('Last updated: %1$s ', 'carspace-dashboard'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp'))
        );
        echo '</div>';
    }
}

// Initialize the endpoint
new Carspace_Endpoint_Booking_Container();
