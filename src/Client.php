<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * FeatureLoop Client
 *
 * Main entry point for the FeatureLoop SDK.
 *
 * Usage:
 * ```php
 * use WPFeatureLoop\Client;
 * use WPFeatureLoop\Widget;
 *
 * $client = new Client('pk_live_xxx', 'project_id');
 *
 * // Render widget
 * $widget = new Widget($client, ['locale' => 'en']);
 * echo $widget->render();
 *
 * // Or use API directly
 * if ($client->canInteract()) {
 *     $client->createFeature('My feature idea', 'Description here');
 *     $client->vote('feature_id');
 * }
 * ```
 */
class Client
{
    /**
     * API client instance
     */
    private Api $api;

    /**
     * Required capability for interactions
     */
    private string $capability;

    /**
     * Constructor
     *
     * @param string $publicKey Public API key (starts with pk_live_)
     * @param string $projectId Project ID
     * @param array<string, mixed> $options Optional configuration
     *                                      - api_url: Custom API URL
     *                                      - capability: Required WP capability (default: 'read')
     */
    public function __construct(string $publicKey, string $projectId, array $options = [])
    {
        $apiUrl = $options['api_url'] ?? null;
        $this->capability = $options['capability'] ?? 'read';

        $this->api = new Api($publicKey, $projectId, $apiUrl);
    }

    /**
     * Check if current user can interact with FeatureLoop
     *
     * @return bool
     */
    public function canInteract(): bool
    {
        return User::canInteract($this->capability);
    }

    /**
     * Get features list
     *
     * @param array<string, mixed> $args Query arguments
     *                                   - status: Filter by status slug
     *                                   - page: Page number
     *                                   - limit: Items per page
     * @return array|\WP_Error
     */
    public function getFeatures(array $args = [])
    {
        return $this->api->getFeatures($args);
    }

    /**
     * Get single feature by ID
     *
     * @param string $featureId Feature ID
     * @return array|\WP_Error
     */
    public function getFeature(string $featureId)
    {
        return $this->api->getFeature($featureId);
    }

    /**
     * Create a new feature request
     *
     * @param string $title Feature title
     * @param string $description Feature description (optional)
     * @return array|\WP_Error
     */
    public function createFeature(string $title, string $description = '')
    {
        if (!$this->canInteract()) {
            return new \WP_Error('wpfeatureloop_unauthorized', 'User cannot interact');
        }

        return $this->api->createFeature($title, $description);
    }

    /**
     * Vote for a feature
     *
     * @param string $featureId Feature ID
     * @return array|\WP_Error
     */
    public function vote(string $featureId)
    {
        if (!$this->canInteract()) {
            return new \WP_Error('wpfeatureloop_unauthorized', 'User cannot interact');
        }

        return $this->api->vote($featureId);
    }

    /**
     * Remove vote from a feature
     *
     * @param string $featureId Feature ID
     * @return array|\WP_Error
     */
    public function unvote(string $featureId)
    {
        if (!$this->canInteract()) {
            return new \WP_Error('wpfeatureloop_unauthorized', 'User cannot interact');
        }

        return $this->api->unvote($featureId);
    }

    /**
     * Add comment to a feature
     *
     * @param string $featureId Feature ID
     * @param string $content Comment content
     * @return array|\WP_Error
     */
    public function addComment(string $featureId, string $content)
    {
        if (!$this->canInteract()) {
            return new \WP_Error('wpfeatureloop_unauthorized', 'User cannot interact');
        }

        return $this->api->addComment($featureId, $content);
    }

    /**
     * Get current user's anonymous ID
     *
     * @return string
     */
    public function getUserId(): string
    {
        return User::getAnonymousId();
    }

    /**
     * Get current user's display name (real or pseudonym)
     *
     * @return string
     */
    public function getUserDisplayName(): string
    {
        return User::getDisplayName();
    }

    /**
     * Check if user has given consent to share personal data
     *
     * @return bool
     */
    public function hasUserConsent(): bool
    {
        return User::hasConsent();
    }

    /**
     * Get user consent status
     *
     * @return bool|null True if consented, false if declined, null if not decided
     */
    public function getUserConsentStatus(): ?bool
    {
        return User::getConsentStatus();
    }

    /**
     * Set user consent
     *
     * @param bool $consent Whether user consents to sharing data
     * @return bool Success
     */
    public function setUserConsent(bool $consent): bool
    {
        return User::setConsent($consent);
    }

    /**
     * Get the API client instance (for advanced usage)
     *
     * @return Api
     */
    public function getApi(): Api
    {
        return $this->api;
    }
}
