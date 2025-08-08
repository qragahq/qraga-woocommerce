<?php
/**
 * Main Qraga Plugin Class.
 *
 * @package Qraga
 * @license GPL-3.0-or-later
 */

defined('ABSPATH') || exit;
final class Qraga_Plugin
{
    private static $instance;

    private $version = '0.1.0';
    public $product_sync;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define Plugin Constants.
     * @since 0.1.0
     */
    private function define_constants()
    {
        if (!defined('QRAGA_ABSPATH')) {
            define('QRAGA_ABSPATH', dirname(QRAGA_PLUGIN_FILE) . '/');
        }
        if (!defined('QRAGA_DEV')) {
            define('QRAGA_DEV', false);
        }
        if (!defined('QRAGA_REST_API_ROUTE')) {
            define('QRAGA_REST_API_ROUTE', 'qraga/v1');
        }
        if (!defined('QRAGA_URL')) {
            define('QRAGA_URL', plugin_dir_url(QRAGA_PLUGIN_FILE)); 
        }
        if (!defined('QRAGA_VERSION')) {
            define('QRAGA_VERSION', $this->version);
        }
    }
    
    /**
     * Include required files.
     * @since 0.1.0
     */
    private function includes()
    {
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-menu.php';
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-assets.php';
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-api.php';
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-product-sync.php';

        require_once QRAGA_ABSPATH . 'includes/class-qraga-widget-display.php';
        require_once QRAGA_ABSPATH . 'includes/widgets/class-qraga-product-widget-widget.php';
        
        if (is_admin()) {
            new Qraga_Menu();
            new Qraga_Assets();
        }
        Qraga_Api::instance();

        $this->product_sync = new Qraga_Product_Sync();
    }

    /**
     * Initialize hooks.
     * @since 0.1.0
     */
    private function init_hooks()
    {
        add_action('init', [$this, 'register_blocks'], 0);
        add_action('init', [$this, 'register_shortcodes']);
        add_action( 'widgets_init', [$this, 'register_widgets']);
        add_action( 'wp_enqueue_scripts', [ 'Qraga_Widget_Display', 'register_and_enqueue_scripts' ] );
        add_action( 'customize_register', array( $this, 'customize_register' ), 20 );
        add_action( 'template_redirect', array( $this, 'hook_widget_render' ) );
    }

    /**
     * Register widgets.
     * @since 0.1.0
     */
    public function register_widgets() {
         if ( ! class_exists('Qraga_Product_Widget_Widget') ) {
            return;
         }
         register_widget( 'Qraga_Product_Widget_Widget' );
    }

    /**
     * Register shortcodes.
     * @since 0.1.0
     */
    public function register_shortcodes()
    {
        if (class_exists('Qraga_Widget_Display')) {
           add_shortcode('qraga_product_widget', ['Qraga_Widget_Display', 'render_placeholder']);
        }
    }

