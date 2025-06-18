<?php
// File: includes/admin/class-qraga-product-sync.php

defined('ABSPATH') || exit;

class Qraga_Product_Sync {

    const SYNC_META_KEY = '_qraga_synced';

    /**
     * Constructor - Add hooks.
     */
    public function __construct() {
        add_action( 'woocommerce_update_product', array( $this, 'handle_product_save_hook' ), 10, 1 );
        add_action( 'wp_trash_post', array( $this, 'handle_product_trash_hook' ), 10, 1 );
        add_action( 'before_delete_post', array( $this, 'handle_product_delete_hook' ), 10, 1 );
    }

    // --- Start of Ported/Adapted Methods from old QragaService.php ---

    /**
     * Transforms a WC_Product object into the desired JSON structure.
     * Ported from QragaService->transform_product_data
     * Made public to be callable from Qraga_Api for bulk operations.
     */
    public function transform_product_data( \WC_Product $product ): array {
        if ( ! $product instanceof \WC_Product ) {
            return [];
        }

        $product_data = [
            'id'          => (string) $product->get_id(),
            'title'       => $product->get_name(),
            'description' => $product->get_description() ?: $product->get_short_description(),
            'categories'  => $this->get_term_names( $product->get_category_ids(), 'product_cat' ),
            'tags'        => $this->get_term_names( $product->get_tag_ids(), 'product_tag' ),
            'slug'        => $product->get_slug(),
            'features'    => new \stdClass(), // Initialize as empty object
            'variants'    => [],
        ];

        $features = $this->get_product_features( $product );
        $product_data['features'] = empty($features) ? new \stdClass() : (object)$features;

        if ( $product->is_type( 'variable' ) && $product->has_child() ) {
            $variation_ids = $product->get_children();
            foreach ( $variation_ids as $variation_id ) {
                $variation = wc_get_product( $variation_id );
                if ( $variation instanceof \WC_Product_Variation && $variation->exists() ) {
                    $product_data['variants'][] = $this->transform_variation_data( $variation, $product->get_id() );
                }
            }
        } else {
            $product_data['variants'][] = $this->transform_simple_product_as_variant( $product );
        }

        if ( empty( $product_data['variants'] ) && $product->exists() ) {
             $product_data['variants'][] = $this->transform_simple_product_as_variant( $product );
        }
        
        return apply_filters('qraga_product_payload', $product_data, $product);
    }

    /**
     * Transforms a WC_Product_Variation object into the variant structure.
     * Ported from QragaService->transform_variation_data
     */
    private function transform_variation_data( \WC_Product_Variation $variation, int $parent_product_id ): array {
        $image_id  = $variation->get_image_id();
        $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
        $parent_product = wc_get_product($parent_product_id);
        $parent_name = $parent_product ? $parent_product->get_name() : 'Product #' . $parent_product_id;
        $variation_title = $variation->get_name() ?: $parent_name . ' - Variation #' . $variation->get_id();

        $variation_features = $this->get_variation_features( $variation );

        return [
            'id'                 => (string) $variation->get_id(),
            'title'              => $variation_title,
            'price'              => [
                'amount'   => (int) round( (float) $variation->get_price() * 100 ),
                'currency' => get_woocommerce_currency(),
            ],
            'inventory_quantity' => $variation->get_stock_quantity() ?? 0,
            'features'           => empty($variation_features) ? new \stdClass() : (object)$variation_features,
            'images'             => $image_url ? [ $image_url ] : [],
        ];
    }

    /**
     * Transforms a simple WC_Product object into the variant structure.
     * Ported from QragaService->transform_simple_product_as_variant
     */
    private function transform_simple_product_as_variant( \WC_Product $product ): array {
        $image_id  = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
        $product_features = $this->get_product_features( $product, true );

        return [
            'id'                 => (string) $product->get_id(),
            'title'              => $product->get_name(),
            'price'              => [
                'amount'   => (int) round( (float) $product->get_price() * 100 ),
                'currency' => get_woocommerce_currency(),
            ],
            'inventory_quantity' => $product->get_stock_quantity() ?? 0,
            'features'           => empty($product_features) ? new \stdClass() : (object)$product_features,
            'images'             => $image_url ? [ $image_url ] : [],
        ];
    }

    /**
     * Gets product features from attributes.
     * Ported from QragaService->get_product_features
     */
    private function get_product_features( \WC_Product $product, bool $is_simple_variant = false ): array {
        $features   = [];
        $attributes = $product->get_attributes();

        foreach ( $attributes as $attribute ) {
            if ( $attribute->get_variation() && !$is_simple_variant && $product->is_type('variable') ) {
                 continue;
            }
            if ( $attribute->is_taxonomy() ) {
                $terms = wp_get_post_terms( $product->get_id(), $attribute->get_name(), [ 'fields' => 'names' ] );
                if ( ! is_wp_error( $terms ) && !empty($terms) ) {
                    $features[ wc_attribute_taxonomy_slug( $attribute->get_name() ) ] = implode( ', ', $terms );
                }
            } else {
                $options = $attribute->get_options();
                if (!empty($options)) {
                    $features[ sanitize_title( $attribute->get_name() ) ] = implode( ', ', $options );
                }
            }
        }
        return array_filter( $features );
    }

