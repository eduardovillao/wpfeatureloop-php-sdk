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
    }

    /**
     * Auto-detect assets URL based on plugin location
     */
    private function detectAssetsUrl(): ?string
    {
        // Find the plugin that instantiated this Client using backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $wpPluginDir = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $file = $frame['file'];

            // Check if this file is in the plugins directory
            if (strpos($file, $wpPluginDir) === 0) {
                // Extract the plugin slug
                $relativePath = substr($file, strlen($wpPluginDir) + 1);
                $parts = explode('/', $relativePath);
                $pluginSlug = $parts[0];

                // Build URL assuming SDK is in vendor/eduardovillao/wpfeatureloop-sdk/
                return plugins_url(
                    'vendor/eduardovillao/wpfeatureloop-sdk/assets',
                    $wpPluginDir . '/' . $pluginSlug . '/plugin.php'
                );
            }
        }

        // Could not auto-detect
        return null;
    }

    /**
     * Enqueue CSS and JS assets (registers if not already registered)
     */
    public function enqueueAssets(): void
    {
        if (!$this->assetsUrl) {
            return;
        }

        // wp_enqueue_* will register automatically if not registered
        wp_enqueue_style(
            self::HANDLE,
            $this->assetsUrl . '/css/wpfeatureloop.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            self::HANDLE,
            $this->assetsUrl . '/js/wpfeatureloop.js',
            [],
            self::VERSION,
            true
        );
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
