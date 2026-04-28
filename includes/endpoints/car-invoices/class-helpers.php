<?php
/**
 * Car Invoices Helper Functions
 * 
 * Utility functions for the Car Invoices endpoint.
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

/**
 * Car Invoices Helper Class
 */
class Carspace_Car_Invoices_Helpers {
    
    /**
     * Get vehicle attribute
     * 
     * @param int $product_id The product ID
     * @param string $attribute_name The attribute name
     * @return string The attribute value
     */
    public static function get_vehicle_attribute($product_id, $attribute_name) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }
        
        // Try to get the attribute
        $attribute_value = '';
        
        // Try standard attribute format
        $taxonomy = 'pa_' . sanitize_title($attribute_name);
        if ($product->get_attribute($taxonomy)) {
            $attribute_value = $product->get_attribute($taxonomy);
        } 
        // Try custom attribute format
        else if ($product->get_attribute($attribute_name)) {
            $attribute_value = $product->get_attribute($attribute_name);
        }
        
        return $attribute_value;
    }
    
    /**
     * Render simple pagination
     * 
     * @param int $total_pages Total number of pages
     * @param int $current_page Current page
     */
    public static function render_simple_pagination($total_pages, $current_page) {
        echo '<nav aria-label="' . esc_attr__('Page navigation', 'carspace-dashboard') . '">';
        echo '<ul class="pagination justify-content-center mt-4">';
        
        // Previous button
        $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
        echo '<li class="page-item ' . $prev_disabled . '">';
        echo '<a class="page-link" href="?invoice-page=' . ($current_page - 1) . '" ' . 
             ($prev_disabled ? 'aria-disabled="true" tabindex="-1"' : '') . '>';
        echo '<span aria-hidden="true">&laquo;</span> ' . esc_html__('Previous', 'carspace-dashboard');
        echo '</a>';
        echo '</li>';
        
        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="?invoice-page=1">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = ($i == $current_page) ? 'active' : '';
            echo '<li class="page-item ' . $active . '">'; 
            echo '<a class="page-link" href="?invoice-page=' . $i . '">' . $i . '</a>';
            echo '</li>';
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="?invoice-page=' . $total_pages . '">' . $total_pages . '</a></li>';
        }
        
        // Next button
        $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        echo '<li class="page-item ' . $next_disabled . '">';
        echo '<a class="page-link" href="?invoice-page=' . ($current_page + 1) . '" ' . 
             ($next_disabled ? 'aria-disabled="true" tabindex="-1"' : '') . '>';
        echo esc_html__('Next', 'carspace-dashboard') . ' <span aria-hidden="true">&raquo;</span>';
        echo '</a>';
        echo '</li>';
        
        echo '</ul>';
        echo '</nav>';
    }
}