<?php
/**
 * Plugin Name: Qraga
 * Version: 0.1.0
 * Author: The WordPress Contributors
 * Author URI: https://woo.com
 * Text Domain: qraga
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Feature: custom_order_tables // Added for HPOS
 *
 * @package extension
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MAIN_PLUGIN_FILE' ) ) {
	define( 'MAIN_PLUGIN_FILE', __FILE__ );
}

// Include the Service class
$qraga_service_file = plugin_dir_path( __FILE__ ) . 'includes/Service/QragaService.php';
if ( file_exists( $qraga_service_file ) ) {
    include_once $qraga_service_file;
} // else: Consider adding an error log or admin notice if the service file is expected but missing

// Include the REST Controller class
$qraga_rest_controller_file = plugin_dir_path( __FILE__ ) . 'includes/class-qraga-rest-controller.php';
if ( file_exists( $qraga_rest_controller_file ) ) {
    include_once $qraga_rest_controller_file;
} // else: Consider adding an error log or admin notice if the rest controller file is expected but missing

/**
 * Declare compatibility with High-Performance Order Storage (HPOS)
 */
add_action(
    'before_woocommerce_init',
    function() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

use Qraga\Admin\Setup;

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function qraga_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Qraga requires WooCommerce to be installed and active. You can download %s here.', 'qraga' ), '<a href="https://woo.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

register_activation_hook( __FILE__, 'qraga_activate' );

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function qraga_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'qraga_missing_wc_notice' );
		return;
	}
}

if ( ! class_exists( 'qraga' ) ) :
	/**
	 * The qraga class.
	 */
	class qraga {
		/**
		 * This class instance.
		 *
		 * @var \qraga single instance of this class.
		 */
		private static $instance;

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( is_admin() ) {
				new Setup();
			}
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			// If the scaffold provided a $version property, use it. Otherwise, use a placeholder string.
            $version = property_exists($this, 'version') ? $this->version : '0.1.0';
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'qraga' ), $version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			// If the scaffold provided a $version property, use it. Otherwise, use a placeholder string.
            $version = property_exists($this, 'version') ? $this->version : '0.1.0';
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'qraga' ), $version );
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
		 * @return \qraga
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
endif;

add_action( 'plugins_loaded', 'qraga_init', 10 );

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function qraga_init() {
	load_plugin_textdomain( 'qraga', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'qraga_missing_wc_notice' );
		return;
	}

	// Initialize the main plugin class instance
	qraga::instance();

	// Initialize Qraga Service and hooks
	if ( class_exists( \Qraga\Service\QragaService::class ) ) {
	    static $qraga_service = null;
        if (is_null($qraga_service)) {
             $qraga_service = new \Qraga\Service\QragaService();
        }

        // Hook into product save/update actions.
        add_action( 'woocommerce_update_product', function( $product_id, $product ) use ( $qraga_service ) {
            if ($qraga_service) {
                $qraga_service->handle_product_save( $product_id, $product );
            }
        }, 10, 2 );

        // Hook into product trashing action.
        add_action( 'wp_trash_post', function( $post_id ) use ( $qraga_service ) {
            if ($qraga_service) {
                $qraga_service->handle_product_delete( $post_id );
            }
        }, 10, 1 );

        // Hook into product permanent deletion action.
        add_action( 'before_delete_post', function( $post_id ) use ( $qraga_service ) {
            if ($qraga_service) {
                $qraga_service->handle_product_delete( $post_id );
            }
        }, 10, 1 );
	} else {
	    error_log('Qraga Error: QragaService class not found, cannot initialize product hooks.');
	}

}

/**
 * Initialize REST API routes.
 */
function qraga_initialize_rest_api() {
	if ( ! class_exists( \Qraga\QragaRestController::class ) ) {
	    error_log('Qraga Error: QragaRestController class not found, cannot initialize REST API routes.');
		return;
	}
	$controller = new \Qraga\QragaRestController();
	$controller->register_routes();
}
add_action( 'rest_api_init', 'qraga_initialize_rest_api' );

