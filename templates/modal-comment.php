<?php
/**
 * Comments modal template
 *
 * @var string $comments_title
 * @var string $add_comment_placeholder
 * @var string $icon_close
 * @var string $icon_send
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-modal-overlay" id="wfl-comment-modal">
    <div class="wfl-modal">
        <div class="wfl-modal-header">
            <h2 class="wfl-modal-title" id="wfl-comment-title">
                <?php echo esc_html($comments_title); ?>
            </h2>
            <button class="wfl-modal-close" id="wfl-comment-modal-close">
                <?php echo $icon_close; ?>
            </button>
        </div>
        <div class="wfl-modal-body">
            <div class="wfl-comments-list" id="wfl-comments-list"></div>
            <div class="wfl-comment-input-wrapper">
                <input type="text"
                       class="wfl-comment-input"
                       id="wfl-comment-input"
                       placeholder="<?php echo esc_attr($add_comment_placeholder); ?>">
                <button class="wfl-comment-submit" id="wfl-comment-submit">
                    <?php echo $icon_send; ?>
                </button>
            </div>
        </div>
    </div>
</div>
