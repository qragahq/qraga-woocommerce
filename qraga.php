<?php

/**
 * Plugin Name: Qraga
 * Description: WooCommerce extension for Qraga integration.
 * Version: 0.1.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: qraga
 * Domain Path: /languages
 * Requires at least: 5.9
 * Tested up to: 6.1
 * Requires PHP: 7.3
 *
 */

defined('ABSPATH') || exit;

// Define plugin file constant
if ( ! defined( 'QRAGA_PLUGIN_FILE' ) ) {
	define( 'QRAGA_PLUGIN_FILE', __FILE__ );
}

/**
 * Check if WooCommerce is active.
 */
function qraga_is_woocommerce_active() {
    return class_exists( 'WooCommerce' );
}

/**
 * Display admin notice if WooCommerce is not active.
 */
function qraga_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e( 'Qraga requires WooCommerce to be installed and activated. Please install or activate WooCommerce.', 'qraga' ); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin core components only if WooCommerce is active.
 */
function qraga_initialize_plugin() {
    // Check for WooCommerce
    if ( ! qraga_is_woocommerce_active() ) {
        add_action( 'admin_notices', 'qraga_woocommerce_missing_notice' );
        return; // Stop initialization if WooCommerce is missing
    }

    // Load text domain
    load_plugin_textdomain( 'qraga', false, dirname( plugin_basename( QRAGA_PLUGIN_FILE ) ) . '/languages' );

    // Proceed with loading the main plugin class
    require_once dirname( QRAGA_PLUGIN_FILE ) . '/includes/class-qraga-plugin.php'; // We should probably rename the class file too
    Qraga_Plugin::instance(); // Ensure class name matches the file/class being loaded
}

// Hook into plugins_loaded to initialize
add_action( 'plugins_loaded', 'qraga_initialize_plugin' );
