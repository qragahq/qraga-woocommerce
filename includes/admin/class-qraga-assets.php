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
			$root = rest_url(QRAGA_REST_API_ROUTE . '/'); 
			$baseUrl = QRAGA_URL; 

			// Check for development mode using QRAGA_DEV
			if (defined('QRAGA_DEV') && QRAGA_DEV) {
?>
				<script>
					// Renamed JavaScript object to qragaData
					var qragaData = {
						apiNonce: '<?php echo $apiNonce; ?>',
						root: '<?php echo $root; ?>',
						baseUrl: '<?php echo $baseUrl; ?>',
					}
				</script>
				<script type="module">
					import RefreshRuntime from "http://localhost:5178/@react-refresh"
					RefreshRuntime.injectIntoGlobalHook(window)
					window.$RefreshReg$ = () => {}
					window.$RefreshSig$ = () => (type) => type
					window.__vite_plugin_react_preamble_installed__ = true
				</script>
				<script type="module" src="http://localhost:5178/@vite/client"></script>
				<script type="module" src="http://localhost:5178/src/main.tsx"></script> <?php // Path to Vite dev server entry point
?>
<?php
			} else {
				// Production mode: Enqueue compiled assets
				// Renamed script handle to 'qraga-admin-app'
				// Using QRAGA_URL and QRAGA_VERSION constants
				wp_enqueue_script(
					'qraga-admin-app', 
					QRAGA_URL . 'includes/admin/assets/js/index.js', // Path to compiled JS
					array('wp-i18n'), // Dependencies
					QRAGA_VERSION, 
					true // Load in footer
				);
				// Renamed JavaScript object to qragaData for wp_localize_script
				wp_localize_script(
					'qraga-admin-app', // Must match the script handle
					'qragaData',
					array(
						'apiNonce' => $apiNonce,
						'root'     => $root,
						'baseUrl'  => $baseUrl,
					)
				);
				// Enqueue compiled CSS if it exists (Vite might put it in assets/css by default)
				$css_file_path = QRAGA_ABSPATH . 'includes/admin/assets/css/index.css'; // Common output for Vite
				$css_file_url = QRAGA_URL . 'includes/admin/assets/css/index.css';
				if (file_exists($css_file_path)) {
					wp_enqueue_style('qraga-admin-styles', $css_file_url, array(), QRAGA_VERSION);
				}
			}
		}
	}
}

new Qraga_Assets(); // Updated class instantiation 