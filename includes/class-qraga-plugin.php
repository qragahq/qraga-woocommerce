<?php

defined('ABSPATH') || exit;

/**
 * Main Qraga Plugin Class.
 */
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
        $this->define('QRAGA_ABSPATH', dirname(QRAGA_PLUGIN_FILE) . '/');
        $this->define('QRAGA_DEV', false);
        $this->define('QRAGA_REST_API_ROUTE', 'qraga/v1');
        $this->define('QRAGA_URL', plugin_dir_url(QRAGA_PLUGIN_FILE)); 
        $this->define('QRAGA_VERSION', $this->get_version());
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
        add_action('init', [$this, 'register_shortcodes']);
        add_action( 'init', [$this, 'register_blocks']);
        add_action( 'widgets_init', [$this, 'register_widgets']);
        add_action( 'wp_enqueue_scripts', [ 'Qraga_Widget_Display', 'register_and_enqueue_scripts' ] );
        add_action( 'customize_register', array( $this, 'customize_register' ) );
        add_action( 'template_redirect', array( $this, 'hook_widget_render' ) );
    }

    /**
     * Register blocks.
     * @since 0.1.0
     */
    public function register_blocks() {
        if ( ! class_exists('Qraga_Widget_Display') ) {
            return; 
        }
        
        $block_json_path = QRAGA_ABSPATH . 'includes/admin/backend/src/blocks/qraga-widget/'; 

        if ( file_exists( $block_json_path . 'block.json' ) ) {
             register_block_type( $block_json_path );
        }
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
     * Define constant if not already set.
     * @since  0.1.0
     * @param  string $name
     * @param  string|bool $value
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
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

        $wp_customize->add_section( 'qraga_widget_section', array(
            'title'       => __( 'Qraga Widget', 'qraga' ),
            'priority'    => 160,
            'description' => __( 'Configure the display of the Qraga widget on product pages.', 'qraga' ),
        ) );

        $wp_customize->add_setting( 'qraga_widget_position', array(
            'default'           => 'woocommerce_after_single_product_summary',
            'sanitize_callback' => array( $this, 'sanitize_widget_position' ),
            'type'              => 'option',
            'capability'        => 'edit_theme_options',
        ) );

        $wp_customize->add_control( 'qraga_widget_position_control', array(
            'label'       => __( 'Widget Position on Product Page', 'qraga' ),
            'description' => __( 'Select where the Qraga widget should appear.', 'qraga' ),
            'section'     => 'qraga_widget_section',
            'settings'    => 'qraga_widget_position',
            'type'        => 'select',
            'choices'     => array(
                'woocommerce_after_single_product_summary'  => __( 'After Product Summary', 'qraga' ),
                'woocommerce_after_single_product'          => __( 'After Main Product Section', 'qraga' ),
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
            'woocommerce_after_single_product',
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
        
        add_action( $position, array( $this, 'display_widget_from_hook' ), 15 );
    }

    /**
     * Displays the Qraga widget placeholder when called by a WordPress action hook.
     * This method calls the static render_placeholder from Qraga_Widget_Display and echoes its output.
     */
    public function display_widget_from_hook() {
        echo Qraga_Widget_Display::render_placeholder();
    }
} 