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
     * Render the complete widget (container + modals + templates + scripts)
     *
     * @return string HTML
     */
    public function render(): string
    {
        // Enqueue assets only when widget is rendered
        $this->client->enqueueAssets();

        $html = '';

        // Main container with skeleton
        $html .= $this->renderTemplate('widget', [
            'container_id' => $this->config['container_id'],
            'title' => $this->t('title'),
            'subtitle' => $this->t('subtitle'),
            'can_interact' => $this->client->canInteract(),
            'suggest_feature_text' => $this->t('suggestFeature'),
            'skeleton_html' => $this->renderTemplate('skeleton'),
        ]);

        // Feature modal (only if user can interact)
        if ($this->client->canInteract()) {
            $html .= $this->renderTemplate('modal-feature', [
                'suggest_title' => $this->t('suggestTitle'),
                'title_label' => $this->t('titleLabel'),
                'title_placeholder' => $this->t('titlePlaceholder'),
                'description_label' => $this->t('descriptionLabel'),
                'description_placeholder' => $this->t('descriptionPlaceholder'),
                'cancel_text' => $this->t('cancel'),
                'submit_text' => $this->t('submit'),
            ]);
        }

        // Comment modal
        $html .= $this->renderTemplate('modal-comment', [
            'comments_title' => $this->t('comments'),
            'add_comment_placeholder' => $this->t('addComment'),
        ]);

        // Toast
        $html .= $this->renderTemplate('toast');

        // JS Templates
        $html .= $this->renderTemplate('js-templates', [
            'translations' => $this->translations,
        ]);

        // JS Config
        $config = wp_json_encode($this->getJsConfig());
        $html .= sprintf('<script>window.wpfeatureloop_config = %s;</script>', $config);

        return $html;
    }

    /**
     * Get JS configuration array
     *
     * @return array Configuration for WPFeatureLoopWidget
     */
    private function getJsConfig(): array
    {
        return [
            'container_id' => $this->config['container_id'],
            'rest_url' => rest_url(RestApi::NAMESPACE),
            'nonce' => wp_create_nonce('wp_rest'),
            'can_interact' => $this->client->canInteract(),
            'i18n' => $this->translations,
        ];
    }
}
