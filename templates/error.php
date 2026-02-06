<?php
/**
 * Error state template
 *
 * @var string $icon_error
 * @var string $error_title
 * @var string $error_text
 * @var string $retry_text
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-error">
    <div class="wfl-error-icon"><?php echo $icon_error; ?></div>
    <h3 class="wfl-error-title"><?php echo esc_html($error_title); ?></h3>
    <p class="wfl-error-text"><?php echo esc_html($error_text); ?></p>
    <button class="wfl-btn wfl-btn-primary" id="wfl-retry">
        <?php echo esc_html($retry_text); ?>
    </button>
</div>