    /**
     * Returns Plugin version.
     * @since  0.1.0
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Get the singleton instance.
     * @since 0.1.0
     * @return Qraga_Plugin
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cloning is forbidden.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'qraga' ), $this->version );
    }

    /**
     * Unserializing instances is forbidden.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'qraga' ), $this->version );
    }

    /**
     * Register Customizer controls for Qraga Widget.
     *
     * @param WP_Customize_Manager $wp_customize Customizer object.
     */
    public function customize_register( $wp_customize ) {
        if ( function_exists('wp_is_block_theme') && wp_is_block_theme() ) {
            return;
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Try to attach our control into WooCommerce's existing Single Product/Product Page section when available
        $target_section_id = null;
        $candidate_section_ids = array(
            'woocommerce_product_page',
            'woocommerce_single_product',
            'woocommerce_single',
            'woocommerce_product',
        );

        foreach ( $candidate_section_ids as $candidate_id ) {
            if ( $wp_customize->get_section( $candidate_id ) ) {
                $target_section_id = $candidate_id;
                break;
            }
        }

        if ( ! $target_section_id ) {
            // Fallback: create our own section, preferably under the WooCommerce panel
            $target_section_id = 'qraga_widget_section';
            if ( $wp_customize->get_panel( 'woocommerce' ) ) {
                $wp_customize->add_section( $target_section_id, array(
                    'title'       => __( 'Qraga Widget', 'qraga' ),
                    'priority'    => 160,
                    'description' => __( 'Configure the display of the Qraga widget on product pages.', 'qraga' ),
                    'panel'       => 'woocommerce',
                ) );
            } else {
                $wp_customize->add_section( $target_section_id, array(
                    'title'       => __( 'Qraga Widget', 'qraga' ),
                    'priority'    => 160,
                    'description' => __( 'Configure the display of the Qraga widget on product pages.', 'qraga' ),
                ) );
            }
        }

        $wp_customize->add_setting( 'qraga_widget_position', array(
            'default'           => 'woocommerce_after_single_product_summary',
            'sanitize_callback' => array( $this, 'sanitize_widget_position' ),
            'type'              => 'option',
            'capability'        => 'edit_theme_options',
        ) );

        $wp_customize->add_control( 'qraga_widget_position_control', array(
            'label'       => __( 'Qraga Widget', 'qraga' ),
            //'description' => __( 'Select where the widget appears on the product page.', 'qraga' ),
            'section'     => $target_section_id,
            'settings'    => 'qraga_widget_position',
            'type'        => 'select',
            'priority'    => 999,
            'choices'     => array(
                'woocommerce_after_single_product_summary'  => __( 'After Product Summary', 'qraga' ),
                'woocommerce_single_product_summary'        => __( 'Inside Product Summary', 'qraga' ),
                'woocommerce_product_thumbnails'            => __( 'After Product Images', 'qraga' ),
            ),
        ) );
    }

    /**
     * Sanitize the widget position selection.
     *
     * @param string $input The selected position hook name.
     * @return string Sanitized position hook name or default.
     */
    public function sanitize_widget_position( $input ) {
        $allowed_positions = array(
            'woocommerce_after_single_product_summary',
            'woocommerce_single_product_summary',
            'woocommerce_product_thumbnails',
        );
        if ( in_array( $input, $allowed_positions ) ) {
            return $input;
        }
        return 'woocommerce_after_single_product_summary';
    }

    /**
     * Hook the widget render function to the selected WooCommerce action.
     */
    public function hook_widget_render() {
        if ( is_admin() || ! function_exists( 'is_product' ) ) {
            return;
        }

        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            return;
        }
        
        if ( ! is_product() ) {
            return;
        }

        $position = get_option( 'qraga_widget_position', 'woocommerce_after_single_product_summary' );
        $position = $this->sanitize_widget_position( $position );
        
        // Use much higher priority (lower number) for summary to appear at very bottom
        $priority = ( $position === 'woocommerce_single_product_summary' ) ? 99 : 15;
        add_action( $position, array( $this, 'display_widget_from_hook' ), $priority );
    }

    /**
     * Displays the Qraga widget placeholder when called by a WordPress action hook.
     * This method calls the static render_placeholder from Qraga_Widget_Display and echoes its output.
     */
    public function display_widget_from_hook() {
        // Debug: Add a comment to see if the hook is being called
        echo '<!-- Qraga Widget Hook Called -->';
        echo Qraga_Widget_Display::render_placeholder();
    }

    public function register_blocks() {
        $block_build_path = QRAGA_ABSPATH . 'includes/admin/blocks/qraga-product-widget/build/'; 

        if ( !file_exists( $block_build_path . 'block.json' ) ) {
            return;
        }

        register_block_type( 
            $block_build_path, 
            array(
                'render_callback' => [ 'Qraga_Plugin', 'render_qraga_product_widget_block' ], 
            )
        );
    }

    /**
     * Render callback for the Qraga Product Widget block.
     */
    public static function render_qraga_product_widget_block( $attributes, $content, $block ) {
        if ( ! is_singular('product') ) {
            return '';
        }
    
        if ( ! class_exists('Qraga_Widget_Display') ) {
            return ''; 
        }
        
        $wrapper_attributes = get_block_wrapper_attributes();
        $widget_content = Qraga_Widget_Display::render_placeholder( $attributes );
        
        return sprintf(
            '<div %s>%s</div>',
            $wrapper_attributes,
            $widget_content
        );
    }
} 