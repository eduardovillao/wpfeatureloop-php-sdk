<?php

declare(strict_types=1);

namespace WPFeatureLoop;

use WP_Error;

/**
 * API Client
 *
 * Handles all communication with the FeatureLoop API.
 */
class Api
{
    /**
     * API base URL
     */
    private string $apiUrl = 'https://app.wpfeatureloop.com/api/v1';

    /**
     * Public key
     */
    private string $publicKey;

    /**
     * Project ID
     */
    private string $projectId;

    /**
     * User metadata (only sent for users who gave consent)
     */
    private array $metadata;

    /**
     * Constructor
     *
     * @param string $publicKey Public API key
     * @param string $projectId Project ID
     * @param string|null $apiUrl Custom API URL (optional)
     * @param array<string, mixed> $metadata User metadata, sent only with consent (optional)
     */
    public function __construct(string $publicKey, string $projectId, ?string $apiUrl = null, array $metadata = [])
    {
        $this->publicKey = $publicKey;
        $this->projectId = $projectId;
        $this->metadata = $metadata;

        if ($apiUrl !== null) {
            $this->apiUrl = rtrim($apiUrl, '/');
        }
    }

    /**
     * Get features list
     *
     * @param array<string, mixed> $args Query arguments (status, page, limit)
     * @return array|WP_Error
     */
    public function getFeatures(array $args = [])
    {
        $query = http_build_query($args);
        $endpoint = '/features' . ($query ? '?' . $query : '');

        return $this->request('GET', $endpoint);
    }

    /**
     * Create a new feature
     *
     * @param string $title Feature title
     * @param string $description Feature description
     * @return array|WP_Error
     */
    public function createFeature(string $title, string $description = '')
    {
        return $this->request('POST', '/features', [
            'title'       => $title,
            'description' => $description,
        ]);
    }

    /**
     * Vote on a feature
     *
     * @param string $featureId Feature ID
     * @param string $vote Vote type: 'up', 'down', or 'none'
     * @return array|WP_Error
     */
    public function vote(string $featureId, string $vote = 'up')
    {
        return $this->request('POST', '/features/' . $featureId . '/vote', [
            'vote' => $vote,
        ]);
    }

    /**
     * Remove vote from a feature
     *
     * @param string $featureId Feature ID
     * @return array|WP_Error
     */
    public function unvote(string $featureId)
    {
        return $this->vote($featureId, 'none');
    }

    /**
     * Get comments for a feature
     *
     * @param string $featureId Feature ID
     * @return array|WP_Error
     */
    public function getComments(string $featureId)
    {
        return $this->request('GET', '/features/' . $featureId . '/comments');
    }

    /**
     * Add comment to a feature
     *
     * @param string $featureId Feature ID
     * @param string $text Comment text
     * @return array|WP_Error
     */
    public function addComment(string $featureId, string $text)
    {
        return $this->request('POST', '/features/' . $featureId . '/comments', [
            'text' => $text,
        ]);
    }

    /**
     * Tell the API about the current user's consent state
     *
     * Consent lives in WordPress, so the API only learns about it when a
     * request happens to carry the header. Syncing right after a decision
     * applies it immediately instead of waiting for the user's next write.
     *
     * @return array|WP_Error
     */
    public function syncConsent()
    {
        return $this->request('POST', '/consent');
    }

    /**
     * Make API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array|WP_Error
     */
    private function request(string $method, string $endpoint, array $data = [])
    {
        $url = $this->apiUrl . $endpoint;

        // Build headers
        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'X-Public-Key' => $this->publicKey,
                'X-Project-Id' => $this->projectId,
            ],
            User::getHeaders()
        );

        $metadata = User::getMetadata($this->metadata);

        if (!empty($metadata)) {
            $encoded = wp_json_encode($metadata);

            if ($encoded === false) {
                return new WP_Error(
                    'wpfeatureloop_invalid_metadata',
                    'Metadata could not be encoded as JSON'
                );
            }

            $headers['X-User-Metadata'] = $encoded;
        }

        // Build request args
        $args = [
            'method'  => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];

        // Add body for POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        // Make request
        $response = wp_remote_request($url, $args);

        // Check for WP error
        if (is_wp_error($response)) {
            return $response;
        }

        // Parse response
        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check for HTTP errors
        if ($statusCode >= 400) {
            $errorMessage = $data['error'] ?? $data['message'] ?? 'Unknown error';
            return new WP_Error('wpfeatureloop_api_error', $errorMessage, [
                'status' => $statusCode,
                'body' => $body,
            ]);
        }

        // Check for JSON parse error
        if ($data === null && !empty($body)) {
            return new WP_Error('wpfeatureloop_parse_error', 'Invalid JSON response', [
                'body' => $body,
            ]);
        }

        return $data;
    }
}
