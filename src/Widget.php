<?php

declare(strict_types=1);

namespace WPFeatureLoop;

use WPFeatureLoop\Client;
use WPFeatureLoop\RestApi;

/**
 * Widget Renderer
 *
 * Renders the feature voting widget with the same UI/UX as the JS SDK.
 * Uses template files instead of inline HTML strings.
 *
 * Everything (modals, templates, toast) is rendered inside the container div
 * so the JS can scope all queries to this.container without needing unique IDs.
 */
class Widget
{
    /**
     * Container ID
     */
    private const CONTAINER_ID = 'wpfeatureloop';

    /**
     * Client instance
     */
    private Client $client;

    /**
     * Translations
     */
    private array $translations;

    /**
     * Path to templates directory
     */
    private string $templatesPath;

    /**
     * Whether inline styles have already been outputted (shared across instances)
     */
    private static bool $stylesOutputted = false;

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
            'featurePending' => 'Your suggestion has been submitted and will be reviewed.',
            'commentAdded' => 'Comment added!',
            'voteSaved' => 'Vote saved!',
            'statusInbox' => 'Inbox',
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
            'featurePending' => 'Sua sugestão foi enviada e será analisada.',
            'commentAdded' => 'Comentário adicionado!',
            'voteSaved' => 'Voto salvo!',
            'statusInbox' => 'Recebido',
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
     */
    public function __construct(Client $client)
    {
        $this->client = $client;

        $language = $client->getLanguage();
        $this->translations = self::DEFAULT_TRANSLATIONS[$language] ?? self::DEFAULT_TRANSLATIONS['en'];
        $this->templatesPath = dirname(__DIR__) . '/templates';
    }

    /**
     * Get translation
     */
    private function t(string $key): string
    {
        return $this->translations[$key] ?? $key;
    }

    /**
     * Get inline styles
     *
     * Reads pre-minified CSS file and wraps in <style> tag.
     * Only outputs once even with multiple widget instances.
     */
    private function getInlineStyles(): string
    {
        if (self::$stylesOutputted) {
            return '';
        }

        self::$stylesOutputted = true;

        $cssFile = dirname(__DIR__) . '/assets/css/wpfeatureloop.min.css';

        if (!file_exists($cssFile)) {
            return '';
        }

        return '<style>' . file_get_contents($cssFile) . '</style>';
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
     * Render the complete widget
     *
     * Everything (header, skeleton, modals, templates, toast) is inside
     * a single container div so JS can scope all queries.
     *
     * @return string HTML
     */
    public function render(): string
    {
        // Enqueue JS only when widget is rendered
        $this->client->enqueueAssets();

        $html = '';

        // Inline CSS before container to avoid FOUC
        $html .= $this->getInlineStyles();

        // Open container with config as data attribute (JS reads it for auto-init)
        $html .= sprintf(
            '<div id="%s" class="wfl-container" data-loading="true" data-config="%s">',
            esc_attr(self::CONTAINER_ID),
            esc_attr(wp_json_encode($this->getJsConfig()))
        );

        // Header + skeleton (visible content)
        $html .= $this->renderTemplate('widget-inner', [
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

        // Close container
        $html .= '</div>';

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
            'project_id' => $this->client->getProjectId(),
            'rest_url' => rest_url(RestApi::NAMESPACE),
            'nonce' => wp_create_nonce('wp_rest'),
            'can_interact' => $this->client->canInteract(),
            'i18n' => $this->translations,
        ];
    }
}
