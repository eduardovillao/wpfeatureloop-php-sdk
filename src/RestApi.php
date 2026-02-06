<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * REST API Handler
 *
 * Registers WordPress REST API endpoints for the widget.
 * Endpoints are under /wp-json/wpfeatureloop/v1/
 */
class RestApi
{
    /**
     * API namespace
     */
    public const NAMESPACE = 'wpfeatureloop/v1';

    /**
     * Client instance
     */
    private Client $client;

    /**
     * Constructor
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
        ]);

        // POST /features
        register_rest_route(self::NAMESPACE, '/features', [
            'methods' => 'POST',
            'callback' => [$this, 'createFeature'],
            'permission_callback' => [$this, 'canInteract'],
            'args' => [
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
            'args' => [
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
            'args' => [
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
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'content' => [
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
    public function canInteract(): bool
    {
        return $this->client->canInteract();
    }

    /**
     * GET /features
     */
    public function getFeatures(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = [
            'status' => $request->get_param('status'),
            'page' => $request->get_param('page'),
            'limit' => $request->get_param('limit'),
        ];

        // Remove null values
        $args = array_filter($args, fn($v) => $v !== null);

        $result = $this->client->getFeatures($args);

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
        $title = $request->get_param('title');
        $description = $request->get_param('description') ?? '';

        $result = $this->client->createFeature($title, $description);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response($result, 201);
    }

    /**
     * POST /features/{id}/vote
     */
    public function vote(\WP_REST_Request $request): \WP_REST_Response
    {
        $featureId = $request->get_param('id');
        $voteType = $request->get_param('vote');

        if ($voteType === 'none') {
            $result = $this->client->unvote($featureId);
        } elseif ($voteType === 'up') {
            $result = $this->client->vote($featureId);
        } else {
            // down vote - API might handle this differently
            $result = $this->client->vote($featureId);
        }

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response($result);
    }

    /**
     * GET /features/{id}/comments
     */
    public function getComments(\WP_REST_Request $request): \WP_REST_Response
    {
        $featureId = $request->get_param('id');

        // Get feature with comments
        $result = $this->client->getFeature($featureId);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], 400);
        }

        $comments = $result['comments'] ?? [];

        // Format comments for frontend
        $formatted = array_map(function ($comment) {
            $author = $comment['user']['displayName'] ?? 'Anonymous';
            $initials = strtoupper(substr($author, 0, 1));

            return [
                'id' => $comment['id'],
                'author' => $author,
                'initials' => $initials,
                'text' => $comment['content'] ?? '',
                'time' => $this->formatTime($comment['createdAt'] ?? ''),
                'isTeamReply' => $comment['isTeamReply'] ?? false,
            ];
        }, $comments);

        return new \WP_REST_Response([
            'comments' => $formatted,
        ]);
    }

    /**
     * POST /features/{id}/comments
     */
    public function addComment(\WP_REST_Request $request): \WP_REST_Response
    {
        $featureId = $request->get_param('id');
        $content = $request->get_param('content');

        $result = $this->client->addComment($featureId, $content);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'error' => $result->get_error_message(),
            ], 400);
        }

        // Format response for frontend
        $author = $result['user']['displayName'] ?? User::getDisplayName();
        $initials = strtoupper(substr($author, 0, 1));

        return new \WP_REST_Response([
            'id' => $result['id'] ?? '',
            'author' => $author,
            'initials' => $initials,
            'text' => $result['content'] ?? $content,
            'time' => $this->formatTime($result['createdAt'] ?? ''),
            'isTeamReply' => false,
        ], 201);
    }

    /**
     * Format timestamp for display
     */
    private function formatTime(string $timestamp): string
    {
        if (empty($timestamp)) {
            return '';
        }

        $time = strtotime($timestamp);
        if ($time === false) {
            return '';
        }

        $diff = time() - $time;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . 'm ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . 'd ago';
        } else {
            return date('M j', $time);
        }
    }
}
