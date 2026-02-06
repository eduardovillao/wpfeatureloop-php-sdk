<?php
/**
 * Widget container template
 *
 * @var string $container_id
 * @var string $title
 * @var string $subtitle
 * @var bool $can_interact
 * @var string $suggest_feature_text
 * @var string $icon_plus
 * @var string $skeleton_html
 */

defined('ABSPATH') || exit;
?>
<div id="<?php echo esc_attr($container_id); ?>" class="wfl-container" data-loading="true">
    <div class="wfl-header">
        <div class="wfl-header-content">
            <h1 class="wfl-title"><?php echo esc_html($title); ?></h1>
            <p class="wfl-subtitle"><?php echo esc_html($subtitle); ?></p>
        </div>
        <?php if ($can_interact): ?>
            <button class="wfl-btn wfl-btn-primary wfl-ripple" disabled>
                <?php echo $icon_plus; ?>
                <?php echo esc_html($suggest_feature_text); ?>
            </button>
        <?php endif; ?>
    </div>
    <div class="wfl-list">
        <?php echo $skeleton_html; ?>
    </div>
</div>
