<?php

namespace Qraga;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Qraga REST API Controller
 *
 * Registers REST API endpoints for Qraga settings and actions.
 */
class QragaRestController extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'qraga/v1';

	/**
	 * QragaService instance.
	 *
	 * @var Service\QragaService|null
	 */
	private $service = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Ensure the service class is available
		$service_file = plugin_dir_path( __DIR__ ) . 'Service/QragaService.php'; // Use __DIR__ for reliability
		if ( ! class_exists( \Qraga\Service\QragaService::class, false ) && file_exists( $service_file ) ) {
			include_once $service_file;
		}
		if ( class_exists( \Qraga\Service\QragaService::class ) ) {
			$this->service = new Service\QragaService();
		} else {
            error_log('Qraga Error: QragaService class not found for REST Controller.');
        }
	}

	/**
	 * Register the routes for the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/settings',
			[
				// Get settings
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [],
				],
				// Save settings
				[
					'methods'             => WP_REST_Server::CREATABLE, // Using CREATABLE for POST
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => $this->get_settings_endpoint_args(),
				],
				'schema' => [ $this, 'get_public_item_schema' ], // Optional: Define schema
			]
		);

		register_rest_route(
			$this->namespace,
			'/export',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE, // Using CREATABLE for POST
					'callback'            => [ $this, 'trigger_export' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [], // No specific args needed to trigger export
				],
			]
		);
	}

	/**
	 * Check if a given request has appropriate permissions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
	 */
	public function permissions_check( $request ) {
		// Use 'manage_options' for settings, or a more specific capability if created.
		// 'manage_woocommerce' is also a good option if only admins dealing with WC should access this.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Sorry, you do not have permission to manage Qraga settings or actions.', 'qraga' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_settings( $request ) {
		$settings = [
			'siteId'      => get_option( 'qraga_site_id', '' ),
			'apiKey'      => get_option( 'qraga_api_key', '' ),
			'endpointUrl' => get_option( 'qraga_endpoint_url', '' ),
			'widgetId'    => get_option( 'qraga_widget_id', '' ),
		];
		// Note: Don't return the actual API key in GET requests if it should remain secret on reload.
		// Consider returning a placeholder or only checking if it's set.
		// However, for the UI to display it (even as password type), we need to send it.
		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Saves the plugin settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function save_settings( $request ) {
		$site_id = null;
		$api_key = null;
		$endpoint_url = null;
		$widget_id = null;

		// Update values only if they are present in the request
		// This allows partial updates (e.g., just saving widgetId)
		if ( isset( $request['siteId'] ) ) {
			$site_id = sanitize_text_field( $request['siteId'] );
		}
		if ( isset( $request['apiKey'] ) ) {
			$api_key = sanitize_text_field( $request['apiKey'] );
		}
		if ( isset( $request['endpointUrl'] ) ) {
			$endpoint_url = esc_url_raw( $request['endpointUrl'] );
		}
		if ( isset( $request['widgetId'] ) ) {
			// Allow empty string to disable widget, sanitize otherwise
			$widget_id = sanitize_text_field( trim( $request['widgetId'] ) );
		}

		// Apply updates
		$updated = false;
		if ( ! is_null( $site_id ) ) {
			update_option( 'qraga_site_id', $site_id );
			$updated = true;
		}
		if ( ! is_null( $api_key ) ) {
			update_option( 'qraga_api_key', $api_key );
			$updated = true;
		}
		if ( ! is_null( $endpoint_url ) ) {
			// Add validation for URL format if endpointUrl is being updated
			if ( ! filter_var( $endpoint_url, FILTER_VALIDATE_URL ) && ! empty( $endpoint_url ) ) {
				return new WP_Error(
					'qraga_invalid_url',
					esc_html__( 'Invalid Endpoint URL format.', 'qraga' ),
					[ 'status' => 400 ]
				);
			}
			update_option( 'qraga_endpoint_url', $endpoint_url );
			$updated = true;
		}
		if ( ! is_null( $widget_id ) ) {
			update_option( 'qraga_widget_id', $widget_id );
			$updated = true;
		}

		// Basic validation: If core settings are being set for the first time, ensure they are not empty
		// This is less strict than before, allowing partial updates.
		// Consider adding validation based on which fields are *actually* being updated.
		// $current_site_id = get_option('qraga_site_id');
		// if ( empty($current_site_id) && (is_null($site_id) || empty($site_id) ) ) {
		// 	// Handle error: Site ID is required if not already set.
		// }

		if ( ! $updated ) {
			// Nothing was sent to update?
			return new WP_Error(
				'qraga_no_data',
				esc_html__( 'No settings data provided to update.', 'qraga' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Triggers the bulk export process.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function trigger_export( $request ) {
        if ( ! $this->service ) {
            return new WP_Error(
                'qraga_service_unavailable',
                esc_html__( 'Qraga service is not available.', 'qraga' ),
                [ 'status' => 500 ]
            );
        }

		// Check essential settings before allowing export
		$site_id = get_option( 'qraga_site_id', '' );
		$api_key = get_option( 'qraga_api_key', '' );
		$endpoint_url = get_option( 'qraga_endpoint_url', '' );

		if ( empty( $site_id ) || empty( $api_key ) || empty( $endpoint_url ) ) {
			return new WP_Error(
				'qraga_missing_config_for_export',
				esc_html__( 'Site ID, API Key, and Endpoint URL must be configured in Settings before starting sync.', 'qraga' ),
				[ 'status' => 400 ]
			);
		}

		// Note: Bulk export can take time. Consider async processing.
		try {
			$result = $this->service->handle_bulk_export();
            $result['success'] = empty($result['errors']);
			return new WP_REST_Response( $result, 200 );
		} catch ( \Exception $e ) {
            error_log('Qraga Bulk Export Exception: ' . $e->getMessage());
			return new WP_Error(
				'qraga_export_failed',
				esc_html__( 'An unexpected error occurred during the sync.', 'qraga' ),
				[ 'status' => 500, 'details' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Get the arguments for the settings endpoint.
	 *
	 * @return array
	 */
	protected function get_settings_endpoint_args() {
		$args = [];
		$args['siteId'] = [
			'description'       => esc_html__( 'The unique site identifier.', 'qraga' ),
			'type'              => 'string',
			'required'          => false,
			'sanitize_callback' => 'sanitize_text_field',
		];
		$args['apiKey'] = [
			'description'       => esc_html__( 'The API Key for the external service.', 'qraga' ),
			'type'              => 'string',
			'required'          => false,
			'sanitize_callback' => 'sanitize_text_field',
		];
		$args['endpointUrl'] = [
			'description'       => esc_html__( 'The URL of the external API endpoint.', 'qraga' ),
			'type'              => 'string',
			'format'            => 'uri', // Helps with validation
			'required'          => false,
			'sanitize_callback' => 'esc_url_raw',
		];
		// Add args for widgetId
		$args['widgetId'] = [
			'description'       => esc_html__( 'The ID for the Qraga product page widget.', 'qraga' ),
			'type'              => 'string',
			'required'          => false, // Not always required, can be updated separately
			'sanitize_callback' => 'sanitize_text_field',
		];
		return $args;
	}

	/**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @return array Item schema data.
     */
    public function get_public_item_schema() {
        $schema = [
            '\$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'       => 'qraga_settings',
            'type'        => 'object',
            'properties'  => [
                'siteId' => [
                    'description' => esc_html__( 'Unique identifier for the site.', 'qraga' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => false,
                ],
                'apiKey' => [
                    'description' => esc_html__( 'API Key for the external service.', 'qraga' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ], // Be cautious about sending API keys back in 'view' context
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
        return $this->add_additional_fields_schema( $schema );
    }
} 