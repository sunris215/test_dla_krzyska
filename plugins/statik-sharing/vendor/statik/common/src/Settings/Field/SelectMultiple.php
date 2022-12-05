<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

use Statik\Common\Settings\GeneratorInterface;

/**
 * Class SelectMultiple.
 */
class SelectMultiple extends AbstractField
{
    /** @var array|callable */
    private $availableValues;

    /**
     * Select constructor.
     */
    public function __construct(string $name, array $structure, GeneratorInterface $generator)
    {
        parent::__construct($name, $structure, $generator);

        foreach ((array) $generator->getConfig()->get("{$name}.value", []) as $value) {
            $this->availableValues[$value] = $value;
        }

        $this->value = (array) $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function generateFieldHtml(): string
    {
        $options = '';

        $before = "<input type=\"hidden\" name=\"{$this->namespace}[{$this->name}.value]\">";

        foreach ((array) ($this->availableValues ?? []) as $key => $option) {
            $selected = \selected(\in_array($key, $this->value, true), true, false);
            $options .= "<option value=\"{$key}\" {$selected}>{$option}</option>";
        }

        return $before . $this->getFieldHtml($options);
    }

    /**
     * Generate field HTML.
     */
    private function getFieldHtml(string $options): string
    {
        \ob_start(); ?>

        <select id="<?= "{$this->namespace}-{$this->name}"; ?>"
                name="<?= "{$this->namespace}[{$this->name}.value]"; ?>"
                data-multiple="true"
                multiple="multiple"
            <?= $this->generateFieldAttributes(); ?>>
            <?= $options; ?>
        </select>

        <?php return \ob_get_clean();
    }
}