// --- Block Registration ---

/**
 * Register the Qraga Widget Placeholder block.
 */
function qraga_register_widget_block() {
    // Prefer registering via PHP to easily assign the render callback
    register_block_type( 'qraga/widget-placeholder', array(
        'api_version' => 3,
        'title' => __( 'Qraga Widget Placeholder', 'qraga' ),
        'category' => 'woocommerce', // Or widgets, design
        'icon' => 'cloud-upload',
        'description' => __( 'Designates the position for the Qraga product page widget.', 'qraga' ),
        'render_callback' => 'qraga_render_widget_placeholder_block', // Use our PHP function
        'supports' => [ 'html' => false ],
        'editor_script' => 'qraga-block-editor-script', // Handle defined below
    ) );

    // Register the editor script associated with the block handle
    $script_asset_path = plugin_dir_path( __FILE__ ) . 'build/blocks/qraga-widget-placeholder/index.asset.php';
    if ( file_exists( $script_asset_path ) ) {
        $script_asset = require( $script_asset_path );
        wp_register_script(
            'qraga-block-editor-script',
            plugins_url( 'build/blocks/qraga-widget-placeholder/index.js', __FILE__ ),
            $script_asset['dependencies'],
            $script_asset['version']
        );
    } else {
        // Log error only if the block is actually likely to be used (e.g., in block editor context)
        // This prevents errors during activation before build is run.
        if (is_admin()) { // Basic check, could be more specific
             error_log('Qraga Warning: Block editor script asset missing: build/blocks/qraga-widget-placeholder/index.asset.php. Run npm run build.');
        }
    }
}
add_action( 'init', 'qraga_register_widget_block' );

/**
 * Render callback for the Qraga Widget Placeholder block.
 * Outputs the container div if the widget ID is set and on frontend product page.
 *
 * @param array $attributes Block attributes.
 * @param string $content Block inner content.
 * @return string HTML output.
 */
function qraga_render_widget_placeholder_block( $attributes, $content ) {
    // Only output on single product pages on the frontend
    if ( is_admin() || ! function_exists('is_product') || ! is_product() ) {
        // Provide a placeholder in the editor context
        if ( defined('REST_REQUEST') && REST_REQUEST ) {
            return '<div style="padding: 1em; border: 1px dashed #ccc; text-align: center; color: #777;"><em>'.__('Qraga Widget Placeholder', 'qraga').'</em></div>';
        }
        return '';
    }

    $widget_id = trim( get_option( 'qraga_widget_id', '' ) );

    if ( empty( $widget_id ) ) {
        return '';
    }

    // Use the same div structure as the hook render function
    return '<div id="qraga-widget-container" data-widget-id="' . esc_attr( $widget_id ) . '"></div>';
}

// --- Customizer Integration ---

