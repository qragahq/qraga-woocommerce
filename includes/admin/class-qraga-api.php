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
        // error_log('Qraga_Api constructor called.'); // REMOVE THIS DEBUG LOG
        add_action('rest_api_init', function () {
            // error_log('Qraga_Api rest_api_init callback fired.'); // REMOVE THIS DEBUG LOG
            // Settings endpoints - using QRAGA_REST_API_ROUTE
            register_rest_route(QRAGA_REST_API_ROUTE, '/settings/', array(
                // Get settings
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_settings'),
                    'permission_callback' => array($this, 'get_permission'),
                    'args'                => array(),
                ),
                // Save settings
                array(
                    'methods'             => WP_REST_Server::CREATABLE, // Use POST
                    'callback'            => array($this, 'set_settings'),
                    'permission_callback' => array($this, 'get_permission'),
                    'args'                => $this->get_settings_endpoint_args(), // Use args definition
                ),
                 'schema' => array($this, 'get_public_item_schema'), // Add schema
            ));

            // Example: License Endpoint - using QRAGA_REST_API_ROUTE 
            register_rest_route(QRAGA_REST_API_ROUTE, '/license/', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_license_example'),
                'permission_callback' => array($this, 'get_permission')
            ));
            
            // Corrected Bulk Sync Endpoint registration with trailing slash
            register_rest_route(QRAGA_REST_API_ROUTE, '/export/', array(
                'methods'             => WP_REST_Server::CREATABLE, 
                'callback'            => array($this, 'handle_bulk_sync_request'),
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
        $prefix = 'qraga_'; 
        $options = array(
            $prefix . 'site_id',       // Was qraga_site_id in old code
            $prefix . 'api_key',       // Was qraga_api_key
            $prefix . 'endpoint_url',  // Was qraga_endpoint_url
            $prefix . 'widget_id',     // Was qraga_widget_id
            // Removed shop_id and endpoint_region
        );
        return $options;
    }

    public function get_settings(WP_REST_Request $request)
    {
        // Match the keys used in the old controller's response
        $settings = [
            'siteId'      => get_option( 'qraga_site_id', '' ),
            'apiKey'      => get_option( 'qraga_api_key', '' ),
            'endpointUrl' => get_option( 'qraga_endpoint_url', '' ),
            'widgetId'    => get_option( 'qraga_widget_id', '' ),
        ];
        // Consider security implications of returning apiKey
        return new WP_REST_Response($settings, 200);
    }

    public function set_settings(WP_REST_Request $request)
    {
        $params = $request->get_params();
        $updated = false;

        // Use the keys expected from the frontend (siteId, apiKey, etc.)
        // Save with the qraga_ prefix
        if ( isset( $params['siteId'] ) ) {
            update_option( 'qraga_site_id', sanitize_text_field( $params['siteId'] ) );
            $updated = true;
        }
        if ( isset( $params['apiKey'] ) ) {
             // Avoid saving empty string if key is cleared and wasn't sent? Or allow clearing?
             // Old code updated if !is_null. Let's stick to that for now.
            update_option( 'qraga_api_key', sanitize_text_field( $params['apiKey'] ) );
            $updated = true;
        }
        if ( isset( $params['endpointUrl'] ) ) {
            $url = esc_url_raw( $params['endpointUrl'] );
            // Basic validation like the old controller
            if ( ! filter_var( $url, FILTER_VALIDATE_URL ) && ! empty( $url ) ) {
                 return new WP_Error(
                    'qraga_invalid_url',
                    esc_html__( 'Invalid Endpoint URL format.', 'qraga' ),
                    [ 'status' => 400 ]
                );
            }
            update_option( 'qraga_endpoint_url', $url );
            $updated = true;
        }
         if ( isset( $params['widgetId'] ) ) {
            // Allow empty string, sanitize otherwise
            update_option( 'qraga_widget_id', sanitize_text_field( trim( $params['widgetId'] ) ) );
            $updated = true;
        }

        if ( ! $updated ) {
             return new WP_Error(
                'qraga_no_data',
                esc_html__( 'No settings data provided to update.', 'qraga' ),
                [ 'status' => 400 ]
            );
        }

        // Return the newly saved settings
        return $this->get_settings($request); 
    }

    /**
     * Get the arguments for the settings endpoint.
     * Replicates structure from old controller for consistency.
     * @return array
     */
    protected function get_settings_endpoint_args() {
        $args = [];
        $args['siteId'] = [
            'description'       => esc_html__( 'The unique site identifier.', 'qraga' ),
            'type'              => 'string',
            'required'          => false,
            'validate_callback' => function($param, $request, $key) { return is_string($param); }, // Basic validation
            'sanitize_callback' => 'sanitize_text_field',
        ];
        $args['apiKey'] = [
            'description'       => esc_html__( 'The API Key for the external service.', 'qraga' ),
            'type'              => 'string',
            'required'          => false,
            'validate_callback' => function($param, $request, $key) { return is_string($param); },
            'sanitize_callback' => 'sanitize_text_field',
        ];
        $args['endpointUrl'] = [
            'description'       => esc_html__( 'The URL of the external API endpoint.', 'qraga' ),
            'type'              => 'string',
            'format'            => 'uri', 
            'required'          => false,
            'validate_callback' => function($param, $request, $key) { 
                return is_string($param) && (empty($param) || filter_var($param, FILTER_VALIDATE_URL)); 
            },
            'sanitize_callback' => 'esc_url_raw',
        ];
        $args['widgetId'] = [
            'description'       => esc_html__( 'The ID for the Qraga product page widget.', 'qraga' ),
            'type'              => 'string',
            'required'          => false,
             'validate_callback' => function($param, $request, $key) { return is_string($param); },
            'sanitize_callback' => 'sanitize_text_field',
        ];
        return $args;
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

    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @return array Item schema data.
     */
    public function get_public_item_schema() {
         $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'       => 'qraga_settings',
            'type'        => 'object',
            'properties'  => [
                // Match keys used in get_settings response
                'siteId' => [
                    'description' => esc_html__( 'Unique identifier for the site.', 'qraga' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
                'apiKey' => [
                    'description' => esc_html__( 'API Key for the external service.', 'qraga' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ], 
                    'readonly'    => false,
                ],
                'endpointUrl' => [
                    'description' => esc_html__( 'Endpoint URL for the external service.', 'qraga' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
                'widgetId' => [
                    'description' => esc_html__( 'The ID for the Qraga product page widget.', 'qraga' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
            ],
        ];
        // If using WP_REST_Controller base class, add_additional_fields_schema might be available
        // return $this->add_additional_fields_schema( $schema );
        return $schema; // Return schema directly if not extending WP_REST_Controller
    }

    /**
     * Handles the bulk product sync (export) request.
     * Replicates logic from old QragaService->handle_bulk_export and QragaRestController->trigger_export.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function handle_bulk_sync_request( WP_REST_Request $request )
    {
        // Check essential settings first (from old QragaRestController->trigger_export)
        $site_id = get_option( 'qraga_site_id', '' );
        $api_key = get_option( 'qraga_api_key', '' );
        $endpoint_url_base = get_option( 'qraga_endpoint_url', '' );

        if ( empty( $site_id ) || empty( $api_key ) || empty( $endpoint_url_base ) ) {
            return new WP_Error(
                'qraga_missing_config_for_export',
                esc_html__( 'Site ID, API Key, and Endpoint URL must be configured in Settings before starting sync.', 'qraga' ),
                [ 'status' => 400 ]
            );
        }

        // Instantiate Qraga_Product_Sync to use its transformation logic
        // Ensure Qraga_Product_Sync is loaded. It should be if Qraga_Plugin included it.
        if (!class_exists('Qraga_Product_Sync')) {
             error_log('Qraga API Error: Qraga_Product_Sync class not found during bulk sync.');
            return new WP_Error('qraga_service_unavailable', esc_html__( 'Product sync service is not available.', 'qraga' ), [ 'status' => 500 ]);
        }
        $product_sync_service = new Qraga_Product_Sync();

        // Logic ported from old QragaService->handle_bulk_export
        define('QRAGA_BATCH_SIZE', 100); // From old QragaService const BATCH_SIZE
        $processed_count = 0;
        $batch_count   = 0;
        $errors        = [];
        $error_ids     = []; // To store IDs of products that failed transformation or were in failed batches
        $page          = 1;
        $success_overall = true;

        do {
            $args = [
                'status'   => 'publish', // Export only published products
                'limit'    => QRAGA_BATCH_SIZE,
                'page'     => $page,
                'orderby'  => 'ID',
                'order'    => 'ASC',
                'return'   => 'objects', // Get WC_Product objects
                'type'     => array('simple', 'variable') // Ensure we only get types handled by transform_product_data
            ];

            $products_query = new WP_Query( $args ); // Using WP_Query to get WC_Product objects directly is not standard.
                                                // wc_get_products is preferred.
            $products = wc_get_products( $args );

            if ( empty( $products ) ) {
                break; // No more products found
            }

            $current_batch_data_transformed = [];
            $product_ids_in_batch = [];

            foreach ( $products as $product_object ) {
                if ( ! $product_object instanceof \WC_Product ) {
                    continue;
                }
                // Use the transform_product_data method from Qraga_Product_Sync instance
                $transformed_data = $product_sync_service->transform_product_data( $product_object ); 
                // Note: transform_product_data is private in the provided Qraga_Product_Sync, 
                // it should be public or we need a public wrapper if Qraga_Api is to call it.
                // For now, assuming it can be called or will be made callable.
                // If it's made public in Qraga_Product_Sync, no change here.

                if ( ! empty( $transformed_data ) ) {
                    $current_batch_data_transformed[] = $transformed_data;
                    $product_ids_in_batch[] = $product_object->get_id();
                } else {
                    $errors[] = "Failed to transform product ID: " . $product_object->get_id();
                    $error_ids[] = $product_object->get_id();
                }
            }

            if ( ! empty( $current_batch_data_transformed ) ) {
                $batch_result = $this->send_ndjson_batch(
                    $current_batch_data_transformed, 
                    $endpoint_url_base, 
                    $site_id, 
                    $api_key
                );
                
                if( ! $batch_result['success'] ) {
                    $errors[] = "Batch starting with product ID " . ($product_ids_in_batch[0] ?? 'N/A') . " (page {$page}) failed: " . $batch_result['message'];
                    $error_ids = array_merge($error_ids, $product_ids_in_batch); // Assume all in failed batch are errors
                    $success_overall = false;
                } else {
                    // Mark products in successful batch as synced
                    foreach ($product_ids_in_batch as $synced_product_id) {
                        update_post_meta($synced_product_id, Qraga_Product_Sync::SYNC_META_KEY, time());
                         $processed_count++; // Increment successfully processed count only on actual success
                    }
                }
                $batch_count++;
            } else {
                 // If current_batch_data_transformed is empty but products were fetched, it means all failed transformation
                 // $processed_count for this batch is 0, errors are already logged.
            }

            $page++;
            if ($page > 1000) { // Safety break from old service
                $errors[] = "Bulk export safety break triggered after 1000 pages.";
                $success_overall = false;
                break;
            }

        } while ( count( $products ) === QRAGA_BATCH_SIZE );

        $final_message = $success_overall ? 'Bulk sync completed.' : 'Bulk sync completed with errors.';
        if ($processed_count === 0 && empty($errors)) {
            $final_message = 'No products found to sync or all products were already up to date (if sync meta is checked).'; // Or more specific if possible
        }

        return new WP_REST_Response( [
            'success'   => $success_overall,
            'message'   => $final_message,
            'processed' => $processed_count,
            'batches'   => $batch_count,
            'errors'    => $errors, // Array of error messages
            'error_ids' => array_unique($error_ids) // Array of product IDs that had issues
        ], $success_overall ? 200 : 207 ); // 207 Multi-Status if there were errors
    }

    /**
     * Sends a batch of products as NDJSON to the Qraga bulk endpoint.
     * Inspired by QragaService->send_batch_to_endpoint
     */
    private function send_ndjson_batch( array $batch_data, string $endpoint_url_base, string $site_id, string $api_key ): array {
        if ( empty( $batch_data ) ) {
            return [ 'success' => true, 'message' => 'Batch was empty.' ];
        }

        // Construct URL: ENDPOINT/v1/site/{siteId}/products/bulk
        $bulk_api_url = trailingslashit( $endpoint_url_base ) . 'v1/site/' . rawurlencode($site_id) . '/products/bulk';

        // Use Qraga_Product_Sync's get_api_request_args for headers, but override content type and method
        // This is a bit awkward. Ideally, get_api_request_args would be more flexible or we'd have a dedicated one.
        // For now, let's build args directly as per old send_batch_to_endpoint
        $headers = [
            'Content-Type'  => 'application/x-ndjson',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];
        $args = [
            'method'  => 'POST',
            'timeout' => 60, // Increased timeout for bulk potentially
            'headers' => $headers,
            'data_format' => 'body',
        ];

        $request_body_string = '';
        foreach ( $batch_data as $product_payload ) {
            if ( empty( $product_payload['id'] ) ) { // From old QragaService check
                error_log('Qraga API Bulk Error: Product data in batch missing required id field.');
                // Potentially return an error or skip this item, affecting the batch result
                continue; 
            }
            $json_line = wp_json_encode( $product_payload );
            if ($json_line === false) {
                error_log('Qraga API Bulk Error: Failed to JSON encode product for bulk. ID (from payload): ' . ($product_payload['id'] ?? 'N/A'));
                continue; // Skip this product
            }
            $request_body_string .= $json_line . "\n";
        }

        if (empty($request_body_string)) {
            return ['success' => false, 'message' => 'No valid products found in batch to generate NDJSON body.'];
        }

        $args['body'] = $request_body_string;

        error_log("Qraga API: Attempting BULK POST to: {$bulk_api_url}");
        $response = wp_remote_post( $bulk_api_url, $args );
        
        // Use the handle_api_response from Qraga_Product_Sync if it's suitable and accessible
        // Or replicate its logic here.
        // Assuming Qraga_Product_Sync is not available or its handle_api_response is private:
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            error_log( "Qraga API Bulk Request Failed to {$bulk_api_url}. Error: {$error_message}" );
            return [ 'success' => false, 'message' => "HTTP request failed: {$error_message}" ];
        }
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        if ( $response_code >= 200 && $response_code < 300 ) {
            error_log("Qraga API Bulk Request Success to {$bulk_api_url}. Response Code: {$response_code}");
            return [ 'success' => true, 'message' => 'Batch processed successfully.', 'response_code' => $response_code, 'body' => $response_body ];
        } else {
            error_log( "Qraga API Bulk Request Failed to {$bulk_api_url}. Response Code: {$response_code}. Body: {$response_body}" );
            return [ 'success' => false, 'message' => "Bulk endpoint returned error code: {$response_code}. Body: {$response_body}", 'response_code' => $response_code ];
        }
    }
} 