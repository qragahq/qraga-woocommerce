<?php
// File: includes/class-qraga-widget-display.php

defined('ABSPATH') || exit;

/**
 * Handles the frontend display logic for the Qraga Product Widget.
 */
class Qraga_Widget_Display {

    const CDN_HANDLE = 'qraga-widget-cdn';
    const INIT_HANDLE = 'qraga-widget-init';
    const VARIANT_HANDLE = 'qraga-variant-handler';

    /**
     * Registers frontend scripts needed for the widget.
     * Enqueues them conditionally, typically on single product pages.
     *
     * Hook this into 'wp_enqueue_scripts'.
     */
    public static function register_and_enqueue_scripts() {
        $is_dev = defined('QRAGA_DEV') ? QRAGA_DEV : false;
        
        // Use local script in development, CDN in production
        if ($is_dev) {
            $script_url = 'http://localhost:9000/qraga-assistant.js';
        } else {
            $script_url = 'https://cdn.qraga.com/widgets/assistant/v1/qraga-assistant.js';
        }
        
        wp_register_script(
            self::CDN_HANDLE,
            $script_url,
            [],
            null,
            true // Load in footer
        );

        $init_script_path = 'assets/js/frontend/qraga-widget-init.js';
        $init_asset_path = defined('QRAGA_PLUGIN_DIR') ? QRAGA_PLUGIN_DIR . 'assets/js/frontend/qraga-widget-init.asset.php' : '';
        $init_script_version = defined('QRAGA_VERSION') ? QRAGA_VERSION : '1.0.0';
        $init_deps = [self::CDN_HANDLE];

        $init_asset = array('dependencies' => $init_deps, 'version' => $init_script_version);
        if (!empty($init_asset_path) && file_exists($init_asset_path)) {
            $asset_data = require($init_asset_path);
            $init_asset['dependencies'] = isset($asset_data['dependencies']) ? array_merge($init_deps, $asset_data['dependencies']) : $init_deps;
            $init_asset['version']      = $asset_data['version'] ?? $init_script_version;
        }
        wp_register_script(
            self::INIT_HANDLE,
            defined('QRAGA_URL') ? QRAGA_URL . $init_script_path : '',
            array_unique($init_asset['dependencies']),
            $init_asset['version'],
            true // Load in footer
        );

        $variant_script_path = 'assets/js/frontend/qraga-variant-handler.js';
        $variant_asset_path = defined('QRAGA_PLUGIN_DIR') ? QRAGA_PLUGIN_DIR . 'assets/js/frontend/qraga-variant-handler.asset.php' : '';
        $variant_script_version = defined('QRAGA_VERSION') ? QRAGA_VERSION : '1.0.0';
        $variant_deps = ['jquery', self::INIT_HANDLE];

        $variant_asset = array('dependencies' => $variant_deps, 'version' => $variant_script_version);
        if (!empty($variant_asset_path) && file_exists($variant_asset_path)) {
            $asset_data = require($variant_asset_path);
            $variant_asset['dependencies'] = isset($asset_data['dependencies']) ? array_merge($variant_deps, $asset_data['dependencies']) : $variant_deps;
            $variant_asset['version']      = $asset_data['version'] ?? $variant_script_version;
        }
         wp_register_script(
            self::VARIANT_HANDLE,
            defined('QRAGA_URL') ? QRAGA_URL . $variant_script_path : '',
            array_unique($variant_asset['dependencies']),
            $variant_asset['version'],
            true // Load in footer
        );

        if ( is_singular('product') ) {
            wp_enqueue_script(self::CDN_HANDLE);
            wp_enqueue_script(self::INIT_HANDLE);
            wp_enqueue_script(self::VARIANT_HANDLE);
        }
    }

    /**
     * Renders the placeholder div for the Qraga Product Widget block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block default content.
     * @param WP_Block $block      Block instance.
     * @return string HTML output of the placeholder div.
     */
    public static function render_widget_block( array $attributes, string $content, WP_Block $block ): string {
        return self::render_placeholder( $attributes );
    }

    /**
     * Generates the HTML placeholder for the Qraga widget.
     * This will be found and initialized by qraga-widget-init.js.
     *
     * @param array|string $atts Attributes (from block, shortcode/widget, or hook).
     * @return string HTML output.
     */
    public static function render_placeholder($atts = []): string {
        // Ensure $atts is an array, especially when called from hooks
        if (!is_array($atts)) {
            $atts = [];
        }
        
        $widget_id = get_option('qraga_widget_id');

        if (empty($widget_id)) {
            return '';
        }

        $product_id = null;
        if (!empty($atts['product_id'])) {
            $product_id = intval($atts['product_id']);
        } elseif (is_singular('product')) {
             $current_post = get_post();
             if($current_post && $current_post->post_type === 'product') {
                $product_id = $current_post->ID;
             }
        }
        
        if ( empty($product_id) ) {
            return '';
        }
        
        $container_id = 'qraga-widget-container';
        $is_dev = defined('QRAGA_DEV') ? QRAGA_DEV : false;

        $html_placeholder = sprintf(
            '<div id="%s" class="qraga-widget-placeholder" data-widget-id="%s" data-product-id="%s" data-dev="%s" style="margin-top: 20px; margin-bottom: 20px;"></div>',
            esc_attr($container_id),
            esc_attr($widget_id),
            esc_attr((string)$product_id),
            esc_attr($is_dev ? 'true' : 'false')
        );

        return $html_placeholder;
    }
} 