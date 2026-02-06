<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * FeatureLoop Client
 *
 * Main entry point for the FeatureLoop SDK.
 *
 * STEP 1 - In your main plugin file (runs on every request):
 * ```php
 * use WPFeatureLoop\Client;
 *
 * // Initialize client early (registers REST API routes)
 * Client::init('pk_live_xxx', 'project_id');
 * ```
 *
 * STEP 2 - Where you want to render the widget:
 * ```php
 * use WPFeatureLoop\Client;
 *
 * // Render widget (that's it!)
 * echo Client::renderWidget(['locale' => 'en']);
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
     * Singleton instance
     */
    private static ?Client $instance = null;

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
     * Initialize the client (singleton pattern)
     *
     * Call this in your main plugin file so REST API routes are registered on every request.
     *
     * @param string $publicKey Public API key (starts with pk_live_)
     * @param string $projectId Project ID
     * @param array<string, mixed> $options Optional configuration
     * @return Client
     */
    public static function init(string $publicKey, string $projectId, array $options = []): Client
    {
        if (self::$instance === null) {
            self::$instance = new self($publicKey, $projectId, $options);
        }

        return self::$instance;
    }

    /**
     * Get the singleton instance
     *
     * @return Client|null Returns null if not initialized
     */
    public static function getInstance(): ?Client
    {
        return self::$instance;
    }

    /**
     * Render the widget (convenience static method)
     *
     * @param array $config Widget configuration (locale, container_id, etc.)
     * @return string HTML or empty string if not initialized
     */
    public static function renderWidget(array $config = []): string
    {
        if (self::$instance === null) {
            return '<!-- WPFeatureLoop: Client not initialized. Call Client::init() first. -->';
        }

        $widget = new Widget(self::$instance, $config);
        return $widget->render();
    }

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
    private function __construct(string $publicKey, string $projectId, array $options = [])
    {
        $apiUrl = $options['api_url'] ?? null;
        $this->capability = $options['capability'] ?? 'read';

        // Auto-detect assets URL if not provided
        $this->assetsUrl = isset($options['assets_url'])
            ? rtrim($options['assets_url'], '/')
            : $this->detectAssetsUrl();

        $this->api = new Api($publicKey, $projectId, $apiUrl);
        $this->restApi = new RestApi($this);

        // Register REST API routes
        $this->scheduleOrRun('rest_api_init', [$this->restApi, 'registerRoutes']);

        // Register assets (admin only for now)
        $this->scheduleOrRun('admin_enqueue_scripts', [$this, 'registerAssets']);
    }

    /**
     * Schedule a callback for a hook, or run immediately if hook already fired
     */
    private function scheduleOrRun(string $hook, callable $callback): void
    {
        if (did_action($hook)) {
            call_user_func($callback);
        } else {
            add_action($hook, $callback);
        }
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
     * Register CSS and JS assets (called via admin_enqueue_scripts hook)
     *
     * This registers assets early so they're available for enqueuing later.
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
     * Enqueue CSS and JS assets (called when widget renders)
     *
     * Assets must be registered first via registerAssets().
     */
    public function enqueueAssets(): void
    {
        // If not registered yet (edge case), register now
        if (!wp_style_is(self::HANDLE, 'registered')) {
            $this->registerAssets();
        }

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
