<?php
/**
 * Car Invoices Endpoint
 *
 * Main entry point for the Car Invoices functionality.
 * This file loads all required components.
 *
 * @package Carspace_Dashboard
 * @since 3.3.0
 */

defined('ABSPATH') || exit;

// Include component files
require_once CARSPACE_PATH . 'includes/endpoints/car-invoices/class-helpers.php';
require_once CARSPACE_PATH . 'includes/endpoints/car-invoices/class-render.php';
require_once CARSPACE_PATH . 'includes/endpoints/car-invoices/class-modals.php';
require_once CARSPACE_PATH . 'includes/endpoints/car-invoices/class-ajax-handlers.php';
require_once CARSPACE_PATH . 'includes/endpoints/car-invoices/class-main.php';
require_once CARSPACE_PATH . 'includes/endpoints/car-invoices/class-sync-handler.php';

// Initialize the endpoint
new Carspace_Endpoint_Car_Invoices();
