<?php
/**
 * HTML templates for JavaScript consumption
 *
 * These <template> tags are cloned and populated by JS.
 * SVGs are inline - JS clones the whole template including icons.
 *
 * @var array $translations
 */

defined('ABSPATH') || exit;
?>

<template id="wfl-template-card">
    <div class="wfl-card" data-id="">
        <div class="wfl-vote">
            <button class="wfl-vote-btn wfl-vote-up wfl-tooltip" data-id="" data-action="up" data-tooltip="">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
            </button>
            <span class="wfl-vote-count" data-id=""></span>
            <button class="wfl-vote-btn wfl-vote-down wfl-tooltip" data-id="" data-action="down" data-tooltip="">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
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
        <div class="wfl-empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3 class="wfl-empty-title"><?php echo esc_html($translations['emptyTitle']); ?></h3>
        <p class="wfl-empty-text"><?php echo esc_html($translations['emptyText']); ?></p>
    </div>
</template>

<template id="wfl-template-error">
    <div class="wfl-error">
        <div class="wfl-error-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
        </div>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
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
