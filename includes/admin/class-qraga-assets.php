<?php

/**
 * Handle backend scripts for Qraga
 *
 * @since     0.1.0
 */

defined('ABSPATH') || exit;

class Qraga_Assets // Renamed class
{
	public function __construct()
	{
		add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'), 10, 1);
	}

	/**
	 * Enqueue Backend Scripts
	 *
	 * @since 0.1.0
	 */
	public static function admin_enqueue_scripts()
	{
		$currentScreen = get_current_screen();
		$screenID = $currentScreen->id;

		// Updated screen ID to match the 'qraga' menu slug
		if ($screenID === "toplevel_page_qraga") {
			$apiNonce = wp_create_nonce('wp_rest');
			// Using renamed QRAGA_ constants
			$root = rest_url();
			$baseUrl = QRAGA_URL; 

			// Load built admin app
			wp_enqueue_script(
				'qraga-admin-app', 
				QRAGA_URL . 'includes/admin/assets/js/main.js', 
				array('wp-i18n'), 
				QRAGA_VERSION, 
				true 
			);
			// Pass data to JavaScript
			wp_localize_script(
				'qraga-admin-app',
				'qragaData',
				array(
					'apiNonce' => $apiNonce,
					'root'     => $root,
					'baseUrl'  => $baseUrl,
				)
			);
		}
	}
}

new Qraga_Assets(); // Updated class instantiation 