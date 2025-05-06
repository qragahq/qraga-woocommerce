<?php

namespace Qraga\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Qraga Service class.
 *
 * Handles product data transformation and synchronization logic.
 */
class QragaService {

	const BATCH_SIZE = 100;

	/**
	 * Transforms and handles saving/updating a product.
	 *
	 * @param int $product_id The ID of the product being saved.
	 * @param \WC_Product $product The product object.
	 */
	public function handle_product_save( $product_id, $product ) {
		$transformed_data = $this->transform_product_data( $product );
		$product_identifier = 'prod-wc-' . $product_id; // Use consistent ID format

		if ( !empty( $transformed_data ) ) {

			$is_update = get_post_meta( $product_id, '_qraga_synced', true );

			if ( $is_update ) {
				// Update existing product: PUT /products/{id}
				$result = $this->send_single_product( $transformed_data, $product_identifier, 'PUT' );
			} else {
				// Create new product: POST /products
				$result = $this->send_single_product( $transformed_data, null, 'POST' );
			}

			if ( $result['success'] ) {
				// Mark as synced after successful operation
				update_post_meta( $product_id, '_qraga_synced', time() );
				error_log('Qraga Product Sync Success (' . ( $is_update ? 'Update' : 'Create' ) . '): ' . $product_identifier);
			} else {
				error_log('Qraga Product Sync Failed (' . ( $is_update ? 'Update' : 'Create' ) . '): ' . $product_identifier . ' - Error: ' . $result['message']);
			}

		} else {
			error_log('Qraga Product Save/Update: Failed to transform product ID ' . $product_id);
		}
	}

