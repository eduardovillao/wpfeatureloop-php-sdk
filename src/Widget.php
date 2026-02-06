<?php

declare(strict_types=1);

namespace WPFeatureLoop;

/**
 * Widget Renderer
 *
 * Renders the feature voting widget with the same UI/UX as the JS SDK.
 * Uses template files instead of inline HTML strings.
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
     * Path to templates directory
     */
    private string $templatesPath;

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
     *                      - templates_path: Custom templates directory (optional)
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->config = array_merge([
            'locale' => 'en',
            'container_id' => 'wpfeatureloop',
            'templates_path' => null,
        ], $config);

        $locale = $this->config['locale'];
        $this->translations = self::DEFAULT_TRANSLATIONS[$locale] ?? self::DEFAULT_TRANSLATIONS['en'];

        // Default templates path is relative to this file
        $this->templatesPath = $this->config['templates_path']
            ?? dirname(__DIR__) . '/templates';
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
     * Render a template file with variables
     */
    private function renderTemplate(string $template, array $vars = []): string
    {
        $templateFile = $this->templatesPath . '/' . $template . '.php';

        if (!file_exists($templateFile)) {
            return "<!-- Template not found: {$template} -->";
        }

        // Extract variables to local scope
        extract($vars, EXTR_SKIP);

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Render the widget container (skeleton)
     *
     * @return string HTML
     */
    public function render(): string
    {
        $skeletonHtml = $this->renderTemplate('skeleton');

        return $this->renderTemplate('widget', [
            'container_id' => $this->config['container_id'],
            'title' => $this->t('title'),
            'subtitle' => $this->t('subtitle'),
            'can_interact' => $this->client->canInteract(),
            'suggest_feature_text' => $this->t('suggestFeature'),
            'icon_plus' => $this->icon('plus'),
            'skeleton_html' => $skeletonHtml,
        ]);
    }

    /**
     * Render a feature card
     *
     * @param array $feature Feature data
     * @return string HTML
     */
    public function renderCard(array $feature): string
    {
        $votes = (int) ($feature['votes'] ?? 0);
        $commentsCount = (int) ($feature['commentsCount'] ?? 0);
        $userVote = $feature['userVote'] ?? null;

        return $this->renderTemplate('card', [
            'id' => $feature['id'],
            'title' => $feature['title'],
            'description' => $feature['description'] ?? '',
            'votes' => $votes,
            'comments_count' => $commentsCount,
            'status' => $feature['status'] ?? 'open',
            'user_vote' => $userVote,
            'vote_class' => $votes > 0 ? 'wfl-vote-positive' : ($votes < 0 ? 'wfl-vote-negative' : ''),
            'up_voted' => $userVote === 'up',
            'down_voted' => $userVote === 'down',
            'comment_text' => $commentsCount === 1 ? $this->t('comment') : $this->t('comments'),
            'upvote_tooltip' => $this->t('upvote'),
            'downvote_tooltip' => $this->t('downvote'),
            'icon_arrow_up' => $this->icon('arrowUp'),
            'icon_arrow_down' => $this->icon('arrowDown'),
            'icon_comment' => $this->icon('comment'),
            'status_html' => $this->renderStatus($feature['status'] ?? 'open'),
        ]);
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

        return $this->renderTemplate('status', [
            'status' => $status,
            'label' => $labels[$status] ?? $status,
        ]);
    }

    /**
     * Render empty state
     */
    public function renderEmpty(): string
    {
        return $this->renderTemplate('empty', [
            'icon_empty' => $this->icon('empty'),
            'empty_title' => $this->t('emptyTitle'),
            'empty_text' => $this->t('emptyText'),
        ]);
    }

    /**
     * Render error state
     */
    public function renderError(): string
    {
        return $this->renderTemplate('error', [
            'icon_error' => $this->icon('error'),
            'error_title' => $this->t('errorTitle'),
            'error_text' => $this->t('errorText'),
            'retry_text' => $this->t('retry'),
        ]);
    }

    /**
     * Render feature creation modal
     */
    public function renderModal(): string
    {
        if (!$this->client->canInteract()) {
            return '';
        }

        return $this->renderTemplate('modal-feature', [
            'suggest_title' => $this->t('suggestTitle'),
            'title_label' => $this->t('titleLabel'),
            'title_placeholder' => $this->t('titlePlaceholder'),
            'description_label' => $this->t('descriptionLabel'),
            'description_placeholder' => $this->t('descriptionPlaceholder'),
            'cancel_text' => $this->t('cancel'),
            'submit_text' => $this->t('submit'),
            'icon_close' => $this->icon('close'),
        ]);
    }

    /**
     * Render comments modal
     */
    public function renderCommentModal(): string
    {
        return $this->renderTemplate('modal-comment', [
            'comments_title' => $this->t('comments'),
            'add_comment_placeholder' => $this->t('addComment'),
            'icon_close' => $this->icon('close'),
            'icon_send' => $this->icon('send'),
        ]);
    }

    /**
     * Render toast notification container
     */
    public function renderToast(): string
    {
        return $this->renderTemplate('toast');
    }

    /**
     * Render JS templates (<template> tags for JavaScript)
     */
    public function renderJsTemplates(): string
    {
        return $this->renderTemplate('js-templates', [
            'icons' => self::ICONS,
            'translations' => $this->translations,
        ]);
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