/**
 * Register Customizer controls for Qraga Widget.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function qraga_customize_register( $wp_customize ) {

    // Only add Customizer option for Classic Themes
    if ( function_exists('wp_is_block_theme') && wp_is_block_theme() ) {
        return;
    }

    // Only proceed if WooCommerce is active (optional but good practice)
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // --- Add Qraga Widget Section ---
    $wp_customize->add_section( 'qraga_widget_section', array(
        'title'    => __( 'Qraga Widget', 'qraga' ),
        'priority' => 160, // Adjust priority to place it logically (e.g., near WooCommerce sections)
        'description' => __( 'Configure the display of the Qraga widget on product pages.', 'qraga' ),
        // 'panel' => 'woocommerce', // Optional: Put it inside the WooCommerce panel
    ) );

    // --- Add Widget Position Setting ---
    $wp_customize->add_setting( 'qraga_widget_position', array(
        'default'           => 'woocommerce_after_single_product_summary', // Default position
        'sanitize_callback' => 'qraga_sanitize_widget_position', // Function to validate selection
        'type'              => 'option', // Store directly in wp_options table
        'capability'        => 'edit_theme_options', // Or 'manage_options'
        // 'transport'         => 'postMessage', // Use 'refresh' (default) or 'postMessage' for live preview
    ) );

    // --- Add Widget Position Control ---
    $wp_customize->add_control( 'qraga_widget_position_control', array(
        'label'       => __( 'Widget Position on Product Page', 'qraga' ),
        'description' => __( 'Select where the Qraga widget should appear.', 'qraga' ),
        'section'     => 'qraga_widget_section',
        'settings'    => 'qraga_widget_position',
        'type'        => 'select',
        'choices'     => array(
            'woocommerce_before_single_product_summary' => __( 'Before Product Summary', 'qraga' ),
            'woocommerce_single_product_summary'        => __( 'Inside Product Summary (Top)', 'qraga' ), // Careful, might need specific priority
            'woocommerce_after_add_to_cart_form'        => __( 'After Add to Cart Form', 'qraga' ),
            'woocommerce_after_single_product_summary'  => __( 'After Product Summary', 'qraga' ),
            'woocommerce_after_single_product'          => __( 'After Main Product Section', 'qraga' ),
            // Add more standard WooCommerce hooks if desired
        ),
    ) );

    // --- Optional: Add Setting/Control for Widget ID (Read-only info) ---
    // You generally manage the ID in the plugin settings page, but could show it here
    $wp_customize->add_setting( 'qraga_widget_id_display', array(
        'default' => get_option('qraga_widget_id', ''),
        'type'    => 'option', // Read directly
        'capability' => 'edit_theme_options',
        'sanitize_callback' => '__return_empty_string', // No saving
    ) );
     $wp_customize->add_control( new \WP_Customize_Control(
        $wp_customize,
        'qraga_widget_id_display_control',
        array(
            'label' => __( 'Active Widget ID', 'qraga' ),
            'description' => __( 'Set this on the Qraga plugin settings page.', 'qraga' ),
            'section' => 'qraga_widget_section',
            'settings' => 'qraga_widget_id_display',
            'type' => 'hidden', // Or 'text' with input_attrs['readonly'] = true
             'input_attrs' => array(
                'value' => get_option('qraga_widget_id', __('Not Set', 'qraga')),
                'disabled' => true,
            ),
        )
    ) );

}
add_action( 'customize_register', 'qraga_customize_register' );

/**
 * Sanitize the widget position selection.
 *
 * @param string $input The selected position hook name.
 * @return string Sanitized position hook name or default.
 */
function qraga_sanitize_widget_position( $input ) {
    $allowed_positions = array(
        'woocommerce_before_single_product_summary',
        'woocommerce_single_product_summary',
        'woocommerce_after_add_to_cart_form',
        'woocommerce_after_single_product_summary',
        'woocommerce_after_single_product',
    );
    if ( in_array( $input, $allowed_positions ) ) {
        return $input;
    }
    // Return default if input is invalid
    return 'woocommerce_after_single_product_summary';
}

// --- Front-end Widget Injection ---

/**
 * Enqueue the external Qraga widget script if configured.
 */
