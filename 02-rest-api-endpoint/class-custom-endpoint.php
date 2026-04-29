<?php
/**
 * Custom REST API Endpoint — ezekiel/v1/items
 *
 * @package EzekielApetu\RestApi
 */

namespace EzekielApetu\RestApi;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers and handles a versioned REST API resource: /ezekiel/v1/items.
 *
 * Supports:
 *   GET  /ezekiel/v1/items        — list items with optional filtering
 *   GET  /ezekiel/v1/items/(?P<id>\d+) — retrieve a single item
 *   POST /ezekiel/v1/items        — create a new item
 *
 * Hook this class in via:
 *   add_action( 'rest_api_init', array( new Custom_Endpoint(), 'register_routes' ) );
 */
class Custom_Endpoint {

    /**
     * REST API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'ezekiel/v1';

    /**
     * Resource base path.
     *
     * @var string
     */
    const BASE = 'items';

    /**
     * Register all routes for this endpoint.
     *
     * @return void
     */
    public function register_routes(): void {
        // Collection: GET (list) + POST (create).
        register_rest_route(
            self::NAMESPACE,
            '/' . self::BASE,
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_item' ),
                    'permission_callback' => array( $this, 'create_item_permissions_check' ),
                    'args'                => $this->get_endpoint_args_for_item_schema( true ),
                ),
                'schema' => array( $this, 'get_item_schema' ),
            )
        );

        // Single item: GET + DELETE.
        register_rest_route(
            self::NAMESPACE,
            '/' . self::BASE . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => __( 'Unique identifier for the item.', 'ezekiel-rest-api' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => array( $this, 'validate_item_id' ),
                        ),
                    ),
                ),
                'schema' => array( $this, 'get_item_schema' ),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    /**
     * Check whether the current user may read items.
     *
     * Public read access is intentional for this example; swap for
     * current_user_can( 'read' ) to require authentication.
     *
     * @param \WP_REST_Request $request The incoming request.
     * @return true|\WP_Error
     */
    public function get_items_permissions_check( \WP_REST_Request $request ): true|\WP_Error {
        return true;
    }

    /**
     * Check whether the current user may create items.
     *
     * Requires the `publish_posts` capability as a proxy for "trusted editor".
     * Adjust to a custom capability in real projects.
     *
     * @param \WP_REST_Request $request The incoming request.
     * @return bool|\WP_Error
     */
    public function create_item_permissions_check( \WP_REST_Request $request ): bool|\WP_Error {
        if ( ! current_user_can( 'publish_posts' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to create items.', 'ezekiel-rest-api' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Request handlers
    // -------------------------------------------------------------------------

    /**
     * Handle GET /ezekiel/v1/items — return a paginated list of items.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error Response object on success, WP_Error on failure.
     */
    public function get_items( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        global $wpdb;

        $per_page = absint( $request->get_param( 'per_page' ) ?: 10 );
        $page     = absint( $request->get_param( 'page' ) ?: 1 );
        $status   = sanitize_text_field( $request->get_param( 'status' ) ?: 'active' );
        $search   = sanitize_text_field( $request->get_param( 'search' ) ?: '' );
        $offset   = ( $page - 1 ) * $per_page;

        $table = $wpdb->prefix . 'ezekiel_boilerplate_items';

        // Build query with optional search — always use prepared statements.
        if ( $search ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, user_id, title, status, created_at FROM {$table}
                     WHERE status = %s AND title LIKE %s
                     ORDER BY created_at DESC
                     LIMIT %d OFFSET %d",
                    $status,
                    '%' . $wpdb->esc_like( $search ) . '%',
                    $per_page,
                    $offset
                )
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE status = %s AND title LIKE %s",
                    $status,
                    '%' . $wpdb->esc_like( $search ) . '%'
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, user_id, title, status, created_at FROM {$table}
                     WHERE status = %s
                     ORDER BY created_at DESC
                     LIMIT %d OFFSET %d",
                    $status,
                    $per_page,
                    $offset
                )
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                    $status
                )
            );
        }

        if ( $wpdb->last_error ) {
            return new \WP_Error(
                'db_query_error',
                __( 'Database query failed.', 'ezekiel-rest-api' ),
                array( 'status' => 500 )
            );
        }

        $data     = array_map( array( $this, 'prepare_item_for_response_collection' ), $results ?: array() );
        $response = rest_ensure_response( $data );

        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

        return $response;
    }

    /**
     * Handle GET /ezekiel/v1/items/{id} — return a single item.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_item( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        global $wpdb;

        $id    = absint( $request->get_param( 'id' ) );
        $table = $wpdb->prefix . 'ezekiel_boilerplate_items';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id )
        );

        if ( ! $item ) {
            return new \WP_Error(
                'rest_item_not_found',
                __( 'Item not found.', 'ezekiel-rest-api' ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( $this->prepare_item_for_response( $item, $request ) );
    }

    /**
     * Handle POST /ezekiel/v1/items — create a new item.
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_item( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        global $wpdb;

        $title   = sanitize_text_field( $request->get_param( 'title' ) );
        $content = wp_kses_post( $request->get_param( 'content' ) );
        $status  = sanitize_text_field( $request->get_param( 'status' ) ?: 'active' );

        if ( empty( $title ) ) {
            return new \WP_Error(
                'rest_missing_param',
                __( 'Title is required.', 'ezekiel-rest-api' ),
                array( 'status' => 400 )
            );
        }

        if ( ! in_array( $status, array( 'active', 'inactive', 'draft' ), true ) ) {
            return new \WP_Error(
                'rest_invalid_param',
                __( 'Status must be one of: active, inactive, draft.', 'ezekiel-rest-api' ),
                array( 'status' => 400 )
            );
        }

        $table = $wpdb->prefix . 'ezekiel_boilerplate_items';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id' => get_current_user_id(),
                'title'   => $title,
                'content' => $content,
                'status'  => $status,
            ),
            array( '%d', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return new \WP_Error(
                'rest_insert_failed',
                __( 'Could not create the item.', 'ezekiel-rest-api' ),
                array( 'status' => 500 )
            );
        }

        $item_id  = $wpdb->insert_id;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $new_item = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $item_id )
        );

        $response = rest_ensure_response( $this->prepare_item_for_response( $new_item, $request ) );
        $response->set_status( 201 );
        $response->header( 'Location', rest_url( self::NAMESPACE . '/' . self::BASE . '/' . $item_id ) );

        return $response;
    }

    // -------------------------------------------------------------------------
    // Response shaping
    // -------------------------------------------------------------------------

    /**
     * Shape a full item row into the API response format.
     *
     * @param object           $item    Raw database row.
     * @param \WP_REST_Request $request The current request.
     * @return array<string, mixed>
     */
    public function prepare_item_for_response( object $item, \WP_REST_Request $request ): array {
        return array(
            'id'         => (int) $item->id,
            'user_id'    => (int) $item->user_id,
            'title'      => $item->title,
            'content'    => $item->content,
            'status'     => $item->status,
            'created_at' => mysql2date( 'c', $item->created_at ),
            'updated_at' => mysql2date( 'c', $item->updated_at ),
            '_links'     => array(
                'self' => array(
                    array( 'href' => rest_url( self::NAMESPACE . '/' . self::BASE . '/' . $item->id ) ),
                ),
            ),
        );
    }

    /**
     * Shape an item row for use in a collection response (lighter payload).
     *
     * @param object $item Raw database row.
     * @return array<string, mixed>
     */
    private function prepare_item_for_response_collection( object $item ): array {
        return array(
            'id'         => (int) $item->id,
            'user_id'    => (int) $item->user_id,
            'title'      => $item->title,
            'status'     => $item->status,
            'created_at' => mysql2date( 'c', $item->created_at ),
        );
    }

    // -------------------------------------------------------------------------
    // Schema & argument definitions
    // -------------------------------------------------------------------------

    /**
     * Retrieve the item's JSON schema, conforming to JSON Schema draft 4.
     *
     * @return array<string, mixed>
     */
    public function get_item_schema(): array {
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'ezekiel-item',
            'type'       => 'object',
            'properties' => array(
                'id'         => array(
                    'description' => __( 'Unique identifier for the item.', 'ezekiel-rest-api' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'user_id'    => array(
                    'description' => __( 'ID of the user who owns this item.', 'ezekiel-rest-api' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'title'      => array(
                    'description' => __( 'Title of the item.', 'ezekiel-rest-api' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'required'    => true,
                ),
                'content'    => array(
                    'description' => __( 'Content body of the item.', 'ezekiel-rest-api' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'status'     => array(
                    'description' => __( 'Item status.', 'ezekiel-rest-api' ),
                    'type'        => 'string',
                    'enum'        => array( 'active', 'inactive', 'draft' ),
                    'default'     => 'active',
                    'context'     => array( 'view', 'edit' ),
                ),
                'created_at' => array(
                    'description' => __( 'Creation date (ISO 8601).', 'ezekiel-rest-api' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view' ),
                    'readonly'    => true,
                ),
                'updated_at' => array(
                    'description' => __( 'Last modified date (ISO 8601).', 'ezekiel-rest-api' ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array( 'view' ),
                    'readonly'    => true,
                ),
            ),
        );
    }

    /**
     * Build args array for item creation from the schema.
     *
     * @param bool $require_title Whether to mark title as required.
     * @return array<string, mixed>
     */
    private function get_endpoint_args_for_item_schema( bool $require_title = false ): array {
        return array(
            'title'   => array(
                'description'       => __( 'Title of the item.', 'ezekiel-rest-api' ),
                'type'              => 'string',
                'required'          => $require_title,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'content' => array(
                'description'       => __( 'Content body of the item.', 'ezekiel-rest-api' ),
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'status'  => array(
                'description'       => __( 'Item status.', 'ezekiel-rest-api' ),
                'type'              => 'string',
                'enum'              => array( 'active', 'inactive', 'draft' ),
                'default'           => 'active',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }

    /**
     * Build query parameters accepted by the collection endpoint.
     *
     * @return array<string, mixed>
     */
    private function get_collection_params(): array {
        return array(
            'page'     => array(
                'description'       => __( 'Page number of the result set.', 'ezekiel-rest-api' ),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'description'       => __( 'Number of items per page.', 'ezekiel-rest-api' ),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'status'   => array(
                'description'       => __( 'Filter by item status.', 'ezekiel-rest-api' ),
                'type'              => 'string',
                'enum'              => array( 'active', 'inactive', 'draft' ),
                'default'           => 'active',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'search'   => array(
                'description'       => __( 'Search items by title.', 'ezekiel-rest-api' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }

    // -------------------------------------------------------------------------
    // Custom validators
    // -------------------------------------------------------------------------

    /**
     * Validate that the given ID corresponds to an existing record.
     *
     * @param mixed            $value   The submitted value.
     * @param \WP_REST_Request $request The current request.
     * @param string           $param   The parameter name being validated.
     * @return true|\WP_Error
     */
    public function validate_item_id( mixed $value, \WP_REST_Request $request, string $param ): true|\WP_Error {
        global $wpdb;

        $id    = absint( $value );
        $table = $wpdb->prefix . 'ezekiel_boilerplate_items';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id = %d", $id )
        );

        if ( ! $exists ) {
            return new \WP_Error(
                'rest_invalid_param',
                /* translators: %s: parameter name */
                sprintf( __( 'Invalid %s: no item with that ID.', 'ezekiel-rest-api' ), $param ),
                array( 'status' => 404 )
            );
        }

        return true;
    }
}
