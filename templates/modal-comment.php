<?php
/**
 * Comments modal template
 *
 * @var string $comments_title
 * @var string $add_comment_placeholder
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="m22 2-11 11"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
