<?php
/**
 * HTML templates for JavaScript consumption
 *
 * These <template> tags are cloned and populated by JS.
 * No inline HTML strings in JS!
 *
 * @var array $icons
 * @var array $translations
 */

defined('ABSPATH') || exit;
?>

<template id="wfl-template-card">
    <div class="wfl-card" data-id="">
        <div class="wfl-vote">
            <button class="wfl-vote-btn wfl-vote-up wfl-tooltip"
                    data-id=""
                    data-action="up"
                    data-tooltip="">
                <?php echo $icons['arrowUp']; ?>
            </button>
            <span class="wfl-vote-count" data-id=""></span>
            <button class="wfl-vote-btn wfl-vote-down wfl-tooltip"
                    data-id=""
                    data-action="down"
                    data-tooltip="">
                <?php echo $icons['arrowDown']; ?>
            </button>
        </div>
        <div class="wfl-content">
            <div class="wfl-content-header">
                <h3 class="wfl-feature-title" data-id=""></h3>
                <span class="wfl-status">
                    <span class="wfl-status-dot"></span>
                    <span class="wfl-status-label"></span>
                </span>
            </div>
            <p class="wfl-description"></p>
            <div class="wfl-footer">
                <button class="wfl-meta wfl-comment-trigger" data-id="">
                    <?php echo $icons['comment']; ?>
                    <span class="wfl-comment-count"></span>
                </button>
            </div>
        </div>
    </div>
</template>

<template id="wfl-template-status">
    <span class="wfl-status">
        <span class="wfl-status-dot"></span>
        <span class="wfl-status-label"></span>
    </span>
</template>

<template id="wfl-template-empty">
    <div class="wfl-empty">
        <div class="wfl-empty-icon"><?php echo $icons['empty']; ?></div>
        <h3 class="wfl-empty-title"><?php echo esc_html($translations['emptyTitle']); ?></h3>
        <p class="wfl-empty-text"><?php echo esc_html($translations['emptyText']); ?></p>
    </div>
</template>

<template id="wfl-template-error">
    <div class="wfl-error">
        <div class="wfl-error-icon"><?php echo $icons['error']; ?></div>
        <h3 class="wfl-error-title"><?php echo esc_html($translations['errorTitle']); ?></h3>
        <p class="wfl-error-text"><?php echo esc_html($translations['errorText']); ?></p>
        <button class="wfl-btn wfl-btn-primary wfl-retry-btn">
            <?php echo esc_html($translations['retry']); ?>
        </button>
    </div>
</template>

<template id="wfl-template-comment">
    <div class="wfl-comment">
        <div class="wfl-comment-avatar"></div>
        <div class="wfl-comment-content">
            <div class="wfl-comment-header">
                <span class="wfl-comment-author"></span>
                <span class="wfl-comment-team-badge" style="display: none;">Team</span>
                <span class="wfl-comment-time"></span>
            </div>
            <p class="wfl-comment-text"></p>
        </div>
    </div>
</template>

<template id="wfl-template-no-comments">
    <p class="wfl-no-comments"><?php echo esc_html($translations['noComments']); ?></p>
</template>

<template id="wfl-template-header">
    <div class="wfl-header">
        <div class="wfl-header-content">
            <h1 class="wfl-title"><?php echo esc_html($translations['title']); ?></h1>
            <p class="wfl-subtitle"><?php echo esc_html($translations['subtitle']); ?></p>
        </div>
        <button class="wfl-btn wfl-btn-primary wfl-ripple wfl-add-feature-btn" style="display: none;">
            <?php echo $icons['plus']; ?>
            <?php echo esc_html($translations['suggestFeature']); ?>
        </button>
    </div>
</template>

<template id="wfl-template-skeleton">
    <div class="wfl-skeleton-card">
        <div class="wfl-skeleton-vote">
            <div class="wfl-skeleton wfl-skeleton-vote-btn"></div>
            <div class="wfl-skeleton wfl-skeleton-vote-count"></div>
            <div class="wfl-skeleton wfl-skeleton-vote-btn"></div>
        </div>
        <div class="wfl-skeleton-content">
            <div class="wfl-skeleton wfl-skeleton-title"></div>
            <div class="wfl-skeleton wfl-skeleton-desc"></div>
            <div class="wfl-skeleton wfl-skeleton-desc-2"></div>
            <div class="wfl-skeleton-footer">
                <div class="wfl-skeleton wfl-skeleton-meta"></div>
                <div class="wfl-skeleton wfl-skeleton-tag"></div>
            </div>
        </div>
    </div>
</template>