	/**
	 * Handles deleting/trashing a product.
	 *
	 * @param int $post_id The ID of the post (product) being deleted/trashed.
	 */
	public function handle_product_delete( $post_id ) {
		if ( get_post_type( $post_id ) !== 'product' ) {
			return;
		}

		$product_identifier = 'prod-wc-' . $post_id; // Use the same ID format
		$site_id = get_option( 'qraga_site_id', '' );
		$base_url = get_option( 'qraga_endpoint_url', '' ); // Get base URL only

		if ( empty( $site_id ) || empty( $base_url ) ) {
			error_log('Qraga Delete Error: Site ID or Endpoint URL not configured.');
			return;
		}

		$url = trailingslashit( $base_url ) . 'v1/site/' . $site_id . '/products/' . $product_identifier;

		$args = $this->get_api_request_args('DELETE');
		if (!is_array($args)) { // get_api_request_args returns error string if config incomplete
			error_log("Qraga Delete Error for {$product_identifier}: {$args}");
			return;
		}

		error_log("Qraga Attempting DELETE: {$url}");
		$response = wp_remote_request( $url, $args ); // Use wp_remote_request for DELETE

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( "Qraga Delete Failed for {$product_identifier}. Error: {$error_message}" );
			// Handle error, maybe retry or notify admin
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code >= 200 && $response_code < 300 ) {
				error_log("Qraga Delete Success for {$product_identifier}. Response Code: {$response_code}");
				// Also remove the sync meta on successful delete
				delete_post_meta( $post_id, '_qraga_synced' );
			} else {
				$response_body = wp_remote_retrieve_body( $response );
				error_log( "Qraga Delete Failed for {$product_identifier}. Response Code: {$response_code}. Body: {$response_body}" );
				// Handle error
			}
		}
	}

	/**
	 * Handles bulk export of WooCommerce products to an external endpoint.
	 * Uses POST to /v1/site/{siteId}/products/bulk
	 *
	 * @return array Result summary
	 */
	public function handle_bulk_export() {
		$batch         = [];
		$processed_count = 0;
		$batch_count   = 0;
		$errors        = [];
		$page          = 1;

		do {
			$args = [
				'status'   => 'publish', // Export only published products (adjust if needed)
				'limit'    => self::BATCH_SIZE, // Use batch size as query limit for efficiency
				'page'     => $page,
				'orderby'  => 'ID',
				'order'    => 'ASC',
				'return'   => 'objects', // Get WC_Product objects
			];

			$products = wc_get_products( $args );

			if ( empty( $products ) ) {
				break; // No more products found
			}

			$current_batch_data = [];
			foreach ( $products as $product ) {
				if ( ! $product instanceof \WC_Product ) {
					continue;
				}
				$transformed_data = $this->transform_product_data( $product );
				if ( ! empty( $transformed_data ) ) {
					$current_batch_data[] = $transformed_data;
					$processed_count++;
				}
			}

			// Send the batch if it contains data
			if ( ! empty( $current_batch_data ) ) {
				$batch_result = $this->send_batch_to_endpoint( $current_batch_data );
				if( ! $batch_result['success'] ) {
					$errors[] = "Batch starting page {$page} failed: " . $batch_result['message'];
				}
				if ($batch_result['success']) {
					// Mark products in successful batch as synced
					foreach ($products as $product) {
						if (isset($product) && $product instanceof \WC_Product && in_array($this->transform_product_data($product), $current_batch_data)) {
							update_post_meta($product->get_id(), '_qraga_synced', time());
						}
					}
				}
				$batch_count++;
			}

			$page++;

			// Optional: Add a small sleep or check for stop signals in long-running processes
			// sleep(1);
			// Prevent potential infinite loops if wc_get_products has issues
			if ($page > 1000) { // Safety break after 1000 pages (100k products with batch size 100)
				$errors[] = "Bulk export safety break triggered after 1000 pages.";
				break;
			}


		} while ( count( $products ) === self::BATCH_SIZE ); // Continue if the last query returned a full batch

		return [
			'success'   => empty($errors),
			'processed' => $processed_count,
			'batches'   => $batch_count,
			'errors'    => $errors
		];
	}

	/**
	 * Uses POST to /v1/site/{siteId}/products/bulk with Content-Type: application/x-ndjson
	 *
	 * @param array $batch Array of product data objects.
	 * @return array Result status
	 */
	private function send_batch_to_endpoint( array $batch ) {
		if ( empty( $batch ) ) {
			return [ 'success' => true ];
		}

		$site_id = get_option( 'qraga_site_id', '' );
		$base_url = get_option( 'qraga_endpoint_url', '' );

		// Construct URL: ENDPOINT/v1/site/{siteId}/products/bulk
		$url = trailingslashit( $base_url ) . 'v1/site/' . $site_id . '/products/bulk';

		$args = $this->get_api_request_args('POST', 'application/x-ndjson'); // Specify Content-Type
		if (!is_array($args)) { // Config incomplete
			return [ 'success' => false, 'message' => $args ];
		}

		$request_body_string = '';
		foreach ( $batch as $product_data ) {
            if ( empty( $product_data['id'] ) ) {
                error_log('QragaService Error: Product data in batch missing required id field for bulk action.');
                continue; // Skip this product
            }
            $source_line = json_encode( $product_data );

            if ($source_line === false) {
                error_log('QragaService Error: Failed to JSON encode source for product ID ' . $product_data['id']);
                continue; // Skip this product
            }

            $request_body_string .= $source_line . "\n";
		}

        // Ensure body is not empty if all products failed encoding/validation
        if (empty($request_body_string)) {
            return ['success' => false, 'message' => 'No valid products found in batch to generate request body.'];
        }

        // Add the final required newline for the _bulk format
        // $request_body_string .= "\n"; // The loop already adds a newline after each source line

		// Add body to args
		$args['body'] = $request_body_string;

		error_log("Qraga Attempting POST : {$url}");
        // error_log("Qraga Bulk Body: \n" . $request_body_string); // Uncomment to debug body
		$response = wp_remote_post( $url, $args );

		return $this->handle_api_response( $response, $url );
	}

	/**
	 * Sends a single product via POST or PUT.
	 *
	 * @param array $product_data Transformed product data.
	 * @param string|null $product_identifier The external product ID (used for PUT).
	 * @param string $method 'POST' or 'PUT'.
	 * @return array Result status
	 */
	private function send_single_product( array $product_data, $product_identifier, string $method ) {
		$site_id = get_option( 'qraga_site_id', '' );
		$base_url = get_option( 'qraga_endpoint_url', '' );

		if ( $method === 'PUT' && empty( $product_identifier ) ) {
			return [ 'success' => false, 'message' => 'Product identifier required for PUT request.'];
		}

		// Construct URL
		$url = trailingslashit( $base_url ) . 'v1/site/' . $site_id . '/products';
		if ( $method === 'PUT' ) {
			$url .= '/' . $product_identifier;
		}

		$args = $this->get_api_request_args( $method );
		if (!is_array($args)) { // Config incomplete
			return [ 'success' => false, 'message' => $args ];
		}

		// Add body
		$args['body'] = json_encode( $product_data );
		if ($args['body'] === false) {
			error_log("QragaService: Failed to JSON encode single product data for {$product_identifier}.");
			return ['success' => false, 'message' => 'Failed to encode product data.'];
		}

		error_log("Qraga Attempting {$method}: {$url}");
		$response = wp_remote_request( $url, $args ); // Use wp_remote_request for PUT support

		return $this->handle_api_response( $response, $url );
	}

	/**
	 * Gets base arguments for API requests (headers, method, timeout).
	 *
	 * @param string $method The HTTP method (POST, PUT, DELETE, GET).
	 * @param string $content_type The Content-Type header value.
	 * @return array|string Array of args on success, error string on failure (missing config).
	 */
	private function get_api_request_args( string $method = 'POST', string $content_type = 'application/json' ) {
		$api_key = get_option( 'qraga_api_key', '' );
		$url_base = get_option( 'qraga_endpoint_url', '' );
		$site_id = get_option( 'qraga_site_id', '' );

		// Validate required settings
		if ( empty( $url_base ) || empty( $site_id ) || empty( $api_key ) ) {
			$missing = [];
			if ( empty( $url_base ) ) $missing[] = 'Endpoint URL';
			if ( empty( $site_id ) ) $missing[] = 'Site ID';
			if ( empty( $api_key ) ) $missing[] = 'API Key';
			return 'External endpoint configuration is incomplete. Missing: ' . implode(', ', $missing) . '.';
		}

		$headers = [
			'Content-Type' => $content_type,
			'Accept' => 'application/json', // Usually expect JSON response
			'Authorization' => 'Bearer ' . $api_key,
		];

		return [
			'method'  => strtoupper($method),
			'timeout' => 45,
			'headers' => $headers,
			'data_format' => 'body',
		];
	}

	/**
	 * Handles the response from wp_remote_request/post.
	 *
	 * @param \WP_Error|array $response The response object.
	 * @param string $url The URL that was requested.
	 * @return array Result array ['success' => bool, 'message' => string]
	 */
	private function handle_api_response( $response, string $url ) {
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( "QragaService Request Failed to {$url}. Error: {$error_message}" );
			return [ 'success' => false, 'message' => "HTTP request failed: {$error_message}" ];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 200 && $response_code < 300 ) {
			error_log("QragaService Request Success to {$url}. Response Code: {$response_code}");
			return [ 'success' => true ];
		} elseif ($response_code === 401 || $response_code === 403) {
			$error_message = "Authentication/Authorization failed for {$url}. Check API Key. Response Code: {$response_code}. Body: {$response_body}";
			error_log("QragaService Error: {$error_message}");
			return [ 'success' => false, 'message' => "Authentication/Authorization failed (Code: {$response_code}). Check API Key." ];
		} else {
			error_log( "QragaService Request Failed to {$url}. Response Code: {$response_code}. Body: {$response_body}" );
			return [ 'success' => false, 'message' => "Endpoint returned error code: {$response_code}. Body: {$response_body}" ];
		}
	}

	/**
	 * Transforms a WC_Product object into the desired JSON structure.
	 *
	 * @param \WC_Product $product The product object.
	 * @return array The transformed product data.
	 */
	private function transform_product_data( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return [];
		}

		$product_data = [
			'id'          => 'prod-wc-' . $product->get_id(), // Generate a unique ID based on WC ID
			'title'       => $product->get_name(),
			'description' => $product->get_description() ?: $product->get_short_description(), // Use description or short description
			'categories'  => $this->get_term_names( $product->get_category_ids(), 'product_cat' ),
			'tags'        => $this->get_term_names( $product->get_tag_ids(), 'product_tag' ),
			'features'    => [], // Initialize, will be replaced below
			'variants'    => [],
		];

		// Get features and ensure it's an object {} even if empty
		$features = $this->get_product_features( $product );
		$product_data['features'] = empty($features) ? new \stdClass() : $features;

		if ( $product->is_type( 'variable' ) && $product->has_child() ) {
			$variation_ids = $product->get_children();
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation instanceof \WC_Product_Variation && $variation->exists() ) { // Check if variation exists
					$product_data['variants'][] = $this->transform_variation_data( $variation, $product->get_id() );
				}
			}
		} else {
			// Handle simple product (or other types) as a single variant
			$product_data['variants'][] = $this->transform_simple_product_as_variant( $product );
		}

		// For simple products (or types without children), create a default variant
		// using the main product data to ensure the variants list is not empty.
		if ( empty( $product_data['variants'] ) && $product->exists() ) { // Check product exists too
			 $product_data['variants'][] = $this->transform_simple_product_as_variant( $product );
		}


		return $product_data;
	}

	/**
	 * Transforms a WC_Product_Variation object into the variant structure.
	 *
	 * @param \WC_Product_Variation $variation The variation object.
	 * @param int                   $parent_product_id The parent product ID.
	 * @return array The transformed variation data.
	 */
	private function transform_variation_data( $variation, $parent_product_id ) {
		$image_id  = $variation->get_image_id();
		$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
		// Use variation description or fallback to parent's name/title if specific title isn't set for variation
		$parent_product = wc_get_product($parent_product_id);
		$parent_name = $parent_product ? $parent_product->get_name() : 'Product #' . $parent_product_id;
		$variation_title = $variation->get_name() ?: $parent_name . ' - Variation #' . $variation->get_id();

		// Get features and ensure it's an object {} even if empty
		$variation_features = $this->get_variation_features( $variation );

		return [
			'id'                 => 'var-wc-' . $variation->get_id(),
			'title'              => $variation_title,
			'price'              => [
				'amount'   => (int) round( (float) $variation->get_price() * 100 ), // Store price in cents, round to handle potential float issues
				'currency' => get_woocommerce_currency(),
			],
			'inventory_quantity' => $variation->get_stock_quantity() ?? 0, // Handle null stock
			'features'           => empty($variation_features) ? new \stdClass() : $variation_features,
			'images'             => $image_url ? [ $image_url ] : [],
		];
	}

	/**
	 * Transforms a simple WC_Product object into the variant structure.
	 *
	 * @param \WC_Product $product The simple product object.
	 * @return array The transformed variant data.
	 */
	private function transform_simple_product_as_variant( $product ) {
		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';

		// Get features and ensure it's an object {} even if empty
		$product_features = $this->get_product_features( $product, true );

		return [
			'id'                 => 'var-wc-' . $product->get_id() . '-simple', // Unique ID for the "simple" variant
			'title'              => $product->get_name(), // Simple product acts as its own variant title
			'price'              => [
				'amount'   => (int) round( (float) $product->get_price() * 100 ), // Store price in cents, round
				'currency' => get_woocommerce_currency(),
			],
			'inventory_quantity' => $product->get_stock_quantity() ?? 0, // Handle null stock
			'features'           => empty($product_features) ? new \stdClass() : $product_features, // Get features, maybe simplified for variant context
			'images'             => $image_url ? [ $image_url ] : [],
		];
	}

	/**
	 * Gets product features from attributes.
	 * Adjust this method based on where your 'features' like dpi, connectivity etc are stored (attributes vs meta).
	 *
	 * @param \WC_Product $product The product object.
	 * @param bool $is_simple_variant Context if we are treating a simple product as a variant.
	 * @return array Key-value pairs of features.
	 */
	private function get_product_features( $product, $is_simple_variant = false ) {
		$features   = [];
		$attributes = $product->get_attributes();

		foreach ( $attributes as $attribute ) {
			// Skip attributes used for variations unless treating simple product as variant
            if ( $attribute->get_variation() && !$is_simple_variant && $product->is_type('variable') ) {
                 continue;
            }

			if ( $attribute->is_taxonomy() ) {
				$terms = wp_get_post_terms( $product->get_id(), $attribute->get_name(), [ 'fields' => 'names' ] );
				if ( ! is_wp_error( $terms ) && !empty($terms) ) {
					// Use the attribute slug (like 'pa_color') as the key
					$features[ wc_attribute_taxonomy_slug( $attribute->get_name() ) ] = implode( ', ', $terms );
				}
			} else {
				// For custom product attributes
				$options = $attribute->get_options();
				if (!empty($options)) {
					// Use the attribute name (like 'DPI') as the key
					$features[ sanitize_title( $attribute->get_name() ) ] = implode( ', ', $options );
				}
			}
		}

		// Add specific features if not already covered by attributes (example)
		// You might fetch these from post meta if they are not attributes
		if ($product->get_weight()) {
		    $features['weight'] = wc_format_weight( $product->get_weight() );
		}
		if ($product->get_length() || $product->get_width() || $product->get_height()) {
		    $features['dimensions'] = wc_format_dimensions( $product->get_dimensions(false) );
		}


		// Filter out empty features if needed
		return array_filter( $features );
	}


	/**
	 * Gets variation features from its attributes.
	 *
	 * @param \WC_Product_Variation $variation The variation object.
	 * @return array Key-value pairs of features specific to the variation.
	 */
	private function get_variation_features( $variation ) {
		$features   = [];
		$attributes = $variation->get_attributes(); // These are key-value pairs attribute_slug => option_slug

		foreach ( $attributes as $slug => $option_slug ) {
			if ( empty( $option_slug ) ) continue; // Skip attributes not set for this variation

			// Get the human-readable attribute name (e.g., "Color")
			// Need to handle both taxonomy (pa_color) and custom attributes
			$attribute_taxonomy = $slug;
			if (strpos($slug, 'attribute_') === 0) {
				$attribute_taxonomy = substr($slug, strlen('attribute_'));
			}
			$attribute_label = wc_attribute_label( $attribute_taxonomy );

			// Get the human-readable term name (e.g., "Black")
			$term = get_term_by( 'slug', $option_slug, $attribute_taxonomy );
			$option_name = $term ? $term->name : $option_slug; // Fallback to slug if term not found or it's a custom attribute value

			$features[ sanitize_title( $attribute_label ) ] = $option_name;
		}

		// Add variation-specific meta if needed
		// $limited_edition = get_post_meta($variation->get_id(), '_limited_edition', true);
		// if ($limited_edition) {
		//     $features['limited_edition'] = 'Yes';
		// }

		return array_filter( $features );
	}


	/**
	 * Gets term names from IDs for a given taxonomy.
	 *
	 * @param array  $term_ids Array of term IDs.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array Array of term names.
	 */
	private function get_term_names( $term_ids, $taxonomy ) {
		$names = [];
		if ( ! empty( $term_ids ) && is_array( $term_ids ) ) {
			$terms = get_terms( [
				'taxonomy'   => $taxonomy,
				'include'    => $term_ids,
				'hide_empty' => false,
				'fields'     => 'names',
			] );
			if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
				$names = $terms;
			}
		}
		return $names;
	}
} 