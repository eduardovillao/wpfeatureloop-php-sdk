<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * REST API Handler
 *
 * Registers WordPress REST API endpoints for the widget.
 * Endpoints are under /wp-json/wpfeatureloop/v1/
 *
 * Routes are registered once and shared across all Client instances.
 * Each request includes a project_id query parameter so the handler
 * can resolve the correct Client instance from the registry.
 */
class RestApi
{
    /**
     * API namespace
     */
    public const NAMESPACE = 'wpfeatureloop/v1';

    /**
     * Common arg definition for project_id (required on all routes)
     */
    private const PROJECT_ID_ARG = [
        'project_id' => [
            'required' => true,
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
    ];

    private function resolveClient(\WP_REST_Request $request): ?Client
    {
        return Client::getInstance($request->get_param('project_id'));
    }

    /**
     * Register REST API routes
     */
    public function registerRoutes(): void
    {
        // GET /features
        register_rest_route(self::NAMESPACE, '/features', [
            'methods' => 'GET',
            'callback' => [$this, 'getFeatures'],
            'permission_callback' => '__return_true',
            'args' => self::PROJECT_ID_ARG,
        ]);

        // POST /features
        register_rest_route(self::NAMESPACE, '/features', [
            'methods' => 'POST',
            'callback' => [$this, 'createFeature'],
            'permission_callback' => [$this, 'canInteract'],
            'args' => self::PROJECT_ID_ARG + [
                'title' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'description' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'default' => '',
                ],
            ],
        ]);

        // POST /features/{id}/vote
        register_rest_route(self::NAMESPACE, '/features/(?P<id>[a-zA-Z0-9_-]+)/vote', [
            'methods' => 'POST',
            'callback' => [$this, 'vote'],
            'permission_callback' => [$this, 'canInteract'],
            'args' => self::PROJECT_ID_ARG + [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'vote' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['up', 'down', 'none'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /features/{id}/comments
        register_rest_route(self::NAMESPACE, '/features/(?P<id>[a-zA-Z0-9_-]+)/comments', [
            'methods' => 'GET',
            'callback' => [$this, 'getComments'],
            'permission_callback' => '__return_true',
            'args' => self::PROJECT_ID_ARG + [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /features/{id}/comments
        register_rest_route(self::NAMESPACE, '/features/(?P<id>[a-zA-Z0-9_-]+)/comments', [
            'methods' => 'POST',
            'callback' => [$this, 'addComment'],
            'permission_callback' => [$this, 'canInteract'],
            'args' => self::PROJECT_ID_ARG + [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'text' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);
    }

    /**
     * Permission callback - check if user can interact
     */
    public function canInteract(\WP_REST_Request $request): bool
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return false;
        }

        return $client->canInteract();
    }

    /**
     * GET /features
     */
    public function getFeatures(\WP_REST_Request $request): \WP_REST_Response
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return new \WP_REST_Response([
                'error' => 'Unknown project ID',
            ], 400);
        }

        $args = [
            'status' => $request->get_param('status'),
            'page' => $request->get_param('page'),
            'limit' => $request->get_param('limit'),
        ];

        // Remove null values
        $args = array_filter($args, fn($v) => $v !== null);

        $result = $client->getFeatures($args);

        if (is_wp_error($result)) {
            $errorData = $result->get_error_data();
            $statusCode = $errorData['status'] ?? 400;
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
                'debug' => WP_DEBUG ? $errorData : null,
            ], $statusCode);
        }

        // Backend API returns array directly, wrap it for SDK format
        $features = is_array($result) && !isset($result['features'])
            ? $result
            : ($result['features'] ?? []);

        return new \WP_REST_Response([
            'features' => $features,
        ]);
    }

    /**
     * POST /features
     */
    public function createFeature(\WP_REST_Request $request): \WP_REST_Response
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return new \WP_REST_Response([
                'error' => 'Unknown project ID',
            ], 400);
        }

        $title = $request->get_param('title');
        $description = $request->get_param('description') ?? '';

        $result = $client->createFeature($title, $description);

        if (is_wp_error($result)) {
            $errorData = $result->get_error_data();
            $statusCode = $errorData['status'] ?? 400;
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], $statusCode);
        }

        return new \WP_REST_Response($result, 201);
    }

    /**
     * POST /features/{id}/vote
     */
    public function vote(\WP_REST_Request $request): \WP_REST_Response
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return new \WP_REST_Response([
                'error' => 'Unknown project ID',
            ], 400);
        }

        $featureId = $request->get_param('id');
        $voteType = $request->get_param('vote'); // 'up', 'down', or 'none'

        $result = $client->vote($featureId, $voteType);

        if (is_wp_error($result)) {
            $errorData = $result->get_error_data();
            $statusCode = $errorData['status'] ?? 400;
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], $statusCode);
        }

        return new \WP_REST_Response($result);
    }

    /**
     * GET /features/{id}/comments
     */
    public function getComments(\WP_REST_Request $request): \WP_REST_Response
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return new \WP_REST_Response([
                'error' => 'Unknown project ID',
            ], 400);
        }

        $featureId = $request->get_param('id');

        $result = $client->getComments($featureId);

        if (is_wp_error($result)) {
            $errorData = $result->get_error_data();
            $statusCode = $errorData['status'] ?? 400;
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], $statusCode);
        }

        // Backend returns array directly with formatted comments
        $comments = is_array($result) ? $result : [];

        return new \WP_REST_Response([
            'comments' => $comments,
        ]);
    }

    /**
     * POST /features/{id}/comments
     */
    public function addComment(\WP_REST_Request $request): \WP_REST_Response
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return new \WP_REST_Response([
                'error' => 'Unknown project ID',
            ], 400);
        }

        $featureId = $request->get_param('id');
        $text = $request->get_param('text');

        $result = $client->addComment($featureId, $text);

        if (is_wp_error($result)) {
            $errorData = $result->get_error_data();
            $statusCode = $errorData['status'] ?? 400;
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], $statusCode);
        }

        // Backend already returns formatted comment
        return new \WP_REST_Response($result, 201);
    }
}
