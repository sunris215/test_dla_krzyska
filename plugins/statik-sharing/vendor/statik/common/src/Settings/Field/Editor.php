<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

use Statik\Common\Settings\GeneratorInterface;

/**
 * Class Editor.
 */
class Editor extends AbstractField
{
    private string $editorId;

    /**
     * Editor constructor.
     */
    public function __construct(string $name, array $structure, GeneratorInterface $generator)
    {
        parent::__construct($name, $structure, $generator);

        $this->editorId = \str_replace('.', '-', "{$this->namespace}-{$this->name}");
    }

    /**
     * {@inheritdoc}
     */
    public function generateFieldHtml(): string
    {
        $before = $after = '';

        if ($this->properties['disabled'] ?? null) {
            $before .= '<div class="disabled-wrapper">';
            $after .= '</div>';
        }

        \add_filter('the_editor', [$this, 'editorFilter']);

        \ob_start();
        \wp_editor(\htmlspecialchars_decode($this->value ?? ''), $this->editorId, $this->properties);

        if (\wp_doing_ajax()) {
            \_WP_Editors::editor_js();
        }

        $content = \ob_get_clean();

        \remove_filter('the_editor', [$this, 'editorFilter']);

        return "{$before}{$content}{$after}";
    }

    /**
     * Replace editor name.
     */
    public function editorFilter(string $html): string
    {
        return \str_replace(
            "name=\"{$this->editorId}",
            "name=\"{$this->namespace}[{$this->name}.value]\"",
            $html
        );
    }
}
