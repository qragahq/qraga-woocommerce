<?php

/**
 * Admin API for Qraga
 *
 * @since     0.1.0
 */

defined('ABSPATH') || exit;

class Qraga_Api // Renamed class
{

    private static $_instance = null;

    public function __construct()
    {
        add_action('rest_api_init', function () {
            // Settings endpoints - using QRAGA_REST_API_ROUTE
            register_rest_route(QRAGA_REST_API_ROUTE, '/settings/', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'get_permission')
            ));
            register_rest_route(QRAGA_REST_API_ROUTE, '/settings/', array(
                'methods' => 'POST',
                'callback' => array($this, 'set_settings'),
                'permission_callback' => array($this, 'get_permission')
            ));

            // Example: Posts Endpoint - using QRAGA_REST_API_ROUTE
            register_rest_route(QRAGA_REST_API_ROUTE, '/posts/(?P<page>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_posts_example'),
                'args' => array(
                    'page' => array(
                        'validate_callback' => function ($param, $request, $key) {
                            return is_numeric($param);
                        }
                    ),
                ),
                'permission_callback' => array($this, 'get_permission')
            ));

            // Example: License Endpoint - using QRAGA_REST_API_ROUTE 
            // (can be removed or adapted for Qraga)
            register_rest_route(QRAGA_REST_API_ROUTE, '/license/', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_license_example'),
                'permission_callback' => array($this, 'get_permission')
            ));
        });
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function allowed_html_settings()
    {
        return array(); // Define Qraga settings that can contain HTML if any
    }

    private function registered_settings()
    {
        $prefix = 'qraga_'; // Updated prefix for Qraga settings
        $options = array(
            $prefix . 'widget_id',
            $prefix . 'shop_id',
            $prefix . 'api_key',
            // Add other actual Qraga settings keys here
        );
        return $options;
    }

    public function get_settings()
    {
        $result = [];
        foreach ($this->registered_settings() as $key) {
            $value = get_option($key);
            if ($value !== false) { 
                 $result[$key] = $value;
            }
        }
        return new WP_REST_Response($result, 200);
    }

    public function set_settings($data)
    {
        $fields = $this->registered_settings();
        $allowed_html = $this->allowed_html_settings();
        $params = $data->get_params();

        foreach ($params as $key => $value) {
            if (in_array($key, $fields)) {
                $sanitized_value = in_array($key, $allowed_html) ? wp_kses_post($value) : sanitize_text_field($value);
                update_option($key, $sanitized_value);
            }
        }
        return $this->get_settings(); // Return all current settings after update
    }

    public function get_posts_example($request)
    {
        $params = $request->get_params();
        if (isset($params['page']) && is_numeric($params['page'])) {
            $page = intval($params['page']);
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => 10,
                'post_status' => 'publish',
                'paged' => $page
            );
            $posts_data = [];
            $query = new WP_Query($args);
            $total_pages = $query->max_num_pages;
            foreach ($query->posts as $post) {
                $formatted_date = date_i18n(get_option('date_format'), strtotime($post->post_date));
                $author_info = get_userdata($post->post_author);

                $posts_data[] = array(
                    'postID'     => $post->ID,
                    'postName'   => esc_html($post->post_title),
                    'postDate'   => $formatted_date,
                    'postAuthor' => $author_info ? esc_html($author_info->display_name) : __('Unknown Author', 'qraga'),
                    'postStatus' => $post->post_status,
                );
            }
            $response = array('numOfPages' => $total_pages, 'data' => $posts_data);
            return new WP_REST_Response($response, 200);
        }
        return new WP_Error('invalid_page', __('Invalid page number.', 'qraga'), array('status' => 400));
    }

    function get_license_example()
    {
        $response = array(
            "license_key" => get_option('qraga_license_key', 'N/A'),
            "status" => get_option('qraga_license_status', 'inactive') 
        );
        return new WP_REST_Response($response, 200);
    }

    public function get_permission()
    {
        if (current_user_can('manage_options')) { 
            return true;
        } else {
            return new WP_Error('rest_forbidden', __('You do not have permission to access this endpoint.', 'qraga'), array('status' => 403));
        }
    }

    public function __clone()
    {
        // Using QRAGA_VERSION and 'qraga' text domain
        _doing_it_wrong(__FUNCTION__, __('Cheatin\' huh?', 'qraga'), QRAGA_VERSION);
    }
}

Qraga_Api::instance(); // Updated class instantiation 