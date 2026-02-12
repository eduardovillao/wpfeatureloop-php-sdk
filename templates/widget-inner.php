<?php
/**
 * Widget inner content (header + skeleton)
 *
 * Rendered inside the container div.
 *
 * @var string $title
 * @var string $subtitle
 * @var bool $can_interact
 * @var string $suggest_feature_text
 * @var string $skeleton_html
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-header">
    <div class="wfl-header-content">
        <h1 class="wfl-title"><?php echo esc_html($title); ?></h1>
        <p class="wfl-subtitle"><?php echo esc_html($subtitle); ?></p>
    </div>
    <?php if ($can_interact): ?>
        <button class="wfl-btn wfl-btn-primary wfl-ripple" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            <?php echo esc_html($suggest_feature_text); ?>
        </button>
    <?php endif; ?>
</div>
<div class="wfl-list">
    <?php echo $skeleton_html; ?>
</div>
