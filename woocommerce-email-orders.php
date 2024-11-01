<?php
/*
Plugin Name: WooCommerce Email Orders
Description: Sends out a daily digest of orders placed through WooCommerce
Version: 1.2.2
Author: Apex Digital
Author URI: https://www.apexdigital.co.nz/
*/

// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}

// Load the main controller
define('WOOCOMMERCE_EMAIL_ORDERS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('WOOCOMMERCE_EMAIL_ORDERS_PLUGIN_URL', plugin_dir_url( __FILE__ ));
require_once( WOOCOMMERCE_EMAIL_ORDERS_PLUGIN_PATH . 'controllers/cronController.php' );
if(is_admin()) {
    $cronController = new \WooCommerceEmailOrders\Controllers\cronController();
    $cronController->init();
}

// Setup the cron task when the plugin is activated
register_activation_hook(__FILE__, 'woocommerce_email_order_activation');
function woocommerce_email_order_activation() {
    if (! wp_next_scheduled ( 'woocommerce_email_order_cron' )) {
        wp_schedule_event( strtotime(date('Y-m-d', strtotime('+1 day', current_time('timestamp'))) . ' 01:00:00'), 'daily', 'woocommerce_email_order_cron');
    }
}

// Remove the cron task when the plugin has been deactivated
register_deactivation_hook(__FILE__, 'woocommerce_email_order_deactivation');
function woocommerce_email_order_deactivation() {
    wp_clear_scheduled_hook( 'woocommerce_email_order_cron' );
}

// Hook to run when the cron schedule is triggered
add_action('woocommerce_email_order_cron', function() {
    // Only proceed if WooCommerce is available
    if(function_exists('wc')) {
        $cronController = new \WooCommerceEmailOrders\Controllers\cronController();
        $cronController->cron();
    }
});