    /**
     * Gets variation features from its attributes.
     * Ported from QragaService->get_variation_features
     */
    private function get_variation_features( \WC_Product_Variation $variation ): array {
        $features   = [];
        $attributes = $variation->get_attributes();
        foreach ( $attributes as $slug => $option_slug ) {
            if ( empty( $option_slug ) ) continue;
            $attribute_taxonomy = $slug;
            if (strpos($slug, 'attribute_') === 0) {
                $attribute_taxonomy = substr($slug, strlen('attribute_'));
            }
            $attribute_label = wc_attribute_label( $attribute_taxonomy );
            $term = get_term_by( 'slug', $option_slug, $attribute_taxonomy );
            $option_name = $term ? $term->name : $option_slug;
            $features[ sanitize_title( $attribute_label ) ] = $option_name;
        }
        return array_filter( $features );
    }

    /**
     * Gets term names from IDs for a given taxonomy.
     * Ported from QragaService->get_term_names
     */
    private function get_term_names( array $term_ids, string $taxonomy ): array {
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

    /**
     * Gets base arguments for API requests (headers, method, timeout).
     * Ported from QragaService->get_api_request_args
     * This will be used by handle_product_save and send_delete_request.
     */
    private function get_api_request_args( string $method = 'POST', string $content_type = 'application/json' ): array|string {
        $api_key = get_option( 'qraga_api_key', '' );
        $url_base = get_option( 'qraga_endpoint_url', '' );
        $site_id = get_option( 'qraga_site_id', '' );

        if ( empty( $url_base ) || empty( $site_id ) || empty( $api_key ) ) {
            $missing = [];
            if ( empty( $url_base ) ) $missing[] = 'Endpoint URL';
            if ( empty( $site_id ) ) $missing[] = 'Site ID';
            if ( empty( $api_key ) ) $missing[] = 'API Key';
            return 'External endpoint configuration is incomplete. Missing: ' . implode(', ', $missing) . '.';
        }

        $headers = [
            'Content-Type' => $content_type,
            'Accept' => 'application/json',
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
     * Constructs the API URL for single product operations (create, update, delete).
     * Adapted from QragaService URL construction for single products.
     */
    private function get_single_product_api_url(string $method, ?string $product_identifier = null): ?string {
        $base_url = get_option('qraga_endpoint_url');
        $site_id = get_option('qraga_site_id');

        if (empty($base_url) || empty($site_id)) {
            return null;
        }
        
        $url = trailingslashit($base_url) . 'v1/site/' . rawurlencode($site_id) . '/products';

        if (($method === 'PUT' || $method === 'DELETE')) {
            if (empty($product_identifier)) {
                return null;
            }
            $url .= '/' . rawurlencode($product_identifier);
        }
        return $url;
    }
    
    /**
     * Handles the response from wp_remote_request/post.
     * Ported from QragaService->handle_api_response
     */
    private function handle_api_response( $response, string $url ): array {
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return [ 'success' => false, 'message' => "HTTP request failed: {$error_message}" ];
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( $response_code >= 200 && $response_code < 300 ) {
            return [ 'success' => true, 'message' => 'Request successful.', 'response_code' => $response_code, 'body' => $response_body ];
        } elseif ($response_code === 401 || $response_code === 403) {
            $error_message = "Authentication/Authorization failed for {$url}. Check API Key. Response Code: {$response_code}. Body: {$response_body}";
            return [ 'success' => false, 'message' => "Authentication/Authorization failed (Code: {$response_code}). Check API Key.", 'response_code' => $response_code ];
        } else {
            return [ 'success' => false, 'message' => "Endpoint returned error code: {$response_code}. Body: {$response_body}", 'response_code' => $response_code ];
        }
    }

    // --- End of Ported/Adapted Methods ---

    /**
     * Constructs the API URL for bulk product operations.
     */
    private function get_bulk_products_api_url(): ?string {
        $base_url = get_option('qraga_endpoint_url');
        $site_id = get_option('qraga_site_id');

        if (empty($base_url) || empty($site_id)) {
            return null;
        }
        
        return trailingslashit($base_url) . 'v1/site/' . rawurlencode($site_id) . '/products/bulk'; 
    }

    /**
     * Sends a batch of products to the Qraga API as NDJSON.
     *
     * @param array $products_payload Array of product data arrays (output from transform_product_data).
     * @return array Result from handle_api_response.
     */
    public function send_ndjson_batch(array $products_payload): array {
        if (empty($products_payload)) {
            return ['success' => true, 'message' => 'No products in batch to send.', 'product_ids' => []];
        }

        $api_url = $this->get_bulk_products_api_url();
        if (!$api_url) {
            return ['success' => false, 'message' => 'Could not determine API URL for bulk send.'];
        }

        $args = $this->get_api_request_args('POST', 'application/x-ndjson');
        if (is_string($args)) {
            return ['success' => false, 'message' => $args];
        }

        $ndjson_body = '';
        foreach ($products_payload as $product_data) {
            $json_line = wp_json_encode($product_data);
            if ($json_line === false) {
                continue; 
            }
            $ndjson_body .= $json_line . "\n";
        }

        if (empty(trim($ndjson_body))) {
            return ['success' => false, 'message' => 'Failed to prepare any product for NDJSON body.'];
        }

        $args['body'] = trim($ndjson_body);

        $response = wp_remote_request($api_url, $args);
        return $this->handle_api_response($response, $api_url);
    }

    /**
     * Public facing method for handling product save/update events.
     * Determines whether to sync (create/update) or delete the product in Qraga based on publish status.
     * Called by the save hook.
     *
     * @param int $product_id The ID of the product being saved.
     * @return array Result array indicating success/failure.
     */
    public function process_product_sync( int $product_id ): array {
        $product = wc_get_product( $product_id );

        if ( ! $product || ! $this->is_syncable_product_type( $product ) ) {
            return ['success' => false, 'message' => 'Product not found or not syncable type.', 'product_id' => $product_id];
        }

        $product_status = $product->get_status();
        $was_synced = get_post_meta( $product_id, self::SYNC_META_KEY, true );

        if ( $product_status === 'publish' ) {
            $transformed_data = $this->transform_product_data( $product );
            $product_identifier_for_api = (string) $product_id;

            if ( empty( $transformed_data ) ) {
                return ['success' => false, 'message' => 'Failed to transform product data.', 'product_id' => $product_id];
            }

            $method = $was_synced ? 'PUT' : 'POST';
            
            $api_url = $this->get_single_product_api_url($method, ($method === 'PUT' ? $product_identifier_for_api : null) );
            if (!$api_url) {
                return ['success' => false, 'message' => 'Could not determine API URL.', 'product_id' => $product_id];
            }

            $args = $this->get_api_request_args( $method );
            if (is_string($args)) {
                return ['success' => false, 'message' => $args, 'product_id' => $product_id];
            }

            $args['body'] = wp_json_encode( $transformed_data );
            if ($args['body'] === false) {
                return ['success' => false, 'message' => 'Failed to encode product data to JSON.', 'product_id' => $product_id];
            }
            
            $response = wp_remote_request( $api_url, $args );
            $result = $this->handle_api_response( $response, $api_url );

            if ( $result['success'] ) {
                update_post_meta( $product_id, self::SYNC_META_KEY, time() );
            }
            return array_merge($result, ['product_id' => $product_id]);

        } else {
            if ( $was_synced ) {
                return $this->process_product_delete( $product_id );
            } else {
                return ['success' => true, 'message' => 'Product not published and not previously synced. No sync action needed.', 'product_id' => $product_id];
            }
        }
    }
    
    /**
     * Public facing method for deleting a product from Qraga.
     * Replaces old direct hook callback and integrates logic from QragaService->handle_product_delete
     */
    public function process_product_delete( int $product_id ): array {
         $post_type = get_post_type( $product_id );
        if ( $post_type !== 'product' && $post_type !== 'product_variation' ) {
            return ['success' => false, 'message' => 'Not a product post type.', 'product_id' => $product_id]; 
        }

        $product_identifier_for_api = (string) $product_id;
        $api_url = $this->get_single_product_api_url('DELETE', $product_identifier_for_api);

        if (!$api_url) {
            return ['success' => false, 'message' => 'Could not determine API URL for delete.', 'product_id' => $product_id];
        }

        $args = $this->get_api_request_args('DELETE');
        if (is_string($args)) {
            return ['success' => false, 'message' => $args, 'product_id' => $product_id];
        }

        $response = wp_remote_request( $api_url, $args );
        $result = $this->handle_api_response( $response, $api_url );

        if ( $result['success'] || (isset($result['response_code']) && $result['response_code'] === 404) ) {
            delete_post_meta( $product_id, self::SYNC_META_KEY );
            $result['success'] = true; 
        }
        return array_merge($result, ['product_id' => $product_id]);
    }

    // --- Hook Callbacks ---
    public function handle_product_save_hook( int $product_id ) {
        $this->process_product_sync( $product_id );
    }

    public function handle_product_trash_hook( int $post_id ) {
        if ( get_post_type( $post_id ) === 'product' ) {
            $this->process_product_delete( $post_id );
        }
    }

    public function handle_product_delete_hook( int $post_id ) {
        $post_type = get_post_type( $post_id );
        if ( $post_type === 'product' || $post_type === 'product_variation' ) {
            $this->process_product_delete( $post_id );
        }
    }
    
    /** 
     * Checks if the current product is syncable.
     */
    private function is_syncable_product_type( $product ) {
        return $product->is_type( array( 'simple', 'variable' ) );
    }
}
