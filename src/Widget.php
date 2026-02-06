<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * Widget Renderer
 *
 * Renders the feature voting widget with the same UI/UX as the JS SDK.
 */
class Widget
{
    /**
     * Client instance
     */
    private Client $client;

    /**
     * Widget configuration
     */
    private array $config;

    /**
     * Translations
     */
    private array $translations;

    /**
     * SVG Icons
     */
    private const ICONS = [
        'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
        'arrowUp' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>',
        'arrowDown' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
        'comment' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'close' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        'send' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="m22 2-11 11"/></svg>',
        'empty' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'error' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
    ];

    /**
     * Default translations
     */
    private const DEFAULT_TRANSLATIONS = [
        'en' => [
            'title' => "What's Next?",
            'subtitle' => 'Help us build what matters to you',
            'suggestFeature' => 'Suggest Feature',
            'suggestTitle' => 'Suggest a Feature',
            'titleLabel' => 'Title',
            'titlePlaceholder' => 'Brief description of your feature idea',
            'descriptionLabel' => 'Description',
            'descriptionPlaceholder' => 'Explain the feature and why it would be valuable...',
            'cancel' => 'Cancel',
            'submit' => 'Submit Feature',
            'comments' => 'comments',
            'comment' => 'comment',
            'addComment' => 'Add a comment...',
            'noComments' => 'No comments yet. Be the first to share your thoughts!',
            'emptyTitle' => 'No features yet',
            'emptyText' => 'Be the first to suggest a feature!',
            'errorTitle' => 'Failed to load features',
            'errorText' => 'Please try again later.',
            'retry' => 'Retry',
            'fillAllFields' => 'Please fill in all fields',
            'featureSubmitted' => 'Feature submitted successfully!',
            'commentAdded' => 'Comment added!',
            'voteSaved' => 'Vote saved!',
            'statusOpen' => 'Open',
            'statusPlanned' => 'Planned',
            'statusProgress' => 'In Progress',
            'statusCompleted' => 'Completed',
            'upvote' => 'Upvote',
            'downvote' => 'Downvote',
        ],
        'pt-BR' => [
            'title' => 'O que vem por aí?',
            'subtitle' => 'Ajude-nos a construir o que importa para você',
            'suggestFeature' => 'Sugerir Feature',
            'suggestTitle' => 'Sugerir uma Feature',
            'titleLabel' => 'Título',
            'titlePlaceholder' => 'Breve descrição da sua ideia',
            'descriptionLabel' => 'Descrição',
            'descriptionPlaceholder' => 'Explique a feature e por que seria valiosa...',
            'cancel' => 'Cancelar',
            'submit' => 'Enviar Feature',
            'comments' => 'comentários',
            'comment' => 'comentário',
            'addComment' => 'Adicionar um comentário...',
            'noComments' => 'Nenhum comentário ainda. Seja o primeiro!',
            'emptyTitle' => 'Nenhuma feature ainda',
            'emptyText' => 'Seja o primeiro a sugerir uma feature!',
            'errorTitle' => 'Erro ao carregar features',
            'errorText' => 'Por favor, tente novamente mais tarde.',
            'retry' => 'Tentar novamente',
            'fillAllFields' => 'Por favor, preencha todos os campos',
            'featureSubmitted' => 'Feature enviada com sucesso!',
            'commentAdded' => 'Comentário adicionado!',
            'voteSaved' => 'Voto salvo!',
            'statusOpen' => 'Aberto',
            'statusPlanned' => 'Planejado',
            'statusProgress' => 'Em Progresso',
            'statusCompleted' => 'Concluído',
            'upvote' => 'Votar a favor',
            'downvote' => 'Votar contra',
        ],
    ];

    /**
     * Constructor
     *
     * @param Client $client Client instance
     * @param array $config Widget configuration
     *                      - locale: 'en' or 'pt-BR' (default: 'en')
     *                      - container_id: HTML container ID (default: 'wpfeatureloop')
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->config = array_merge([
            'locale' => 'en',
            'container_id' => 'wpfeatureloop',
        ], $config);

        $locale = $this->config['locale'];
        $this->translations = self::DEFAULT_TRANSLATIONS[$locale] ?? self::DEFAULT_TRANSLATIONS['en'];
    }

    /**
     * Get translation
     */
    private function t(string $key): string
    {
        return $this->translations[$key] ?? $key;
    }

