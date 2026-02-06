<?php
/**
 * Feature creation modal template
 *
 * @var string $suggest_title
 * @var string $title_label
 * @var string $title_placeholder
 * @var string $description_label
 * @var string $description_placeholder
 * @var string $cancel_text
 * @var string $submit_text
 * @var string $icon_close
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-modal-overlay" id="wfl-modal">
    <div class="wfl-modal">
        <div class="wfl-modal-header">
            <h2 class="wfl-modal-title"><?php echo esc_html($suggest_title); ?></h2>
            <button class="wfl-modal-close" id="wfl-modal-close">
                <?php echo $icon_close; ?>
            </button>
        </div>
        <div class="wfl-modal-body">
            <div class="wfl-form-group">
                <label class="wfl-label" for="wfl-feature-title">
                    <?php echo esc_html($title_label); ?>
                </label>
                <input type="text"
                       class="wfl-input"
                       id="wfl-feature-title"
                       placeholder="<?php echo esc_attr($title_placeholder); ?>">
            </div>
            <div class="wfl-form-group">
                <label class="wfl-label" for="wfl-feature-desc">
                    <?php echo esc_html($description_label); ?>
                </label>
                <textarea class="wfl-textarea"
                          id="wfl-feature-desc"
                          placeholder="<?php echo esc_attr($description_placeholder); ?>"></textarea>
            </div>
        </div>
        <div class="wfl-modal-footer">
            <button class="wfl-btn wfl-btn-secondary" id="wfl-modal-cancel">
                <?php echo esc_html($cancel_text); ?>
            </button>
            <button class="wfl-btn wfl-btn-primary wfl-ripple" id="wfl-modal-submit">
                <?php echo esc_html($submit_text); ?>
            </button>
        </div>
    </div>
</div>
