<?php
/**
 * Consent modal template
 *
 * Shown after a write action when the user has not decided yet.
 * Closing without choosing leaves consent undecided on purpose, so the
 * question comes back on the next visit.
 *
 * @var string $consent_title
 * @var string $consent_text
 * @var string $consent_fine_print
 * @var string $consent_accept
 * @var string $consent_decline
 * @var string $consent_dismiss
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-modal-overlay" id="wfl-consent-modal">
    <div class="wfl-modal wfl-consent-modal" role="dialog" aria-modal="true" aria-labelledby="wfl-consent-title">
        <button class="wfl-modal-close" id="wfl-consent-close" aria-label="<?php echo esc_attr($consent_dismiss); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <div class="wfl-modal-body">
            <div class="wfl-consent-modal-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0M22 8c0-2.3-.8-4.3-2-6M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326M4 2C2.8 3.7 2 5.7 2 8"/></svg>
            </div>
            <h2 class="wfl-consent-modal-title" id="wfl-consent-title"><?php echo esc_html($consent_title); ?></h2>
            <p class="wfl-consent-modal-text"><?php echo esc_html($consent_text); ?></p>
            <p class="wfl-consent-fineprint"><?php echo esc_html($consent_fine_print); ?></p>
        </div>
        <div class="wfl-modal-footer">
            <button class="wfl-btn wfl-btn-secondary" data-consent="deny"><?php echo esc_html($consent_decline); ?></button>
            <button class="wfl-btn wfl-btn-primary wfl-ripple" data-consent="grant"><?php echo esc_html($consent_accept); ?></button>
        </div>
    </div>
</div>
