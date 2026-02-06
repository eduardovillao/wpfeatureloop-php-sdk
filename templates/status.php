<?php
/**
 * Status badge template
 *
 * @var string $status
 * @var string $label
 */

defined('ABSPATH') || exit;
?>
<span class="wfl-status wfl-status-<?php echo esc_attr($status); ?>">
    <span class="wfl-status-dot"></span>
    <?php echo esc_html($label); ?>
</span>
