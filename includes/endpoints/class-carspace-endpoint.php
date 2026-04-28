<?php
/**
 * Carspace Endpoint Base Class
 * 
 * Abstract class for all dashboard endpoints
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

/**
 * Abstract base class for all dashboard endpoints
 */
abstract class Carspace_Endpoint {
    /**
     * Endpoint slug
     * 
     * @var string
     */
    protected $endpoint = '';
    
    /**
     * Endpoint title
     * 
     * @var string
     */
    protected $title = '';
    
    /**
     * Endpoint icon
     * 
     * @var string
     */
    protected $icon = '';
    
    /**
     * Menu position
     * 
     * @var int
     */
    protected $position = 50;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Skip registration if extending class didn't set endpoint
        if (empty($this->endpoint)) {
            return;
        }
        
        // Register the endpoint
        add_action('init', array($this, 'register_endpoint'));
        
        // Add to query vars
        add_filter('query_vars', array($this, 'add_query_var'));
        
        // Add to WooCommerce menu
        add_filter('woocommerce_account_menu_items', array($this, 'add_menu_item'));
        
        // Register endpoint content
        add_action('woocommerce_account_' . $this->endpoint . '_endpoint', array($this, 'render_content'));
    }
    
    /**
     * Register the endpoint with WordPress
     */
    public function register_endpoint() {
        add_rewrite_endpoint($this->endpoint, EP_ROOT | EP_PAGES);
    }
    
    /**
     * Add endpoint to query vars
     */
    public function add_query_var($vars) {
        $vars[] = $this->endpoint;
        return $vars;
    }
    
    /**
     * Add to the WooCommerce menu items
     */
    public function add_menu_item($items) {
        // Preserve the logout item which should be last
        $logout = isset($items['customer-logout']) ? $items['customer-logout'] : '';
        
        if ($logout) {
            unset($items['customer-logout']);
        }
        
        // Add our endpoint
        $items[$this->endpoint] = $this->title;
        
        // Add back the logout item
        if ($logout) {
            $items['customer-logout'] = $logout;
        }
        
        return $items;
    }
    
    /**
     * Get filtered cars based on the endpoint's specific filter
     *
     * @param int $user_id User ID
     * @return array Filtered cars array
     */
    protected function get_filtered_cars($user_id) {
        if (!$user_id) {
            return array();
        }
        
        $cars = carspace_get_user_assigned_cars($user_id);
        
        if (empty($cars)) {
            return array();
        }
        
        return array_filter($cars, array($this, 'filter_car'));
    }
    
    /**
     * Filter function for cars - should be overridden by child classes
     * 
     * @param object $car Car post object
     * @return bool Whether to include this car
     */
    protected function filter_car($car) {
        // By default, include all cars. Override in child class.
        return true;
    }
    
    /**
     * Render empty state message when no cars found
     * 
     * @return void
     */
    protected function render_empty_state() {
        echo '<p class="alert alert-info">' . esc_html__('No cars found.', 'carspace-dashboard') . '</p>';
    }
    
    /**
     * Render endpoint content
     * Must be implemented by child classes
     * 
     * @return void
     */
    abstract public function render_content();
}