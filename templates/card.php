<?php
/**
 * Feature card template
 *
 * @var string $id
 * @var string $title
 * @var string $description
 * @var int $votes
 * @var int $comments_count
 * @var string $status
 * @var string|null $user_vote
 * @var string $vote_class
 * @var bool $up_voted
 * @var bool $down_voted
 * @var string $comment_text
 * @var string $upvote_tooltip
 * @var string $downvote_tooltip
 * @var string $icon_arrow_up
 * @var string $icon_arrow_down
 * @var string $icon_comment
 * @var string $status_html
 */

defined('ABSPATH') || exit;
?>
<div class="wfl-card" data-id="<?php echo esc_attr($id); ?>">
    <div class="wfl-vote">
        <button class="wfl-vote-btn wfl-vote-up wfl-tooltip <?php echo $up_voted ? 'wfl-voted' : ''; ?>"
                data-id="<?php echo esc_attr($id); ?>"
                data-action="up"
                data-tooltip="<?php echo esc_attr($upvote_tooltip); ?>">
            <?php echo $icon_arrow_up; ?>
        </button>
        <span class="wfl-vote-count <?php echo esc_attr($vote_class); ?>" data-id="<?php echo esc_attr($id); ?>">
            <?php echo (int) $votes; ?>
        </span>
        <button class="wfl-vote-btn wfl-vote-down wfl-tooltip <?php echo $down_voted ? 'wfl-voted' : ''; ?>"
                data-id="<?php echo esc_attr($id); ?>"
                data-action="down"
                data-tooltip="<?php echo esc_attr($downvote_tooltip); ?>">
            <?php echo $icon_arrow_down; ?>
        </button>
    </div>
    <div class="wfl-content">
        <div class="wfl-content-header">
            <h3 class="wfl-feature-title" data-id="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($title); ?>
            </h3>
            <?php echo $status_html; ?>
        </div>
        <p class="wfl-description"><?php echo esc_html($description); ?></p>
        <div class="wfl-footer">
            <button class="wfl-meta wfl-comment-trigger" data-id="<?php echo esc_attr($id); ?>">
                <?php echo $icon_comment; ?>
                <span><?php echo (int) $comments_count; ?> <?php echo esc_html($comment_text); ?></span>
            </button>
        </div>
    </div>
</div>
