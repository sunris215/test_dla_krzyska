<?php

declare(strict_types=1);

namespace Statik\Common\Settings;

use const Statik\Common\COMMON_DIR;
use const Statik\Common\COMMON_URL;
use Statik\Common\Config\ConfigInterface;
use const Statik\Common\DEVELOPMENT;
use Statik\Common\Helper\Callback;
use Statik\Common\Settings\Field\Editor;
use Statik\Common\Settings\Field\Input;
use Statik\Common\Settings\Field\InputCheckbox;
use Statik\Common\Settings\Field\Repeater;
use Statik\Common\Settings\Field\Select;
use Statik\Common\Settings\Field\SelectMultiple;
use Statik\Common\Settings\Field\Textarea;
use const Statik\Common\VERSION;

/**
 * Class Generator.
 */
class Generator implements GeneratorInterface
{
    private array $registeredFields = [];

    private string $namespace;

    private ConfigInterface $config;

    private string $assetsDir;

    private string $assetsUrl;

    /**
     * Generator constructor.
     */
    public function __construct(ConfigInterface $config, string $namespace)
    {
        $this->config = $config;
        $this->namespace = $namespace;

        $this->assetsDir = COMMON_DIR . 'assets';
        $this->assetsUrl = COMMON_URL . 'assets';

        \add_action('admin_print_styles', [$this, 'enqueueDashboardStyles']);
        \add_action('admin_print_scripts', [$this, 'enqueueDashboardScripts']);
    }

    /**
     * Enqueue required Styles for dashboard.
     */
    public function enqueueDashboardStyles(): void
    {
        $useDevelopFile = \file_exists("{$this->assetsDir}/stylesheets/settings.css");

        \wp_enqueue_style(
            'statik_common_settings',
            "{$this->assetsUrl}/stylesheets/settings" . ($useDevelopFile ? '.css' : '.min.css'),
            [],
            DEVELOPMENT ? \mt_rand() : VERSION
        );
    }

    /**
     * Enqueue required JavaScript scripts for dashboard.
     */
    public function enqueueDashboardScripts(): void
    {
        $useDevelopFile = \file_exists("{$this->assetsDir}/javascripts/settings.js");

        \wp_enqueue_script(
            'statik_common_settings',
            "{$this->assetsUrl}/javascripts/settings" . ($useDevelopFile ? '.js' : '.min.js'),
            ['jquery', 'wp-polyfill'],
            DEVELOPMENT ? \mt_rand() : VERSION,
            true
        );

        $restUrl = \rest_url('/statik/v1');

        \wp_localize_script(
            'statik_common_settings',
            'statik_common_config',
            [
                'api'       => ['base' => $restUrl . '/'],
                'nonce'     => \wp_create_nonce('wp_rest'),
                'debug'     => DEVELOPMENT ? 1 : 0,
                'assetsUrl' => $this->assetsUrl,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function registerFields(string $key, array $fields): ?array
    {
        $this->registeredFields[$key] = $fields;

        return $this->registeredFields;
    }

    /**
     * Initialize fields values.
     */
    public function initializeFields(bool $saveDefault = true): void
    {
        foreach ($this->registeredFields as $key => $fields) {
            \array_walk($fields, [$this, 'initField'], $saveDefault);
            $this->registeredFields[$key] = $fields;
        }

        $this->config->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function generateStructure(string $group): string
    {
        $structureHtml = '';

        foreach ($this->registeredFields[$group] ?? [] as $fieldName => $fieldStructure) {
            if (false === isset($fieldStructure['type'], $fieldStructure['label'])) {
                continue;
            }

            switch ($fieldStructure['type']) {
                case 'input':
                    $field = new Input($fieldName, $fieldStructure, $this);
                    break;
                case 'input:checkbox':
                    $field = new InputCheckbox($fieldName, $fieldStructure, $this);
                    break;
                case 'editor':
                    $field = new Editor($fieldName, $fieldStructure, $this);
                    break;
                case 'textarea':
                    $field = new Textarea($fieldName, $fieldStructure, $this);
                    break;
                case 'select':
                    $field = new Select($fieldName, $fieldStructure, $this);
                    break;
                case 'select:multiple':
                    $field = new SelectMultiple($fieldName, $fieldStructure, $this);
                    break;
                case 'repeater':
                    $field = new Repeater($fieldName, $fieldStructure, $this);
                    break;
                default:
                    continue 2;
            }

            $structureHtml .= $field->generateFieldsetHtml();
        }

        return $this->minifyHtml(
            "<div class=\"statik-settings-grid statik-generator\" data-namespace=\"{$this->namespace}\">{$structureHtml}</div>"
        );
    }

    /**
     * Initialize single field.
     */
    private function initField(array &$field, string $key, bool $saveDefault): void
    {
        if (isset($field['value'])) {
            $field['value'] = Callback::getResults($field['value']);
        } else {
            $field['value'] = $this->config->get("{$key}.value", null);
        }

        if (null === $field['value'] && ($field['default'] ?? null)) {
            $field['value'] = Callback::getResults($field['default']);
        }

        if ($saveDefault && $this->config->get("{$key}.value") !== $field['value']) {
            $this->config->set("{$key}.value", $field['value']);
        }
    }

    /**
     * Minify HTML.
     */
    private function minifyHtml(string $html): string
    {
        return \preg_replace(
            ['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/'],
            ['>', '<', '\\1', ''],
            $html
        );
    }
}
