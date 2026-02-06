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
 * // Initialize client (REST API + assets registered automatically)
 * $client = new Client('pk_live_xxx', 'project_id');
 *
 * // Render widget (just this, nothing else!)
 * $widget = new Widget($client, ['locale' => 'en']);
 * echo $widget->render();
 * ```
 */
class Client
{
    /**
     * SDK Version (used for cache busting)
     */
    public const VERSION = '1.0.0';

    /**
     * Script/style handle
     */
    public const HANDLE = 'wpfeatureloop';

    /**
     * API client instance
     */
    private Api $api;

    /**
     * REST API handler
     */
    private RestApi $restApi;

    /**
     * Required capability for interactions
     */
    private string $capability;

    /**
     * Assets URL
     */
    private ?string $assetsUrl;

    /**
     * Whether assets have been registered
     */
    private static bool $assetsRegistered = false;

    /**
     * Constructor
     *
     * @param string $publicKey Public API key (starts with pk_live_)
     * @param string $projectId Project ID
     * @param array<string, mixed> $options Optional configuration
     *                                      - api_url: Custom API URL
     *                                      - capability: Required WP capability (default: 'read')
     *                                      - assets_url: URL to SDK assets folder (auto-detected if not provided)
     */
    public function __construct(string $publicKey, string $projectId, array $options = [])
    {
        $apiUrl = $options['api_url'] ?? null;
        $this->capability = $options['capability'] ?? 'read';

        // Auto-detect assets URL if not provided
        $this->assetsUrl = isset($options['assets_url'])
            ? rtrim($options['assets_url'], '/')
            : $this->detectAssetsUrl();

        $this->api = new Api($publicKey, $projectId, $apiUrl);
        $this->restApi = new RestApi($this);

        // Auto-register REST API routes
        add_action('rest_api_init', [$this->restApi, 'registerRoutes']);

        // Register assets (not enqueue) - only once
        if ($this->assetsUrl && !self::$assetsRegistered) {
            add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
            self::$assetsRegistered = true;
        }
    }

    /**
     * Auto-detect assets URL based on SDK location
     */
    private function detectAssetsUrl(): string
    {
        // SDK root is parent of src/ directory
        $sdkRoot = dirname(__DIR__);

        // Use composer.json as reference file for plugin_dir_url()
        $referenceFile = $sdkRoot . '/composer.json';

        // Get URL to SDK root, then append assets/
        return plugin_dir_url($referenceFile) . 'assets';
    }

    /**
     * Register CSS and JS assets (does not load them yet)
     */
    public function registerAssets(): void
    {
        if (!$this->assetsUrl) {
            return;
        }

        wp_register_style(
            self::HANDLE,
            $this->assetsUrl . '/css/wpfeatureloop.css',
            [],
            self::VERSION
        );

        wp_register_script(
            self::HANDLE,
            $this->assetsUrl . '/js/wpfeatureloop.js',
            [],
            self::VERSION,
            true
        );
    }

    /**
     * Enqueue the registered assets (call this when rendering widget)
     */
    public function enqueueAssets(): void
    {
        wp_enqueue_style(self::HANDLE);
        wp_enqueue_script(self::HANDLE);
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
