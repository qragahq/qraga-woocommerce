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
        // Define QRAGA_ABSPATH first as it's used by includes()
        $this->define('QRAGA_ABSPATH', dirname(QRAGA_PLUGIN_FILE) . '/'); 

        $this->define('QRAGA_DEV', false); // Default to production
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
        // Core classes
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-menu.php';
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-assets.php';
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-api.php';
        
        // Instantiate admin classes if in admin area
        if (is_admin()) {
            new Qraga_Menu();
            new Qraga_Assets();
        }
        // API class is needed for rest_api_init hook regardless of admin area
        new Qraga_Api();

        // Product Sync Service - Moved to admin directory
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-product-sync.php';
        $this->product_sync = new Qraga_Product_Sync(); // Store instance

        // Product Sync Service
        require_once QRAGA_ABSPATH . 'includes/admin/class-qraga-product-sync.php';
        $this->product_sync = new Qraga_Product_Sync();

        // Add other includes like services, hooks here
        // Example: require_once QRAGA_ABSPATH . 'includes/class-qraga-product-sync.php';
        // Example: new Qraga_Product_Sync();
    }

    /**
     * Initialize hooks.
     * @since 0.1.0
     */
    private function init_hooks()
    {
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * Register shortcodes.
     * @since 0.1.0
     */
    public function register_shortcodes()
    {
        if ($this->product_sync) {
            add_shortcode('qraga_widget', [$this->product_sync, 'generate_widget_code']);
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
} 