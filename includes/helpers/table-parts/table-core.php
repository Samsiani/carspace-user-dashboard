<?php
/**
 * Core Table Rendering
 *
 * Main functions for rendering car tables
 *
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last updated: 2025-08-24 (mod by request)
 *
 * NOTE (2025-08-24 Adjustment):
 *  - Removed Car Price override by WooCommerce Regular Price (now ONLY uses attribute 'Price ($)' as before).
 *  - Removed use of WooCommerce Sale Price entirely.
 *  - Transport Price column now overrides with WooCommerce Regular Price (if set); if not set, falls back to original dynamic tier/location logic.
 */

defined('ABSPATH') || exit;

/**
 * Render car rows for a set of car IDs.
 * Handles all batch preloading internally.
 *
 * @param array $car_ids Array of product IDs to render
 * @return string HTML of <tr> elements
 */
function carspace_render_car_rows($car_ids, $pre_loaded_wc_map = null) {
    if (empty($car_ids)) {
        return '';
    }

    if ($pre_loaded_wc_map !== null) {
        // Use pre-loaded WC products — skip batch preloading
        $wc_products_map = $pre_loaded_wc_map;
    } else {
        // Batch-prime meta and term caches
        update_meta_cache('post', $car_ids);
        update_object_term_cache($car_ids, 'product');

        // Batch-load WC products
        $wc_products_map = array();
        $wc_products = wc_get_products(array(
            'include' => $car_ids,
            'limit'   => count($car_ids),
            'return'  => 'objects',
        ));
        foreach ($wc_products as $wc_p) {
            $wc_products_map[$wc_p->get_id()] = $wc_p;
        }
    }

    $all_vins = array();
    foreach ($wc_products_map as $wc_p) {
        $sku = $wc_p->get_sku();
        if (!empty($sku)) {
            $all_vins[] = $sku;
        }
    }

    // Preload VIN maps
    if (!empty($all_vins)) {
        carspace_preload_vin_buyer_map($all_vins);
        carspace_preload_vin_invoices_map($all_vins);
    }

    // Cache user tier
    $user_tier_key = '';
    if (class_exists('TPC_User_Tier')) {
        $user_tier_obj = new TPC_User_Tier();
        $user_tier_key = $user_tier_obj->get_user_tier(get_current_user_id());
    }

    // Batch preload transport prices
    $transport_price_cache = array();
    if (!empty($wc_products_map)) {
        $location_ids = array();
        foreach ($wc_products_map as $wc_p) {
            $loc_raw = $wc_p->get_attribute('Location ID');
            $loc_id  = intval(preg_replace('/\D+/', '', (string)$loc_raw));
            if ($loc_id > 0) {
                $location_ids[] = $loc_id;
            }
        }
        if (!empty($location_ids)) {
            global $wpdb;
            $tpc_table = $wpdb->prefix . 'tpc_prices';
            $unique_ids = array_unique(array_map('intval', $location_ids));
            $placeholders = implode(',', array_fill(0, count($unique_ids), '%d'));
            $price_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tpc_table} WHERE id IN ({$placeholders})", $unique_ids));
            foreach ($price_rows as $pr) {
                $transport_price_cache[intval($pr->id)] = $pr;
            }
        }
    }

    ob_start();
    $row_count = 0;

    foreach ($car_ids as $product_id) {
        $product = isset($wc_products_map[$product_id]) ? $wc_products_map[$product_id] : null;
        if (!$product) {
            continue;
        }
        $row_class = (++$row_count % 2 == 0) ? 'even' : 'odd';

        $status = get_post_meta($product_id, 'car_status', true);
        $status_class = carspace_get_status_class($status);

        $container_number = $product->get_attribute('container-number');
        $lot_number       = $product->get_attribute('lot-number');

        // Purchase date (post meta, YYYY-MM-DD format)
        $purchase_date = get_post_meta($product_id, '_purchase_date', true);
        $purchase_date_formatted = $purchase_date;

        $year  = $product->get_attribute('year');
        $make  = $product->get_attribute('make');
        $model = $product->get_attribute('model');
        $vin   = $product->get_sku();
        $car_title = trim($year . ' ' . $make . ' ' . $model);

        // Buyer
        $buyer_name = carspace_get_buyer_name_from_invoices($vin);

        // Invoices
        $all_invoices   = carspace_get_all_invoices_by_vin($vin);
        $has_invoices   = !empty($all_invoices);
        $invoices_count = count($all_invoices);

        // Display date formatted as d/m/Y if available
        $purchase_date_display = '';
        if (!empty($purchase_date)) {
            $ts = strtotime($purchase_date);
            if ($ts) {
                $purchase_date_display = date_i18n('d/m/Y', $ts);
            }
        }

        echo '<tr class="' . esc_attr($row_class . ' ' . $status_class) . '"
                data-title="' . esc_attr(strtolower($car_title)) . '"
                data-vin="' . esc_attr(strtolower($vin)) . '"
                data-lot="' . esc_attr(strtolower($lot_number)) . '"
                data-car-price="' . esc_attr($product->get_attribute('Price ($)')) . '"
                data-transport-price=""
                data-container="' . esc_attr(strtolower($container_number)) . '"
                data-purchase-date="' . esc_attr($purchase_date_formatted) . '"
                data-purchase-date-display="' . esc_attr($purchase_date_display) . '">';

        // Car info cell
        echo '<td data-label="' . esc_attr( __('Car', 'carspace-dashboard') ) . '" class="car-info-cell text-start d-flex align-items-center" style="min-width:340px;">';
        $thumbnail_id = get_post_thumbnail_id($product_id);
        if ($thumbnail_id) {
            $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
            $full_image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            echo '<a href="javascript:void(0);" class="car-thumb-link me-3" data-full-img="' . esc_url($full_image_url) . '">';
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($car_title) . '" class="rounded car-featured-thumb" loading="lazy" style="width: 50px; max-width:50px; height: auto; object-fit: cover;">';
            echo '</a>';
        } else {
            echo '<div class="car-thumb-placeholder rounded bg-light d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 30px; height: 30px; color: #adb5bd;">
                        <path d="M19 17h2c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"></path>
                    </svg>
                  </div>';
        }
        echo '<div class="car-info" style="min-width:0;flex:1 1 0%;word-break:break-word;">';
        echo '<a href="' . esc_url(get_permalink($product_id)) . '" class="car-title" style="font-weight:600;">' . esc_html($car_title) . '</a>';
        echo '<div class="vin-container">';
        echo '<span class="vin-code">' . esc_html($vin) . '</span>';
        echo '<button class="btn btn-sm copy-vin" data-clipboard-text="' . esc_attr($vin) . '" aria-label="' . esc_attr( __('Copy VIN', 'carspace-dashboard') ) . '"><i class="fa fa-copy"></i></button>';
        echo '</div></div></td>';

        // Photos cell
        carspace_render_photos_cell($product_id, $product);

        /**
         * CAR PRICE COLUMN
         * Reverted: ONLY use attribute 'Price ($)' (no WooCommerce regular price override).
         */
        $car_price_attr = $product->get_attribute('Price ($)');
        echo '<td data-label="' . esc_attr( __('Car Price', 'carspace-dashboard') ) . '" class="text-center">';
        if (!empty($car_price_attr)) {
            echo '<div class="car-price-cell">';
            echo '<strong class="d-block price-amount">' . esc_html('$ ' . number_format((float)$car_price_attr, 2)) . '</strong>';
            echo '</div>';
        } else {
            echo '<span class="text-muted">' . esc_html( __('—', 'carspace-dashboard') ) . '</span>';
        }
        echo '</td>';

        /**
         * TRANSPORT PRICE COLUMN
         * NEW: Use WooCommerce Regular Price as override.
         * If no regular price set, fallback to original dynamic tier/location logic.
         * (Sale price ignored entirely.)
         */
        $wc_regular_price = $product->get_regular_price();
        $auction_city     = $product->get_attribute('Auction City');
        $loading_port     = $product->get_attribute('Loading Port');
        $location_id_raw  = $product->get_attribute('Location ID');
        $location_id      = intval(preg_replace('/\D+/', '', (string)$location_id_raw));

        echo '<td data-label="' . esc_attr( __('Transport Price', 'carspace-dashboard') ) . '" class="text-center transport-price-column"
                  data-auction-city="' . esc_attr($auction_city) . '"
                  data-loading-port="' . esc_attr($loading_port) . '"
                  data-location-id="' . esc_attr($location_id) . '"
                  data-product-id="' . esc_attr($product_id) . '">';

        if ($wc_regular_price !== '') {
            echo '<div class="transport-price-cell">';
            echo '<strong class="d-block price-amount">' . wc_price( (float)$wc_regular_price ) . '</strong>';
            echo '</div>';
        } else {
            // Dynamic fallback (user tier pre-computed before loop)
            $transport_price = null;

            // Location ID based (using batch-preloaded cache)
            if ($location_id > 0 && isset($transport_price_cache[$location_id])) {
                $price_row = $transport_price_cache[$location_id];
                if (!empty($user_tier_key) && isset($price_row->$user_tier_key) && $price_row->$user_tier_key !== '' && $price_row->$user_tier_key !== null) {
                    $transport_price = (float)$price_row->$user_tier_key;
                } else {
                    $transport_price = (float)$price_row->base_price;
                }
            }

            // Route fallback
            if ($transport_price === null) {
                $db = new TPC_Database();
                $price_data = $db->get_price_by_route($auction_city, $loading_port);
                if ($price_data) {
                    if (!empty($user_tier_key) && isset($price_data->$user_tier_key)) {
                        $transport_price = (float)$price_data->$user_tier_key;
                    } else {
                        $transport_price = (float)$price_data->base_price;
                    }
                }
            }

            if ($transport_price !== null) {
                echo '<div class="transport-price-cell">';
                echo '<strong class="d-block price-amount">' . esc_html(number_format($transport_price, 2) . ' $') . '</strong>';
                echo '</div>';
            } else {
                echo '<span class="text-muted">' . esc_html( __('—', 'carspace-dashboard') ) . '</span>';
            }
        }
        echo '</td>';

        // Buyer
        echo '<td data-label="' . esc_attr( __('Buyer', 'carspace-dashboard') ) . '" class="text-center">' . esc_html( $buyer_name ?: __('—', 'carspace-dashboard') ) . '</td>';

        // Receiver
        echo '<td data-label="' . esc_attr( __('Receiver', 'carspace-dashboard') ) . '" class="text-center">';
        $receiver_name = get_post_meta($product_id, '_receiver_name', true);
        $receiver_id   = get_post_meta($product_id, '_receiver_personal_id', true);
        if (!empty($receiver_name)) {
            echo '<div class="receiver-info">';
            echo '<strong>' . esc_html($receiver_name) . '</strong>';
            if (!empty($receiver_id)) {
                echo '<br><small class="text-muted">' . esc_html($receiver_id) . '</small>';
            }
            echo '</div>';
        } else {
            echo '<button type="button" class="btn btn-outline-secondary btn-sm add-receiver"
                    data-product-id="' . esc_attr($product_id) . '"
                    aria-label="' . esc_attr( __('Add receiver information', 'carspace-dashboard') ) . '">
                    <i class="fas fa-plus"></i>
                  </button>';
        }
        echo '</td>';

        // Invoices
        echo '<td data-label="' . esc_attr( __('Invoices', 'carspace-dashboard') ) . '" class="text-center">';
        if ($has_invoices) {
            echo '<button class="btn btn-outline-primary btn-sm position-relative invoice-info"
                    data-product-id="' . esc_attr($product_id) . '"
                    data-vin="' . esc_attr($vin) . '"
                    aria-label="' . esc_attr( __('View invoices', 'carspace-dashboard') ) . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                    <path d="M10 9H8"/>
                    <path d="M16 13H8"/>
                    <path d="M16 17H8"/>
                </svg>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">'
                . esc_html($invoices_count) .
                '</span>
              </button>';
        } else {
            echo '<button type="button" class="btn btn-outline-secondary btn-sm" disabled
                    aria-label="' . esc_attr( __('No invoices available', 'carspace-dashboard') ) . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                    <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4"/>
                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                    <path d="m8 12.5-5 5"/>
                    <path d="m3 12.5 5 5"/>
                </svg>
              </button>';
        }
        echo '</td>';

        // Shipping
        echo '<td data-label="' . esc_attr( __('Shipping', 'carspace-dashboard') ) . '" class="text-center">
                <button class="btn btn-outline-danger btn-sm shipping-info"
                        data-product-id="' . esc_attr($product_id) . '"
                        aria-label="' . esc_attr( __('View shipping information', 'carspace-dashboard') ) . '">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                        <path d="M2 21c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1 .6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"></path>
                        <path d="M19.38 20A11.6 11.6 0 0 0 21 14l-9-4-9 4a11.6 11.6 0 0 0 1.62 6"></path>
                        <path d="M12 10v5"></path>
                        <path d="m12 10 8-2.5"></path>
                        <path d="m12 10-8-2.5"></path>
                        <path d="M18.5 6.5 12 10l-6.5-3.5"></path>
                        <path d="m18 2-6 3-6-3"></path>
                    </svg>
                </button>
              </td>';

        // Status detection
        $car_status_label = __('Unknown', 'carspace-dashboard');
        $car_status_class = 'bg-secondary';

        $has_booking  = carspace_has_booking_number($product);
        $is_loaded    = carspace_is_car_loaded($product);
        $is_delivered = carspace_is_car_delivered($product_id);

        if ($is_delivered) {
            $car_status_label = __('Car Delivered', 'carspace-dashboard');
            $car_status_class = 'bg-success';
        } elseif ($has_booking && !$is_loaded) {
            $car_status_label = __('Booking Container', 'carspace-dashboard');
            $car_status_class = 'bg-warning';
        } elseif ($is_loaded) {
            $car_status_label = __('Loaded Container', 'carspace-dashboard');
            $car_status_class = 'bg-info';
        } elseif (!$has_booking && !$is_loaded) {
            $has_featured = (bool) $product->get_image_id();
            $gallery_ids  = $product->get_gallery_image_ids();
            $has_gallery  = !empty($gallery_ids);

            if ($has_featured) {
                $car_status_label = __('Car Not Loaded', 'carspace-dashboard');
                $car_status_class = 'bg-danger';
            } elseif (!$has_featured && !$has_gallery) {
                $car_status_label = __('Car Not Delivered', 'carspace-dashboard');
                $car_status_class = 'bg-dark';
            } else {
                $car_status_label = __('Car Not Loaded', 'carspace-dashboard');
                $car_status_class = 'bg-danger';
            }
        } else {
            $car_status_label = __('Car Not Delivered', 'carspace-dashboard');
            $car_status_class = 'bg-dark';
        }

        echo '<td data-label="' . esc_attr( __('Status', 'carspace-dashboard') ) . '" class="text-center">
            <span class="badge ' . esc_attr($car_status_class) . '">' . esc_html($car_status_label) . '</span>
        </td>';

        echo '</tr>';
    }

    return ob_get_clean();
}