function qraga_enqueue_widget_script() {
	// Only on single product pages
	if ( ! is_product() ) {
		return;
	}

	global $product;
	if ( ! is_object( $product ) ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! $product ) {
		return; // Exit if product object couldn't be obtained
	}

	$widget_id = trim( get_option( 'qraga_widget_id', '' ) );

	// Only enqueue if Widget ID is set
	if ( ! empty( $widget_id ) ) {
		// Use the correct widget script URL
		$widget_script_url = 'https://cdn.qraga.com/widgets/assistant/v1/qraga-assistant.js';

		wp_enqueue_script(
			'qraga-external-widget',
			$widget_script_url,
			array(), // Add dependencies if any (e.g., 'jquery')
			null,    // No version needed for external, or use plugin version
			true     // Load in footer
		);

		// --- Prepare data for widget initialization ---
		$product_id_for_widget = 'prod-wc-' . $product->get_id();
		$initial_variant_id = null;

		// Attempt to get a default variant ID if it's a variable product
		if ( $product->is_type( 'variable' ) ) {
			// This is tricky - finding the *default* selected variation reliably
			// might depend on theme/plugins. A simple approach is to take the first available one.
			// It's safer to let the frontend JS set the correct one on load/change.
			// $default_attributes = $product->get_default_attributes();
			// if (!empty($default_attributes)) {
			// 	$variation_id = find_matching_product_variation( $product, $default_attributes );
			// 	if ( $variation_id > 0 ) {
			// 		$initial_variant_id = 'var-wc-' . $variation_id;
			// 	}
			// }
			// If no default, maybe grab the first child ID?
			// $children = $product->get_children();
			// if (!empty($children)) {
			//     $initial_variant_id = 'var-wc-' . $children[0];
			// }
			// For now, leave as null and let frontend JS handle it
		}

		$widget_init_data = [
			'widgetId' => $widget_id,
			'container' => '#qraga-widget-container', // Matches the div ID we output
			'product' => [
				'id' => $product_id_for_widget,
				'variant_id' => $initial_variant_id, // Initially null, JS will update
			]
			// Add user or page context here if needed by the widget
			// 'user' => [ 'id' => get_current_user_id() ],
			// 'page' => [ 'url' => get_permalink() ]
		];

		// --- Add inline script to initialize the widget ---
		$init_script = sprintf(
			'document.addEventListener("DOMContentLoaded", function() { if(window.Qraga) { window.qragaWidget = new window.Qraga(%s).init(); } else { console.error("Qraga widget script loaded but window.Qraga not found."); } });',
			wp_json_encode( $widget_init_data ) // Encode data safely for JS
		);

		wp_add_inline_script(
			'qraga-external-widget',
			$init_script,
			'after' // Add after the main widget script runs
		);

		// Enqueue our script to handle variant changes
		$product_script_path = '/build/product-widget.js';
		$product_script_asset_path = dirname( MAIN_PLUGIN_FILE ) . '/build/product-widget.asset.php'; // Assuming build process creates this
		$product_script_asset = file_exists( $product_script_asset_path )
			? require $product_script_asset_path
			: [ 'dependencies' => ['jquery'], 'version' => filemtime( dirname( MAIN_PLUGIN_FILE ) . $product_script_path ) ];

		wp_enqueue_script(
			'qraga-product-variant-handler',
			plugins_url( $product_script_path, MAIN_PLUGIN_FILE ),
			$product_script_asset['dependencies'], // Should include 'jquery'
			$product_script_asset['version'],
			true // Load in footer
		);
	}
}
add_action( 'wp_enqueue_scripts', 'qraga_enqueue_widget_script' );

/**
 * Hook the widget rendering function to the position selected in the Customizer.
 */
function qraga_hook_widget_render() {
    // Only proceed on the frontend and if WooCommerce functions exist
    if ( is_admin() || ! function_exists('is_product') ) {
        return;
    }

    // **If it's a block theme, assume the user will place the block manually.**
    // **Do not add the widget via hooks in this case.**
    if ( function_exists('wp_is_block_theme') && wp_is_block_theme() ) {
        return;
    }

    // Proceed with hook injection for Classic themes
    $widget_id = trim( get_option( 'qraga_widget_id', '' ) );
    $position  = get_option( 'qraga_widget_position', 'woocommerce_after_single_product_summary' );
    $position = qraga_sanitize_widget_position( $position );

    if ( ! empty( $widget_id ) && is_product() ) {
         add_action( $position, 'qraga_render_widget_div_output', 10 );
    }
}
// Use 'template_redirect' which runs after the query is set up but before the template loads
add_action( 'template_redirect', 'qraga_hook_widget_render' );

/**
 * Output the actual widget container div.
 */
function qraga_render_widget_div_output() {
     // You might want to wrap this with checks again, though the hook should only be added if valid
     $widget_id = trim( get_option( 'qraga_widget_id', '' ) );
     if ( empty( $widget_id ) ) {
         return;
     }

    // Ensure the ID matches what your external script expects
    echo '<div id="qraga-widget-container" data-widget-id="' . esc_attr( $widget_id ) . '"></div>';
}
