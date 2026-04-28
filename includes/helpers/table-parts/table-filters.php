<?php
/**
 * Table Filtering Functions
 *
 * Functions for rendering filter interface and pagination
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last updated: 2025-05-12 10:18:46 by Samsiani
 * Modification (2025-08-24): Added "Car Title" filter field (search by full/partial title) without changing any existing styles or functionality.
 */

defined('ABSPATH') || exit;

/**
 * Render filter bar for car table
 */
function carspace_render_filter_bar() {
    echo '<div class="filter-section mb-4">';
    echo '<div class="card">';
    echo '<div class="card-header bg-light d-flex justify-content-between align-items-center">';
    echo '<h5 class="mb-0">' . esc_html__('Filters', 'carspace-dashboard') . '</h5>';
    echo '<button class="btn btn-sm btn-link filter-toggle" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 25px; height: 25px;">';
    echo '<path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path>';
    echo '</svg>';
    echo '</button>';
    echo '</div>';
    
    echo '<div id="filterCollapse" class="collapse show">';
    echo '<div class="card-body">';
    echo '<div class="row g-3">';
    
    // NEW: Car Title Filter (Year Make Model) - added at start, uses same styling/classes
    echo '<div class="col-md-3">';
    echo '<label for="filter_title" class="form-label">' . esc_html__('Car Title', 'carspace-dashboard') . '</label>';
    echo '<input type="text" class="form-control filter-input" id="filter_title" data-filter-column="title" placeholder="' . esc_attr__('Enter part of car title (year, make, model)', 'carspace-dashboard') . '">';
    echo '</div>';
    
    // VIN filter
    echo '<div class="col-md-3">';
    echo '<label for="filter_vin" class="form-label">' . esc_html__('VIN', 'carspace-dashboard') . '</label>';
    echo '<input type="text" class="form-control filter-input" id="filter_vin" data-filter-column="vin" placeholder="' . esc_attr__('Enter VIN', 'carspace-dashboard') . '">';
    echo '</div>';
    
    // LOT filter
    echo '<div class="col-md-3">';
    echo '<label for="filter_lot" class="form-label">' . esc_html__('LOT', 'carspace-dashboard') . '</label>';
    echo '<input type="text" class="form-control filter-input" id="filter_lot" data-filter-column="lot" placeholder="' . esc_attr__('Enter LOT number', 'carspace-dashboard') . '">';
    echo '</div>';
    
    // Container Number filter
    echo '<div class="col-md-3">';
    echo '<label for="filter_container" class="form-label">' . esc_html__('Container', 'carspace-dashboard') . '</label>';
    echo '<input type="text" class="form-control filter-input" id="filter_container" data-filter-column="container" placeholder="' . esc_attr__('Enter container number', 'carspace-dashboard') . '">';
    echo '</div>';
    
    // Date range filter
    echo '<div class="col-md-3">';
    echo '<label for="filter_date_range" class="form-label">' . esc_html__('Purchase Date', 'carspace-dashboard') . '</label>';
    echo '<input type="text" class="form-control" id="filter_date_range" placeholder="' . esc_attr__('From - To', 'carspace-dashboard') . '">';
    echo '</div>';
    
    // Reset button
    echo '<div class="col-12 mt-3">';
    echo '<button type="button" class="btn btn-outline-secondary" id="reset-filters">' . esc_html__('Reset Filters', 'carspace-dashboard') . '</button>';
    echo '</div>';
    
    echo '</div>'; // row
    echo '</div>'; // card-body
    echo '</div>'; // collapse
    echo '</div>'; // card
    echo '</div>'; // filter-section
}

/**
 * Render client-side pagination controls
 * 
 * @param int $total_pages Total number of pages
 */
function carspace_render_client_pagination($total_pages) {
    $pagination_range = 2; // Number of page links to show before and after current page
    
    echo '<nav aria-label="' . esc_attr__('Page navigation', 'carspace-dashboard') . '">';
    echo '<ul class="pagination justify-content-center mt-4" id="clientPagination" data-current-page="1">';
    
    // Previous button
    echo '<li class="page-item disabled">';
    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true" aria-label="' . esc_attr__('Previous', 'carspace-dashboard') . '" data-page="prev">';
    echo '<span aria-hidden="true">&laquo;</span>';
    echo '</a>';
    echo '</li>';
    
    // First page - always visible and active initially
    echo '<li class="page-item active" aria-current="page"><a class="page-link" href="#" data-page="1">1</a></li>';
    
    // First set of pages
    for ($i = 2; $i <= min($total_pages, $pagination_range + 1); $i++) {
        echo '<li class="page-item"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
    }
    
    // Ellipsis if more pages
    if ($total_pages > $pagination_range + 1) {
        echo '<li class="page-item ellipsis"><a class="page-link" href="#">…</a></li>';
    }
    
    // Last page
    if ($total_pages > 1) {
        echo '<li class="page-item"><a class="page-link" href="#" data-page="' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    
    // Next button
    $next_disabled = $total_pages <= 1 ? 'disabled' : '';
    $next_aria_disabled = $total_pages <= 1 ? 'true' : 'false';
    echo '<li class="page-item ' . $next_disabled . '">';
    echo '<a class="page-link" href="#" aria-disabled="' . $next_aria_disabled . '" aria-label="' . esc_attr__('Next', 'carspace-dashboard') . '" data-page="next">';
    echo '<span aria-hidden="true">&raquo;</span>';
    echo '</a>';
    echo '</li>';
    
    echo '</ul>';
    echo '</nav>';
}