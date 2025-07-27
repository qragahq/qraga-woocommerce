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

    /**
     * Unique hook name for the batch processing action.
     */
    const BATCH_PROCESS_HOOK = 'qraga_process_sync_batch';
    const DEFAULT_BATCH_SIZE = 50; // Define batch size
    const ACTIVE_JOB_ID_OPTION = 'qraga_active_bulk_export_job_id'; // New constant

    public function __construct()
    {
        add_action('rest_api_init', function () {
            // Settings endpoints - using QRAGA_REST_API_ROUTE
            register_rest_route(QRAGA_REST_API_ROUTE, '/settings/', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_settings'),
                    'permission_callback' => array($this, 'get_permission'),
                    'args'                => array(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'set_settings'),
                    'permission_callback' => array($this, 'get_permission'),
                    'args'                => $this->get_settings_endpoint_args(),
                ),
                 'schema' => array($this, 'get_public_item_schema'),
            ));

            // Example: License Endpoint - using QRAGA_REST_API_ROUTE 
            register_rest_route(QRAGA_REST_API_ROUTE, '/license/', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_license_example'),
                'permission_callback' => array($this, 'get_permission')
            ));
            
            // Bulk Sync Trigger Endpoint
            register_rest_route(QRAGA_REST_API_ROUTE, '/export/', array( 
                'methods'             => WP_REST_Server::CREATABLE, 
                'callback'            => array($this, 'handle_bulk_export_trigger'),
                'permission_callback' => array($this, 'get_permission')
            ));

            // Bulk Sync Status Endpoint
            register_rest_route(QRAGA_REST_API_ROUTE, '/export/status/(?P<job_id>[a-zA-Z0-9_.-]+)', array( 
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_bulk_export_status'),
                'permission_callback' => array($this, 'get_permission'),
                'args'                => [
                    'job_id' => [
                        'description' => 'The unique ID of the export job.',
                        'type'        => 'string',
                        'required'    => true,
                        'validate_callback' => function($param, $request, $key) {
                            return is_string($param) && preg_match('/^[a-zA-Z0-9_.-]+$/', $param);
                        }
                    ]
                ]
            ));

            // Endpoint to get current/last active job status on page load
            register_rest_route(QRAGA_REST_API_ROUTE, '/export/current-job', array( 
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_current_active_job_status'),
                'permission_callback' => array($this, 'get_permission')
            ));
        });
        
        // Add the hook for Action Scheduler to call our batch processor method
        add_action(self::BATCH_PROCESS_HOOK, [$this, 'process_sync_batch'], 10, 2);
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
            $prefix . 'site_id',       
            $prefix . 'api_key',       
            $prefix . 'region',
            $prefix . 'api_version',
            $prefix . 'widget_id',     
        );
        return $options;
    }

    /**
     * Helper function to compute the endpoint URL based on region and API version
     * 
     * @param string $region
     * @param string $apiVersion
     * @return string
     */
    public static function compute_endpoint_url( $region = '', $apiVersion = '' ) {
        // Check for environment variable override (for development)
        $envUrl = getenv('QRAGA_API_URL');
        if ( !empty( $envUrl ) ) {
            return rtrim( $envUrl, '/' );
        }
        
        if ( empty( $region ) ) {
            $region = get_option( 'qraga_region', 'US' );
        }
        if ( empty( $apiVersion ) ) {
            $apiVersion = get_option( 'qraga_api_version', 'v1' );
        }
        
        // Map region to base URL
        $regionUrls = [
            'US' => 'https://api-us.qraga.com'
        ];
        
        $baseUrl = isset($regionUrls[$region]) ? $regionUrls[$region] : $regionUrls['US'];
        return $baseUrl . '/' . $apiVersion;
    }

    public function get_settings(WP_REST_Request $request)
    {
        $region = get_option( 'qraga_region', 'US' );
        $apiVersion = get_option( 'qraga_api_version', 'v1' );
        $endpointUrl = self::compute_endpoint_url( $region, $apiVersion );
        
        $settings = [
            'siteId'      => get_option( 'qraga_site_id', '' ),
            'apiKey'      => get_option( 'qraga_api_key', '' ),
            'region'      => $region,
            'apiVersion'  => $apiVersion,
            'endpointUrl' => $endpointUrl,
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
            update_option( 'qraga_api_key', sanitize_text_field( $params['apiKey'] ) );
            $updated = true;
        }
        if ( isset( $params['region'] ) ) {
            $region = sanitize_text_field( $params['region'] );
            // Validate region
            $allowedRegions = ['US'];
            if ( ! in_array( $region, $allowedRegions ) ) {
                return new WP_Error(
                    'qraga_invalid_region',
                    esc_html__( 'Invalid region. Only US is currently supported.', 'qraga' ),
                    [ 'status' => 400 ]
                );
            }
            update_option( 'qraga_region', $region );
            $updated = true;
        }
        if ( isset( $params['apiVersion'] ) ) {
            $apiVersion = sanitize_text_field( $params['apiVersion'] );
            // Validate API version
            $allowedVersions = ['v1'];
            if ( ! in_array( $apiVersion, $allowedVersions ) ) {
                return new WP_Error(
                    'qraga_invalid_api_version',
                    esc_html__( 'Invalid API version. Only v1 is currently supported.', 'qraga' ),
                    [ 'status' => 400 ]
                );
            }
            update_option( 'qraga_api_version', $apiVersion );
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
            'validate_callback' => function($param, $request, $key) { return is_string($param); },
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
                'region' => [
                    'description' => esc_html__( 'Service region for the Qraga API.', 'qraga' ),
                    'type'        => 'string',
                    'enum'        => [ 'US' ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
                'apiVersion' => [
                    'description' => esc_html__( 'API version for the Qraga service.', 'qraga' ),
                    'type'        => 'string',
                    'enum'        => [ 'v1' ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
                'endpointUrl' => [
                    'description' => esc_html__( 'Computed endpoint URL for the external service.', 'qraga' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'widgetId' => [
                    'description' => esc_html__( 'The ID for the Qraga product page widget.', 'qraga' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
            ],
        ];
        return $schema;
    }

    /**
     * Handles the initial request to trigger a bulk export.
     * Schedules the background job using Action Scheduler.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function handle_bulk_export_trigger( WP_REST_Request $request )
    {
        $active_job_id = get_option(self::ACTIVE_JOB_ID_OPTION);
        if ($active_job_id) {
            $active_job_transient_key = 'qraga_job_' . $active_job_id;
            $active_job_status = get_transient($active_job_transient_key);

            if ($active_job_status && isset($active_job_status['status'])) {
                $allowed_active_statuses = ['queued', 'processing', 'error_scheduling_next'];
                if (in_array($active_job_status['status'], $allowed_active_statuses, true)) {
                    // error_log("Qraga Bulk Export Trigger: Found active job {$active_job_id} with status {$active_job_status['status']}. Returning its status.");
                    return new WP_REST_Response( [
                        'status'          => 'active_job_found',
                        'message'         => sprintf(esc_html__( 'An existing sync job (ID: %s) is currently %s.', 'qraga' ), $active_job_id, $active_job_status['status']),
                        'job_id'          => $active_job_id,
                        'job_details'     => $active_job_status
                    ], 200 );
                } else {
                    // error_log("Qraga Bulk Export Trigger: Found stale active job ID {$active_job_id} (status: {$active_job_status['status']}). Clearing option and proceeding.");
                    delete_option(self::ACTIVE_JOB_ID_OPTION);
                }
            } else {
                // error_log("Qraga Bulk Export Trigger: Found active job ID {$active_job_id} but its transient is missing. Clearing option and proceeding.");
                delete_option(self::ACTIVE_JOB_ID_OPTION);
            }
        }

        if ( ! function_exists('as_schedule_single_action') ) {
            // error_log('Qraga Error: Action Scheduler function as_schedule_single_action() not found. Is Action Scheduler active?');
            return new WP_Error(
                'action_scheduler_missing',
                esc_html__( 'Required background processing library (Action Scheduler) is not available.', 'qraga' ),
                [ 'status' => 501 ] // Not Implemented / Service Unavailable
            );
        }

        $site_id = get_option( 'qraga_site_id', '' );
        $api_key = get_option( 'qraga_api_key', '' );
        $endpoint_url_base = get_option( 'qraga_endpoint_url', '' );
        if ( empty( $site_id ) || empty( $api_key ) || empty( $endpoint_url_base ) ) {
            return new WP_Error('qraga_missing_config_for_export', esc_html__( 'Site ID, API Key, and Endpoint URL must be configured in Settings before starting sync.', 'qraga' ), [ 'status' => 400 ] );
        }

        $query_args = [
            'status'   => 'publish',
            'limit'    => -1, // Count all
            'return'   => 'ids', // Only need count
            'type'     => array('simple', 'variable') 
        ];
        $total_products = count(wc_get_products( $query_args ));

        if ($total_products === 0) {
             return new WP_REST_Response( [
                'status'   => 'complete',
                'message' => 'No products found to sync.',
                'total_products' => 0
            ], 200 );
        }

        $job_id = 'qraga_export_' . uniqid();
        $initial_status = [
            'job_id'         => $job_id,
            'status'         => 'queued',
            'total'          => $total_products,
            'processed'      => 0,
            'batches'        => 0,
            'start_time'     => time(),
            'errors'         => [],
            'error_ids'      => []
        ];

        set_transient('qraga_job_' . $job_id, $initial_status, DAY_IN_SECONDS);

        $action_id = as_schedule_single_action(
            time(),
            self::BATCH_PROCESS_HOOK, 
            [
                'job_id' => $job_id,
                'page'   => 1
            ],
            'qraga_bulk_export'
        );

        if ($action_id === 0) {
            // error_log("Qraga Error: Failed to schedule first batch using Action Scheduler for Job ID: {$job_id}");
             delete_transient('qraga_job_' . $job_id);
            return new WP_Error(
                'scheduler_failed',
                esc_html__( 'Failed to schedule the background synchronization task.', 'qraga' ),
                [ 'status' => 500 ]
            );
        }
        
        update_option(self::ACTIVE_JOB_ID_OPTION, $job_id);
        // error_log("Qraga Bulk Export: Queued Job ID {$job_id} with Action ID {$action_id} for {$total_products} products. Set as active job.");

        return new WP_REST_Response( [
            'status'   => 'queued',
            'message' => 'Bulk synchronization job queued successfully.',
            'job_id'   => $job_id,
            'total_products' => $total_products
        ], 202 ); // 202 Accepted
    }

    /**
     * Processes a single batch of products for bulk synchronization via Action Scheduler.
     *
     * @param string $job_id The unique ID for this export job.
     * @param int $page The batch number (page number) to process.
     */
    public function process_sync_batch(string $job_id, int $page) {
        // error_log("Qraga Action Scheduler: Starting processing batch {$page} for Job ID: {$job_id}");

        $transient_key = 'qraga_job_' . $job_id;
        $job_status = get_transient($transient_key);

        if ($job_status === false) {
            // error_log("Qraga Action Scheduler Error: Job ID {$job_id} not found in transients. Aborting batch {$page}.");
            return; // Or throw an exception if Action Scheduler should retry
        }

        // Ensure Qraga_Product_Sync class is available and instantiated
        if (!class_exists('Qraga_Product_Sync')) {
            // error_log("Qraga Action Scheduler Error: Qraga_Product_Sync class not found. Job ID: {$job_id}, Batch: {$page}");
            $job_status['errors'][] = ['timestamp' => time(), 'message' => 'Critical: Qraga_Product_Sync class not found.'];
            $job_status['status'] = 'failed';
            set_transient($transient_key, $job_status, DAY_IN_SECONDS);
            return;
        }
        $qraga_product_sync = new Qraga_Product_Sync();

        $products_synced_in_batch = 0;
        $products_failed_in_batch = 0;
        $batch_api_errors = [];

        try {
            $query_args = [
                'status'   => 'publish',
                'limit'    => self::DEFAULT_BATCH_SIZE,
                'page'     => $page,
                'return'   => 'objects',
                'type'     => array('simple', 'variable'),
                'orderby'  => 'ID', // Consistent ordering
                'order'    => 'ASC',
            ];
            $products = wc_get_products( $query_args );

            if ( !empty( $products ) ) {
                $transformed_products_payload = [];
                foreach ( $products as $product ) {
                    if ( ! $product instanceof WC_Product ) continue;
                    $transformed_data = $qraga_product_sync->transform_product_data( $product );
                    if ( !empty( $transformed_data ) ) {
                        $transformed_products_payload[] = $transformed_data;
                    } else {
                        // error_log("Qraga Batch Sync: Failed to transform product ID {$product->get_id()} for Job {$job_id}");
                        $job_status['errors'][] = ['timestamp' => time(), 'product_id' => $product->get_id(), 'message' => 'Failed to transform product data.'];
                        $job_status['error_ids'][] = $product->get_id();
                        $products_failed_in_batch++;
                    }
                }

                if ( !empty( $transformed_products_payload ) ) {
                    $api_response = $qraga_product_sync->send_ndjson_batch( $transformed_products_payload );
                    $products_synced_in_batch = count( $transformed_products_payload ); // Assume all sent were attempted

                    if ( !$api_response['success'] ) {
                        // error_log("Qraga Batch Sync API Error: Job ID {$job_id}, Batch {$page}. Message: " . ($api_response['message'] ?? 'Unknown API error'));
                        $batch_api_errors[] = 'API Error on batch send: ' . ($api_response['message'] ?? 'Unknown error') . (isset($api_response['response_code']) ? ' (Code: ' . $api_response['response_code'] . ')' : '');
                        // Potentially log product IDs from this batch if API can return per-item errors in bulk
                        // For now, mark all in this specific API call as potentially problematic in logs.
                        foreach ($transformed_products_payload as $p_data) {
                             $job_status['error_ids'][] = str_replace('prod-wc-', '', $p_data['id']); // Store original WC ID
                        }
                    } else {
                        // Check response body for partial errors if applicable, based on Qraga API design
                        // For example, if the API returns a 207 Multi-Status or similar
                        // $body = json_decode($api_response['body'], true);
                        // if ($body && isset($body['errors'])) { ... parse $body['errors'] ... }
                    }
                } else if ($products_failed_in_batch > 0 && empty($transformed_products_payload)){
                     // error_log("Qraga Batch Sync: All products in batch {$page} for job {$job_id} failed transformation. Nothing sent to API.");
                } else {
                    // error_log("Qraga Batch Sync: No products transformed in batch {$page} for job {$job_id}, though products were queried. Products count: " . count($products));
                }

                $job_status['processed'] += count($products); // All queried products are now "processed"
                $job_status['batches']++;
                if (!empty($batch_api_errors)) {
                    $job_status['errors'][] = ['timestamp' => time(), 'batch' => $page, 'messages' => $batch_api_errors];
                }
                // Deduplicate error_ids before saving
                if (!empty($job_status['error_ids'])) {
                    $job_status['error_ids'] = array_values(array_unique($job_status['error_ids']));
                }

                set_transient($transient_key, $job_status, DAY_IN_SECONDS);

                if ( count( $products ) === self::DEFAULT_BATCH_SIZE ) {
                    // More products likely exist, schedule next batch
                    $next_page = $page + 1;
                    $action_id = as_schedule_single_action(
                        time(), // Schedule ASAP
                        self::BATCH_PROCESS_HOOK, 
                        ['job_id' => $job_id, 'page' => $next_page],
                        'qraga_bulk_export'
                    );
                    if ($action_id === 0) {
                        // error_log("Qraga Batch Sync Error: Failed to schedule next batch ({$next_page}) for Job ID {$job_id}. Current batch {$page} processed.");
                        $job_status['errors'][] = ['timestamp' => time(), 'message' => "Critical: Failed to schedule next batch {$next_page}. Manual restart may be needed after this batch."];
                        $job_status['status'] = 'error_scheduling_next'; // Custom status
                        set_transient($transient_key, $job_status, DAY_IN_SECONDS);
                    } else {
                        // error_log("Qraga Batch Sync: Successfully processed batch {$page} for Job {$job_id}. Next batch {$next_page} scheduled with Action ID {$action_id}.");
                    }
                } else {
                    $job_status['status'] = 'completed';
                    $job_status['end_time'] = time();
                    set_transient($transient_key, $job_status, DAY_IN_SECONDS);
                    delete_option(self::ACTIVE_JOB_ID_OPTION);
                    // error_log("Qraga Batch Sync: Completed all batches for Job ID {$job_id}. Cleared active job ID. Total processed: {$job_status['processed']}. Batches: {$job_status['batches']}.");
                }
            } else {
                $job_status['status'] = 'completed';
                $job_status['end_time'] = time();
                set_transient($transient_key, $job_status, DAY_IN_SECONDS);
                delete_option(self::ACTIVE_JOB_ID_OPTION);
                // error_log("Qraga Batch Sync: No more products found for Job ID {$job_id} at page {$page}. Marking as complete. Cleared active job ID. Total processed: {$job_status['processed']}. Batches: {$job_status['batches']}.");
            }
        } catch (Throwable $e) {
            // error_log("Qraga Action Scheduler FATAL Error: Job ID {$job_id}, Batch {$page}. Message: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            $job_status['status'] = 'failed';
            $job_status['errors'][] = ['timestamp' => time(), 'message' => 'Fatal error during batch processing: ' . $e->getMessage()];
            if (isset($job_status['end_time'])) unset($job_status['end_time']);
            set_transient($transient_key, $job_status, DAY_IN_SECONDS);
            // Only delete the active job ID option if we are sure this job won't be retried by Action Scheduler
            // For now, to be safe, let's assume a fatal error means it might be stuck.
            // A manual clear or a timeout on the option might be needed for true "stuck" jobs.
            // However, if Action Scheduler marks it as definitively failed and stops retries, then we should clear it.
            // This part is tricky without knowing Action Scheduler's exact retry and failure lifecycle for this action.
            // Let's clear it on 'failed' status if we are setting it here definitively.
            delete_option(self::ACTIVE_JOB_ID_OPTION); 
            // error_log("Qraga Batch Sync: Job ID {$job_id} failed. Cleared active job ID option due to fatal error.");
            throw $e;
        }
    }

    /**
     * Gets the status of a running or completed bulk export job.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_bulk_export_status( WP_REST_Request $request ) {
        $job_id = $request->get_param('job_id');
        $transient_key = 'qraga_job_' . $job_id;
        $status_data = get_transient($transient_key);

        if ($status_data === false) {
             return new WP_Error(
                'job_not_found',
                esc_html__( 'Export job not found or has expired.', 'qraga' ),
                [ 'status' => 404 ]
            );
        }

        return new WP_REST_Response($status_data, 200);
    }

    /**
     * Gets the status of a potentially active bulk export job.
     * Primarily queries Action Scheduler for a pending/in-progress job in our group,
     * then validates its status against our transient data.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_current_active_job_status( WP_REST_Request $request ) {
        $active_statuses_for_job_transient = ['queued', 'processing', 'error_scheduling_next']; // Our transient statuses considered active

        if (function_exists('as_get_scheduled_actions') && class_exists('ActionScheduler_Store') && class_exists('ActionScheduler')) {
            $as_query_args = [
                'group'     => 'qraga_bulk_export',
                'status'    => ['pending', 'in-progress'], 
                'orderby'   => 'date', 
                'order'     => 'DESC',
                'per_page'  => 1, // Get the most recent one
            ];
            $action_ids = as_get_scheduled_actions($as_query_args, 'ids');

            if (!empty($action_ids)) {
                $action_id = $action_ids[0]; 
                $action = ActionScheduler_Store::instance()->fetch_action($action_id);
                
                if ($action && $action instanceof WC_Action && $action->get_id() === $action_id ) { 
                    $action_args = $action->get_args();
                    if (isset($action_args['job_id'])) {
                        $discovered_job_id = $action_args['job_id'];
                        $transient_key = 'qraga_job_' . $discovered_job_id;
                        $status_data_from_transient = get_transient($transient_key);
                        
                        // Validate this discovered job's transient is valid and active from our plugin's perspective
                        if ($status_data_from_transient && 
                            isset($status_data_from_transient['status']) && 
                            in_array($status_data_from_transient['status'], $active_statuses_for_job_transient, true) &&
                            isset($status_data_from_transient['job_id']) && $status_data_from_transient['job_id'] === $discovered_job_id // Sanity check job_id in transient
                        ) {
                            // Found an active job via Action Scheduler that our transient agrees is active.
                            // Ensure our main option is synced with this discovered active job.
                            update_option(self::ACTIVE_JOB_ID_OPTION, $discovered_job_id);
                            // error_log("Qraga Current Job Status: Confirmed active job {$discovered_job_id} via Action Scheduler group query. Option updated. Returning its transient data.");
                            return new WP_REST_Response($status_data_from_transient, 200);
                        } else {
                            // Action Scheduler has an action, but our transient is missing, stale, or doesn't match.
                            // This could mean our plugin thinks the job is done/failed, or the transient expired.
                            // Or job_id in transient does not match discovered_job_id.
                            $log_message = "Qraga Current Job Status: Discovered action ID {$action_id} (job ID {$discovered_job_id} from args) via AS group, ";
                            if (!$status_data_from_transient) {
                                $log_message .= "but its transient was missing.";
                            } elseif (!isset($status_data_from_transient['status']) || !in_array($status_data_from_transient['status'], $active_statuses_for_job_transient, true)) {
                                $log_message .= "but its transient status was not considered active (status: " . ($status_data_from_transient['status'] ?? 'N/A') . ").";
                            } elseif (!isset($status_data_from_transient['job_id']) || $status_data_from_transient['job_id'] !== $discovered_job_id) {
                                $log_message .= "but its transient job_id ('" . ($status_data_from_transient['job_id'] ?? 'null') ."') did not match the action argument job_id.";
                            }
                             // error_log($log_message . " Treating as no active job for display.");
                            if (get_option(self::ACTIVE_JOB_ID_OPTION) === $discovered_job_id) {
                                delete_option(self::ACTIVE_JOB_ID_OPTION);
                            }
                        }
                    } else {
                         // error_log("Qraga Current Job Status: Action {$action_id} from AS group 'qraga_bulk_export' missing 'job_id' arg.");
                    }
                } else {
                    // error_log("Qraga Current Job Status: Failed to fetch valid action details for action ID {$action_id} from AS group query.");
                }
            }
        } else {
            // error_log("Qraga Current Job Status: Action Scheduler functions not available for querying.");
        }

        // If we reach here, no definitively active job (validated by our transient) was found.
        // Fallback: check the option one last time in case AS query was unavailable or didn't find anything, 
        // but a very recently set option (e.g. by trigger button) might be valid.
        $active_job_id_from_option = get_option(self::ACTIVE_JOB_ID_OPTION);
        if ($active_job_id_from_option) {
            $transient_key_option = 'qraga_job_' . $active_job_id_from_option;
            $status_data_option = get_transient($transient_key_option);
            if ($status_data_option && 
                isset($status_data_option['status']) && 
                in_array($status_data_option['status'], $active_statuses_for_job_transient, true) && 
                isset($status_data_option['job_id']) && $status_data_option['job_id'] === $active_job_id_from_option
            ) {
                 // error_log("Qraga Current Job Status: Using job {$active_job_id_from_option} from option as fallback.");
                 return new WP_REST_Response( $status_data_option, 200 );
            } else if ($status_data_option) {
                 // Transient exists but not active, or job_id mismatch, or stale. Option is bad.
                delete_option(self::ACTIVE_JOB_ID_OPTION);
                // error_log("Qraga Current Job Status: Fallback check for option {$active_job_id_from_option} found its transient stale/inactive. Cleared option.");
            } else {
                // Option exists, transient gone. Option is bad.
                delete_option(self::ACTIVE_JOB_ID_OPTION);
                // error_log("Qraga Current Job Status: Fallback check for option {$active_job_id_from_option} found its transient missing. Cleared option.");
            }
        }

        return new WP_REST_Response( ['status' => 'no_active_job', 'message' => esc_html__('No active export job found.', 'qraga')], 200 );
    }

    // send_ndjson_batch method is now REMOVED.
    // Its logic will be placed in Qraga_Product_Sync.

} 