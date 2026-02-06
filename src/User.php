<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * User Handler
 *
 * Handles user identification, consent, and privacy.
 * All sensitive data is processed here before being sent to the API.
 */
class User
{
    /**
     * Meta key for storing anonymous UUID
     */
    public const META_UUID = 'wpfeatureloop_uuid';

    /**
     * Meta key for storing consent status
     */
    public const META_CONSENT = 'wpfeatureloop_consent';

    /**
     * Meta key for storing consent timestamp
     */
    public const META_CONSENT_AT = 'wpfeatureloop_consent_at';

    /**
     * Get anonymous user ID (hash)
     *
     * Cross-project: same email = same ID across different projects.
     * Falls back to UUID if user has no email.
     *
     * @return string Hashed user identifier (empty if not logged in)
     */
    public static function getAnonymousId(): string
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $user = wp_get_current_user();

        // If user has email, use hash of email (cross-project identification)
        if (!empty($user->user_email)) {
            return hash('sha256', strtolower(trim($user->user_email)));
        }

        // Fallback: UUID unique to this user
        $uuid = get_user_meta($user->ID, self::META_UUID, true);

        if (empty($uuid)) {
            $uuid = wp_generate_uuid4();
            update_user_meta($user->ID, self::META_UUID, $uuid);
        }

        return hash('sha256', $uuid);
    }

    /**
     * Get display name based on consent
     *
     * With consent: real name from WordPress
     * Without consent: pseudonym like "J-a3f2"
     *
     * @return string Display name or pseudonym
     */
    public static function getDisplayName(): string
    {
        if (!is_user_logged_in()) {
            return 'Anonymous';
        }

        $user = wp_get_current_user();

        if (self::hasConsent()) {
            return $user->display_name ?: 'Anonymous';
        }

        // Generate pseudonym: first letter + 4 chars of hash
        $firstName = strtoupper(substr($user->display_name, 0, 1)) ?: 'U';
        $hashPart = substr(self::getAnonymousId(), 0, 4);

        return $firstName . '-' . $hashPart;
    }

    /**
     * Get user email (only if consented)
     *
     * @return string Email or empty string
     */
    public static function getEmail(): string
    {
        if (!is_user_logged_in() || !self::hasConsent()) {
            return '';
        }

        $user = wp_get_current_user();
        return $user->user_email ?: '';
    }

    /**
     * Check if user has given consent
     *
     * @return bool
     */
    public static function hasConsent(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $consent = get_user_meta(get_current_user_id(), self::META_CONSENT, true);
        return $consent === 'yes';
    }

    /**
     * Get consent status
     *
     * @return bool|null True if consented, false if declined, null if not decided
     */
    public static function getConsentStatus(): ?bool
    {
        if (!is_user_logged_in()) {
            return null;
        }

        $consent = get_user_meta(get_current_user_id(), self::META_CONSENT, true);

        if ($consent === 'yes') {
            return true;
        }

        if ($consent === 'no') {
            return false;
        }

        return null;
    }

    /**
     * Set user consent
     *
     * @param bool $consent Whether user consents to sharing data
     * @return bool Success
     */
    public static function setConsent(bool $consent): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $userId = get_current_user_id();

        update_user_meta($userId, self::META_CONSENT, $consent ? 'yes' : 'no');

        if ($consent) {
            update_user_meta($userId, self::META_CONSENT_AT, current_time('mysql'));
        }

        return true;
    }

    /**
     * Check if current user can interact
     *
     * @param string $capability Required capability (default: 'read')
     * @return bool
     */
    public static function canInteract(string $capability = 'read'): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        return current_user_can($capability);
    }

    /**
     * Get all user headers for API requests
     *
     * @return array<string, string> Headers array
     */
    public static function getHeaders(): array
    {
        return [
            'X-User-Id'      => self::getAnonymousId(),
            'X-User-Name'    => self::getDisplayName(),
            'X-User-Email'   => self::getEmail(),
            'X-User-Consent' => self::hasConsent() ? 'true' : 'false',
        ];
    }
}
