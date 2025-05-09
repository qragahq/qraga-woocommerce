<?php

/**
 * Plugin Name: Qraga WooCommerce Integration
 * Description: Integrates Qraga services with WooCommerce for product synchronization and widgets.
 * Version: 0.2.0
 * Author: Qraga Team
 * Author URI: https://qraga.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: qraga
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.8
 *
 */

defined('ABSPATH') || exit;

// Define QRAGA_PLUGIN_FILE for use in other files
if ( ! defined( 'QRAGA_PLUGIN_FILE' ) ) {
	define( 'QRAGA_PLUGIN_FILE', __FILE__ );
}

// Declare compatibility with High-Performance Order Storage (HPOS)
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', QRAGA_PLUGIN_FILE, true );
		}
	}
);

/**
 * Load the main plugin class after all plugins are loaded
 * to ensure WooCommerce and other dependencies are available.
 */
function qraga_initialize_plugin() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'qraga_missing_woocommerce_notice' );
		return;
	}

	load_plugin_textdomain( 'qraga', false, dirname( plugin_basename( QRAGA_PLUGIN_FILE ) ) . '/languages' );

	require_once dirname( QRAGA_PLUGIN_FILE ) . '/includes/class-qraga-plugin.php';
	Qraga_Plugin::instance();
}

/**
 * Display an admin notice if WooCommerce is not active.
 */
function qraga_missing_woocommerce_notice() {
	?>
	<div class="error">
		<p>
			<?php
			/* translators: %s: WooCommerce website URL */
			echo wp_kses_post( sprintf( __( 'Qraga WooCommerce Integration requires WooCommerce to be installed and active. You can download %s.', 'qraga' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) );
			?>
		</p>
	</div>
	<?php
}

// Hook into plugins_loaded to initialize
add_action( 'plugins_loaded', 'qraga_initialize_plugin' );
