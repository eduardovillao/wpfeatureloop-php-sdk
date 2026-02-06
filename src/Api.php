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
     * Constructor
     *
     * @param string $publicKey Public API key
     * @param string $projectId Project ID
     * @param string|null $apiUrl Custom API URL (optional)
     */
    public function __construct(string $publicKey, string $projectId, ?string $apiUrl = null)
    {
        $this->publicKey = $publicKey;
        $this->projectId = $projectId;

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
     * Get single feature
     *
     * @param string $featureId Feature ID
     * @return array|WP_Error
     */
    public function getFeature(string $featureId)
    {
        return $this->request('GET', '/features/' . $featureId);
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