    /**
     * Get icon SVG
     */
    private function icon(string $name): string
    {
        return self::ICONS[$name] ?? '';
    }

    /**
     * Render the widget container (skeleton)
     *
     * @return string HTML
     */
    public function render(): string
    {
        $containerId = esc_attr($this->config['container_id']);

        return sprintf(
            '<div id="%s" class="wfl-container" data-loading="true">%s</div>',
            $containerId,
            $this->renderSkeleton()
        );
    }

    /**
     * Render skeleton loading state
     */
    private function renderSkeleton(): string
    {
        $skeletonCard = '
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
        ';

        $canCreate = $this->client->canInteract();
        $addButton = $canCreate ? sprintf(
            '<button class="wfl-btn wfl-btn-primary wfl-ripple" disabled>%s %s</button>',
            $this->icon('plus'),
            esc_html($this->t('suggestFeature'))
        ) : '';

        return sprintf(
            '
            <div class="wfl-header">
                <div class="wfl-header-content">
                    <h1 class="wfl-title">%s</h1>
                    <p class="wfl-subtitle">%s</p>
                </div>
                %s
            </div>
            <div class="wfl-list">%s</div>
            ',
            esc_html($this->t('title')),
            esc_html($this->t('subtitle')),
            $addButton,
            $skeletonCard
        );
    }

    /**
     * Render a feature card
     *
     * @param array $feature Feature data
     * @return string HTML
     */
    public function renderCard(array $feature): string
    {
        $id = esc_attr($feature['id']);
        $title = esc_html($feature['title']);
        $description = esc_html($feature['description'] ?? '');
        $votes = (int) ($feature['votes'] ?? 0);
        $commentsCount = (int) ($feature['commentsCount'] ?? 0);
        $status = $feature['status'] ?? 'open';
        $userVote = $feature['userVote'] ?? null;

        $voteClass = $votes > 0 ? 'wfl-vote-positive' : ($votes < 0 ? 'wfl-vote-negative' : '');
        $upVoted = $userVote === 'up';
        $downVoted = $userVote === 'down';
        $commentText = $commentsCount === 1 ? $this->t('comment') : $this->t('comments');

        return sprintf(
            '
            <div class="wfl-card" data-id="%s">
                <div class="wfl-vote">
                    <button class="wfl-vote-btn wfl-vote-up wfl-tooltip %s" data-id="%s" data-action="up" data-tooltip="%s">
                        %s
                    </button>
                    <span class="wfl-vote-count %s" data-id="%s">%d</span>
                    <button class="wfl-vote-btn wfl-vote-down wfl-tooltip %s" data-id="%s" data-action="down" data-tooltip="%s">
                        %s
                    </button>
                </div>
                <div class="wfl-content">
                    <div class="wfl-content-header">
                        <h3 class="wfl-feature-title" data-id="%s">%s</h3>
                        %s
                    </div>
                    <p class="wfl-description">%s</p>
                    <div class="wfl-footer">
                        <button class="wfl-meta wfl-comment-trigger" data-id="%s">
                            %s
                            <span>%d %s</span>
                        </button>
                    </div>
                </div>
            </div>
            ',
            $id,
            $upVoted ? 'wfl-voted' : '',
            $id,
            esc_attr($this->t('upvote')),
            $this->icon('arrowUp'),
            $voteClass,
            $id,
            $votes,
            $downVoted ? 'wfl-voted' : '',
            $id,
            esc_attr($this->t('downvote')),
            $this->icon('arrowDown'),
            $id,
            $title,
            $this->renderStatus($status),
            $description,
            $id,
            $this->icon('comment'),
            $commentsCount,
            esc_html($commentText)
        );
    }

    /**
     * Render status badge
     */
    private function renderStatus(string $status): string
    {
        $labels = [
            'open' => $this->t('statusOpen'),
            'planned' => $this->t('statusPlanned'),
            'progress' => $this->t('statusProgress'),
            'completed' => $this->t('statusCompleted'),
        ];

        $label = $labels[$status] ?? $status;

        return sprintf(
            '<span class="wfl-status wfl-status-%s"><span class="wfl-status-dot"></span>%s</span>',
            esc_attr($status),
            esc_html($label)
        );
    }