/**
 * Render car table with the given list of cars.
 * Only renders page 1; subsequent pages loaded via AJAX.
 *
 * @param array $cars Array of car posts
 * @return void
 */
function carspace_render_car_table($cars) {
    // User info
    $current_user   = wp_get_current_user();
    $current_login  = $current_user->user_login;
    $current_utc    = gmdate('Y-m-d H:i:s');
    echo '<div id="dashboard-user-info" class="dashboard-user-info" style="margin-bottom: 16px;">';
    echo '<span><strong>' . esc_html__('Current User\'s Login', 'carspace-dashboard') . ':</strong> ' . esc_html($current_login) . '</span> ';
    echo '<span style="margin-left:1.5em;"><strong>' . esc_html__('Current Date and Time (UTC - YYYY-MM-DD HH:MM:SS formatted)', 'carspace-dashboard') . ':</strong> ' . esc_html($current_utc) . '</span>';
    echo '</div>';

    if (empty($cars)) {
        echo '<p class="alert alert-info">' . esc_html( __('No cars found.', 'carspace-dashboard') ) . '</p>';
        return;
    }

    // Enqueue all required scripts and styles
    carspace_enqueue_table_assets();

    // Render filter bar
    carspace_render_filter_bar();

    $items_per_page = 15;
    $car_ids = wp_list_pluck($cars, 'ID');

    // Batch-prime meta cache for ALL cars (single query, needed to build filterable index)
    update_meta_cache('post', $car_ids);
    update_object_term_cache($car_ids, 'product');

    // Batch-load ALL WC products to build filterable index
    $wc_products_map = array();
    $filterable_data = array();
    $valid_car_ids = array();

    if (!empty($car_ids)) {
        $wc_products = wc_get_products(array(
            'include' => $car_ids,
            'limit'   => count($car_ids),
            'return'  => 'objects',
        ));
        foreach ($wc_products as $wc_p) {
            $id = $wc_p->get_id();
            $wc_products_map[$id] = $wc_p;
            $valid_car_ids[] = $id;

            $year  = $wc_p->get_attribute('year');
            $make  = $wc_p->get_attribute('make');
            $model = $wc_p->get_attribute('model');
            $vin   = $wc_p->get_sku();
            $lot   = $wc_p->get_attribute('lot-number');
            $container = $wc_p->get_attribute('container-number');

            // Purchase date from post meta (YYYY-MM-DD format)
            $purchase_date = get_post_meta($id, '_purchase_date', true);
            $pd_formatted = $purchase_date;

            $filterable_data[$id] = array(
                'title'         => strtolower(trim("$year $make $model")),
                'vin'           => strtolower($vin),
                'lot'           => strtolower($lot),
                'container'     => strtolower($container),
                'purchase_date' => $pd_formatted,
            );
        }
    }

    // Store in transient for AJAX pagination (1 hour TTL)
    $cache_key = 'carspace_tbl_' . get_current_user_id() . '_' . md5(implode(',', $valid_car_ids));
    set_transient($cache_key, array(
        'car_ids'    => $valid_car_ids,
        'filterable' => $filterable_data,
    ), HOUR_IN_SECONDS);

    $total_items = count($valid_car_ids);
    $total_pages = max(1, ceil($total_items / $items_per_page));

    // Get page 1 car IDs
    $page1_ids = array_slice($valid_car_ids, 0, $items_per_page);

    // Table starts here
    echo '<div class="car-table-wrapper table-responsive">';
    echo '<table id="carTable" class="car-table table table-bordered table-hover align-middle"
            data-default-sort="purchase-date" data-default-direction="desc"
            data-items-per-page="' . esc_attr($items_per_page) . '"
            data-total-pages="' . esc_attr($total_pages) . '"
            data-total-items="' . esc_attr($total_items) . '"
            data-cache-key="' . esc_attr($cache_key) . '">';
    echo '<thead class="table-light">
            <tr>
                <th class="sortable" data-sort="make">' . esc_html( __('Car', 'carspace-dashboard') ) . '</th>
                <th>' . esc_html( __('Photos', 'carspace-dashboard') ) . '</th>
                <th class="text-center">' . esc_html( __('Car Price', 'carspace-dashboard') ) . '</th>
                <th class="text-center">' . esc_html( __('Transport Price', 'carspace-dashboard') ) . '</th>
                <th>' . esc_html( __('Buyer', 'carspace-dashboard') ) . '</th>
                <th>' . esc_html( __('Receiver', 'carspace-dashboard') ) . '</th>
                <th>' . esc_html( __('Invoices', 'carspace-dashboard') ) . '</th>
                <th>' . esc_html( __('Shipping', 'carspace-dashboard') ) . '</th>
                <th>' . esc_html( __('Status', 'carspace-dashboard') ) . '</th>
            </tr>
          </thead><tbody id="carTableBody">';

    // Render only page 1 rows (pass pre-loaded WC map to avoid duplicate batch loading)
    $page1_wc_map = array_intersect_key($wc_products_map, array_flip($page1_ids));
    echo carspace_render_car_rows($page1_ids, $page1_wc_map);

    echo '</tbody></table></div>';

    // Loading overlay for AJAX pagination
    echo '<div id="carTableLoading" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">' . esc_html__('Loading...', 'carspace-dashboard') . '</span>
            </div>
          </div>';

    // No results message
    echo '<div id="noResults" class="alert alert-info mt-3 d-none">' . esc_html( __('No cars match your filter criteria.', 'carspace-dashboard') ) . '</div>';

    // Pagination controls
    if ($total_pages > 1) {
        carspace_render_client_pagination($total_pages);
    }

    // Modals
    carspace_render_shipping_modal();
    carspace_render_gallery_modal();
    carspace_render_invoice_modal();
    carspace_render_dealer_fee_modal();
    carspace_render_dealer_note_modal();
    carspace_render_receiver_modal();
    carspace_render_toast_notification();

    // Lightbox markup
    echo '<div id="carImageLightbox" class="car-lightbox" style="display:none;">
        <button type="button" class="car-lightbox-close" aria-label="' . esc_attr( __('Close', 'carspace-dashboard') ) . '">&times;</button>
        <div class="car-lightbox-content">
            <img src="" id="carLightboxImage" alt="" style="max-width:100%;max-height:90vh;object-fit:contain;">
        </div>
    </div>';

    carspace_add_table_scripts_and_styles();
    ?>
    <style>
    .car-featured-thumb {
        width: 50px !important;
        max-width: 50px !important;
        height: auto !important;
        object-fit: cover;
    }
    .car-info-cell {
        min-width: 340px !important;
    }
    .car-lightbox {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0; top: 0;
        width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.9);
        justify-content: center;
        align-items: center;
    }
    .car-lightbox-content {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        max-width: 90vw;
        max-height: 90vh;
    }
    .car-lightbox-close {
        position: absolute;
        right: 20px; top: 20px;
        width: 40px; height: 40px;
        color: #fff;
        font-size: 30px;
        font-weight: bold;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.8;
        transition: opacity 0.2s;
        z-index: 10000;
    }
    .car-lightbox-close:hover { opacity: 1; }
    body.car-lightbox-open { overflow: hidden; }
    #carTableLoading:not(.d-none) + #noResults { display: none !important; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.car-thumb-link', function(e) {
            e.preventDefault();
            var imgUrl = $(this).data('full-img');
            $('#carLightboxImage').attr('src', imgUrl);
            $('#carImageLightbox').fadeIn(200);
            $('body').addClass('car-lightbox-open');
        });
        $('.car-lightbox-close, #carImageLightbox').on('click', function(e) {
            if (e.target === this) {
                $('#carImageLightbox').fadeOut(200);
                $('body').removeClass('car-lightbox-open');
            }
        });
        $(document).on('keyup', function(e) {
            if (e.key === "Escape") {
                $('#carImageLightbox').fadeOut(200);
                $('body').removeClass('car-lightbox-open');
            }
        });
    });
    </script>
    <?php
}
?>
