<?php
/**
 * Empty state template
 *
 * @var string $icon_empty
 * @var string $empty_title
 * @var string $empty_text
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-empty">
    <div class="wfl-empty-icon"><?php echo $icon_empty; ?></div>
    <h3 class="wfl-empty-title"><?php echo esc_html($empty_title); ?></h3>
    <p class="wfl-empty-text"><?php echo esc_html($empty_text); ?></p>
</div>