    /**
     * Render empty state
     */
    public function renderEmpty(): string
    {
        return sprintf(
            '
            <div class="wfl-empty">
                <div class="wfl-empty-icon">%s</div>
                <h3 class="wfl-empty-title">%s</h3>
                <p class="wfl-empty-text">%s</p>
            </div>
            ',
            $this->icon('empty'),
            esc_html($this->t('emptyTitle')),
            esc_html($this->t('emptyText'))
        );
    }

    /**
     * Render error state
     */
    public function renderError(): string
    {
        return sprintf(
            '
            <div class="wfl-error">
                <div class="wfl-error-icon">%s</div>
                <h3 class="wfl-error-title">%s</h3>
                <p class="wfl-error-text">%s</p>
                <button class="wfl-btn wfl-btn-primary" id="wfl-retry">%s</button>
            </div>
            ',
            $this->icon('error'),
            esc_html($this->t('errorTitle')),
            esc_html($this->t('errorText')),
            esc_html($this->t('retry'))
        );
    }

    /**
     * Render feature creation modal
     */
    public function renderModal(): string
    {
        if (!$this->client->canInteract()) {
            return '';
        }

        return sprintf(
            '
            <div class="wfl-modal-overlay" id="wfl-modal">
                <div class="wfl-modal">
                    <div class="wfl-modal-header">
                        <h2 class="wfl-modal-title">%s</h2>
                        <button class="wfl-modal-close" id="wfl-modal-close">%s</button>
                    </div>
                    <div class="wfl-modal-body">
                        <div class="wfl-form-group">
                            <label class="wfl-label" for="wfl-feature-title">%s</label>
                            <input type="text" class="wfl-input" id="wfl-feature-title" placeholder="%s">
                        </div>
                        <div class="wfl-form-group">
                            <label class="wfl-label" for="wfl-feature-desc">%s</label>
                            <textarea class="wfl-textarea" id="wfl-feature-desc" placeholder="%s"></textarea>
                        </div>
                    </div>
                    <div class="wfl-modal-footer">
                        <button class="wfl-btn wfl-btn-secondary" id="wfl-modal-cancel">%s</button>
                        <button class="wfl-btn wfl-btn-primary wfl-ripple" id="wfl-modal-submit">%s</button>
                    </div>
                </div>
            </div>
            ',
            esc_html($this->t('suggestTitle')),
            $this->icon('close'),
            esc_html($this->t('titleLabel')),
            esc_attr($this->t('titlePlaceholder')),
            esc_html($this->t('descriptionLabel')),
            esc_attr($this->t('descriptionPlaceholder')),
            esc_html($this->t('cancel')),
            esc_html($this->t('submit'))
        );
    }

    /**
     * Render comments modal
     */
    public function renderCommentModal(): string
    {
        return sprintf(
            '
            <div class="wfl-modal-overlay" id="wfl-comment-modal">
                <div class="wfl-modal">
                    <div class="wfl-modal-header">
                        <h2 class="wfl-modal-title" id="wfl-comment-title">%s</h2>
                        <button class="wfl-modal-close" id="wfl-comment-modal-close">%s</button>
                    </div>
                    <div class="wfl-modal-body">
                        <div class="wfl-comments-list" id="wfl-comments-list"></div>
                        <div class="wfl-comment-input-wrapper">
                            <input type="text" class="wfl-comment-input" id="wfl-comment-input" placeholder="%s">
                            <button class="wfl-comment-submit" id="wfl-comment-submit">%s</button>
                        </div>
                    </div>
                </div>
            </div>
            ',
            esc_html($this->t('comments')),
            $this->icon('close'),
            esc_attr($this->t('addComment')),
            $this->icon('send')
        );
    }

    /**
     * Render toast notification container
     */
    public function renderToast(): string
    {
        return '<div class="wfl-toast" id="wfl-toast"></div>';
    }

    /**
     * Get all translations for JS
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Get icons for JS
     */
    public function getIcons(): array
    {
        return self::ICONS;
    }
}
