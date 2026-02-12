<?php

declare(strict_types=1);

namespace WPFeatureLoop;

use WPFeatureLoop\Widget;
use WPFeatureLoop\RestApi;
use WPFeatureLoop\Api;
use WPFeatureLoop\User;

/**
 * FeatureLoop Client
 *
 * Main entry point for the FeatureLoop SDK.
 * Supports multiple instances (one per project) on the same WordPress site.
 *
 * STEP 1 - In your main plugin file:
 * ```php
 * use WPFeatureLoop\Client;
 *
 * Client::init('pk_live_xxx', 'project_id');
 * ```
 *
 * STEP 2 - Where you want to render the widget:
 * ```php
 * use WPFeatureLoop\Client;
 *
 * echo Client::renderWidget();
 * ```
 */
class Client
{
    /**
     * SDK Version (used for cache busting)
     */
    public const VERSION = '1.2.0';

    /**
     * Script/style handle (shared across instances — same files)
     */
    public const HANDLE = 'wpfeatureloop';

    /**
     * Registry of instances by projectId
     *
     * @var array<string, Client>
     */
    private static array $instances = [];

    /**
     * Whether REST API routes have been registered
     */
    private static bool $routesRegistered = false;

    /**
     * API client instance
     */
    private Api $api;

    /**
     * Project ID
     */
    private string $projectId;

    /**
     * Required capability for interactions
     */
    private string $capability;

    /**
     * Language for translations
     */
    private string $language;

    /**
     * Assets URL
     */
    private string $assetsUrl;

    /**
     * Initialize the client (registry pattern — one instance per projectId)
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
        if (!isset(self::$instances[$projectId])) {
            self::$instances[$projectId] = new self($publicKey, $projectId, $options);
        }

        return self::$instances[$projectId];
    }

    /**
     * Get an instance from the registry
     *
     * @param string|null $projectId Project ID (null returns last registered instance)
     * @return Client|null Returns null if not initialized
     */
    public static function getInstance(?string $projectId = null): ?Client
    {
        if ($projectId !== null) {
            return self::$instances[$projectId] ?? null;
        }

        // Backward compat: return last registered instance
        return !empty(self::$instances) ? end(self::$instances) : null;
    }

    /**
     * Render the widget
     *
     * @return string HTML
     */
    public function renderWidget(): string
    {
        $widget = new Widget($this);
        return $widget->render();
    }

    /**
     * Get project ID
     *
     * @return string
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    /**
     * Get configured language
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Constructor
     *
     * @param string $publicKey Public API key (starts with pk_live_)
     * @param string $projectId Project ID
     * @param array<string, mixed> $options Optional configuration
     *                                      - language: 'en' or 'pt-BR' (default: 'en')
     *                                      - capability: Required WP capability (default: 'read')
     */
    private function __construct(string $publicKey, string $projectId, array $options = [])
    {
        $apiUrl = $options['api_url'] ?? null;
        $this->capability = $options['capability'] ?? 'read';
        $this->language = $options['language'] ?? 'en';
        $this->projectId = $projectId;

        // Assets URL based on SDK location
        $this->assetsUrl = plugin_dir_url(dirname(__DIR__) . '/include.php') . 'assets';

        $this->api = new Api($publicKey, $projectId, $apiUrl);

        // Register REST API routes only once (shared across all instances)
        if (!self::$routesRegistered) {
            self::$routesRegistered = true;
            $restApi = new RestApi();
            $this->scheduleOrRun('rest_api_init', [$restApi, 'registerRoutes']);
        }

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
     * Register JS asset (called via admin_enqueue_scripts hook)
     *
     * CSS is inlined by Widget to avoid FOUC.
     */
    public function registerAssets(): void
    {
        wp_register_script(
            self::HANDLE,
            $this->assetsUrl . '/js/wpfeatureloop.min.js',
            [],
            self::VERSION,
            true
        );
    }

    /**
     * Enqueue JS asset (called when widget renders)
     *
     * CSS is handled inline by Widget::render().
     */
    public function enqueueAssets(): void
    {
        if (!wp_script_is(self::HANDLE, 'registered')) {
            $this->registerAssets();
        }

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
     * Vote on a feature
     *
     * @param string $featureId Feature ID
     * @param string $vote Vote type: 'up', 'down', or 'none'
     * @return array|\WP_Error
     */
    public function vote(string $featureId, string $vote = 'up')
    {
        if (!$this->canInteract()) {
            return new \WP_Error('wpfeatureloop_unauthorized', 'User cannot interact');
        }

        return $this->api->vote($featureId, $vote);
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
     * Get comments for a feature
     *
     * @param string $featureId Feature ID
     * @return array|\WP_Error
     */
    public function getComments(string $featureId)
    {
        return $this->api->getComments($featureId);
    }

    /**
     * Add comment to a feature
     *
     * @param string $featureId Feature ID
     * @param string $text Comment text
     * @return array|\WP_Error
     */
    public function addComment(string $featureId, string $text)
    {
        if (!$this->canInteract()) {
            return new \WP_Error('wpfeatureloop_unauthorized', 'User cannot interact');
        }

        return $this->api->addComment($featureId, $text);
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
