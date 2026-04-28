<?php
/**
 * Menu Order
 * 
 * Handles the ordering of menu items in the WooCommerce My Account area
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

/**
 * Custom menu order for WooCommerce account endpoints
 */
add_filter('woocommerce_account_menu_items', 'carspace_custom_account_menu_order', 99);

/**
 * Reorder the WooCommerce account menu items
 * 
 * @param array $items Original menu items
 * @return array Modified menu items
 */
function carspace_custom_account_menu_order($items) {
    // Extract "Account details" and "Logout" to add at the end
    $account_details = isset($items['edit-account']) ? array('edit-account' => $items['edit-account']) : array();
    $logout = isset($items['customer-logout']) ? array('customer-logout' => $items['customer-logout']) : array();
    
    // Remove them from the original array
    unset($items['edit-account'], $items['customer-logout']);
    
    // Define the custom order for endpoints
    $custom_order = array(
        'assigned-cars'         => __('My Cars', 'carspace-dashboard'),
        'car-delivered'         => __('Delivered Cars', 'carspace-dashboard'),
        'car-not-delivered'     => __('Not Delivered', 'carspace-dashboard'),
        'booking-container'     => __('Booking Numbers', 'carspace-dashboard'),
        'loaded-container'      => __('Loaded Containers', 'carspace-dashboard'),
        'car-not-loaded'        => __('Not Loaded Cars', 'carspace-dashboard'),
        'car-invoices'          => __('Invoices', 'carspace-dashboard'),
        'support-tickets'       => __('Support Tickets', 'carspace-dashboard'),

    );
    
    /**
     * Filter the custom order of menu items
     * 
     * @param array $custom_order Custom ordered menu items
     */
    $custom_order = apply_filters('carspace_account_menu_order', $custom_order);
    
    // Return the final menu with account details and logout at the end
    return array_merge($custom_order, $account_details, $logout);
}

/**
 * Remove default WooCommerce menu icons if needed
 */
add_action('wp_head', 'carspace_remove_account_menu_icons');

/**
 * Remove icons from WooCommerce account menu items via CSS
 */
function carspace_remove_account_menu_icons() {
    if (!is_account_page()) {
        return;
    }
    
    // Current timestamp for user
    $timestamp = current_time('timestamp');
    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    
    ?>
    <style>
    /* Remove default WooCommerce menu icons */
    .woocommerce-MyAccount-navigation ul li a::before {
        display: none !important;
    }
    
    /* Add proper padding to menu items without icons */
    .woocommerce-MyAccount-navigation ul li a {
        padding-left: 1rem !important;
    }
    
    /* Custom styling for menu items */
    .woocommerce-MyAccount-navigation ul li {
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
    }
    
    .woocommerce-MyAccount-navigation ul li:hover,
    .woocommerce-MyAccount-navigation ul li.is-active {
        border-left-color: #2b7dfa;
    }
    
    /* Add footer with timestamp in navigation */
    .woocommerce-MyAccount-navigation::after {
        display: block;
        padding: 10px;
        margin-top: 20px;
        font-size: 0.75rem;
        color: #999;
        border-top: 1px solid #eee;
        text-align: center;
    }
    </style>
    <?php
}

/**
 * Add mini dashboard above account navigation
 */
add_action('woocommerce_before_account_navigation', 'carspace_add_account_navigation_header');

/**
 * Add user info and time to account navigation
 */
function carspace_add_account_navigation_header() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $current_user = wp_get_current_user();
    $unread_count = carspace_get_unread_notification_count(get_current_user_id());
    
    ?>
  
    <?php
}