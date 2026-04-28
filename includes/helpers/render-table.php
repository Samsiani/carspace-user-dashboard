<?php
/**
 * Table Rendering Functions
 * 
 * Functions for rendering car and invoice tables
 * 
 * @package Carspace_Dashboard
 * @since 3.3.0
 * Last updated: 2025-05-12 10:18:46 by Samsiani
 */

defined('ABSPATH') || exit;

// Define the table parts directory
define('CARSPACE_TABLE_PARTS_DIR', plugin_dir_path(__FILE__) . 'table-parts/');

// Load component files
require_once CARSPACE_TABLE_PARTS_DIR . 'table-data.php';
require_once CARSPACE_TABLE_PARTS_DIR . 'table-filters.php';
require_once CARSPACE_TABLE_PARTS_DIR . 'table-modals.php';
require_once CARSPACE_TABLE_PARTS_DIR . 'table-assets.php';
require_once CARSPACE_TABLE_PARTS_DIR . 'table-core.php';
require_once CARSPACE_TABLE_PARTS_DIR . 'table-ajax.php';