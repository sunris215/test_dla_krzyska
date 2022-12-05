<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

use Statik\Common\Config\ConfigInterface;
use Statik\Common\Settings\GeneratorInterface;

/**
 * Class AbstractField.
 */
abstract class AbstractField implements FieldInterface
{
    protected string $name;

    protected string $namespace;

    protected ConfigInterface $config;

    protected string $label;

    protected string $description;

    protected ?array $properties;

    protected bool $isDefault;

    /** @var mixed */
    protected $value;

    /** @var callable|null */
    protected $conditionsCallback;

    /** @var callable|null */
    protected $conditions;

    /**
     * AbstractField constructor.
     */
    public function __construct(string $name, array $structure, GeneratorInterface $generator)
    {
        $this->name = $name;
        $this->namespace = $generator->getNamespace();
        $this->config = $generator->getConfig();

        $this->value = $structure['value'] ?? null;
        $this->label = $structure['label'];
        $this->description = $structure['description'] ?? '';
        $this->properties = $structure['attrs'] ?? null;
        $this->conditionsCallback = $structure['conditionsCallback'] ?? null;
        $this->conditions = $structure['conditions'] ?? null;
        $this->isDefault = $this->config::isDefaultSettings("{$this->name}.value");

        if ($this->isDefault) {
            $this->properties['disabled'] = 'disabled';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateFieldsetHtml(): string
    {
        if (\is_callable($this->conditionsCallback) && false === ($this->conditionsCallback)($this)) {
            return '';
        }

        if (\is_array($this->conditions)) {
            $conditions = \esc_attr(\json_encode($this->conditions));
        }

        switch (static::class) {
            case Repeater::class:
                $class = 'repeater';
                break;
            case Editor::class:
                $class = 'editor';
                break;
            default:
                $class = 'input';
        }

        \ob_start(); ?>

        <div class="statik-grid-row" <?= $conditions ?? false ? "data-conditions=\"{$conditions}\"" : ''; ?>>
            <div class="statik-grid-col">
                <label for="<?= "{$this->namespace}-{$this->name}"; ?>">
                    <?= $this->label; ?>
                    <?= isset($this->properties['required']) ? ' <sup>*</sup>' : null; ?>
                </label>
                <?php if ($this->description) { ?>
                    <span class="desc"><?= $this->description; ?></span>
                <?php } ?>
            </div>
            <div class="statik-grid-col <?= $class; ?>">
                <div><?= $this->generateFieldHtml(); ?></div>
                <?php if ($this->isDefault) { ?>
                    <i class="dashicons dashicons-admin-network"
                       title="<?= \__('Value is locked by constant in wp-config.php file', 'statik'); ?>"> </i>
                <?php } ?>
            </div>
        </div>

        <?php return \ob_get_clean();
    }

    /**
     * Generate field attributes based on provided data.ą.
     */
    protected function generateFieldAttributes(): string
    {
        $this->properties['id'] = $this->properties['name'] = null;

        $string = '';
        foreach ($this->properties as $key => $property) {
            if (empty($property)) {
                continue;
            }

            if (
                false === \in_array($key, static::NOT_DATA_ATTRS, true)
                && 0 !== \strpos($key, 'data-')
            ) {
                $key = "data-{$key}";
            }

            if (\is_array($property)) {
                $property = \implode(' ', $property);
            }

            $property = \esc_attr($property);

            $string .= "{$key}=\"{$property}\" ";
        }

        return $string;
    }
}
