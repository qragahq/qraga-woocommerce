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

final class Qraga_Plugin
{
    private static $instance;

    private $version = '0.1.0';

    private function __construct()
    {
        $this->define_constants();
        $this->includes();
    }

    private function includes()
    {
        if (is_admin()) {
            require_once(QRAGA_ABSPATH . 'includes/admin/class-qraga-menu.php');
            require_once(QRAGA_ABSPATH . 'includes/admin/class-qraga-assets.php');
        }
        require_once(QRAGA_ABSPATH . 'includes/admin/class-qraga-api.php');
    }
    /**
     * Define Plugin Constants.
     * @since 0.1.0
     */
    private function define_constants()
    {
        $this->define('QRAGA_DEV', false);
        $this->define('QRAGA_REST_API_ROUTE', 'qraga/v1');
        $this->define('QRAGA_URL', plugin_dir_url(__FILE__));
        $this->define('QRAGA_ABSPATH', dirname(__FILE__) . '/');
        $this->define('QRAGA_VERSION', $this->get_version());
    }

    /**
     * Returns Plugin version for global
     * @since  0.1.0
     */
    private function get_version()
    {
        return $this->version;
    }

    /**
     * Define constant if not already set.
     *
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

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Qraga_Plugin::get_instance();
