<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

use Statik\Common\Helper\Callback;
use Statik\Common\Settings\GeneratorInterface;

/**
 * Class InputCheckbox.
 */
class InputCheckbox extends AbstractField
{
    /** @var array|callable */
    private $availableValues;

    /**
     * InputCheckbox constructor.
     */
    public function __construct(string $name, array $structure, GeneratorInterface $generator)
    {
        parent::__construct($name, $structure, $generator);

        $this->availableValues = $structure['values'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function generateFieldHtml(): string
    {
        $field = "<input type=\"hidden\" name=\"{$this->namespace}[{$this->name}.value]\">";

        foreach (Callback::getResults($this->availableValues) as $key => $option) {
            $field .= $this->getFieldHtml($key, $option);
        }

        return $field;
    }

    /**
     * Generate field HTML.
     *
     * @param mixed $key
     * @param mixed $option
     */
    private function getFieldHtml($key, $option): string
    {
        \ob_start(); ?>

        <label for="<?= "{$this->namespace}-{$this->name}-{$key}"; ?>">
            <input type="checkbox"
                   name="<?= "{$this->namespace}[{$this->name}.value][]"; ?>"
                   id="<?= "{$this->namespace}-{$this->name}-{$key}"; ?>"
                   value="<?= $key; ?>"
                <?= \in_array($key, (array) $this->value ?? [], true) ? 'checked="checked"' : ''; ?>
                <?= $this->generateFieldAttributes(); ?>>
            <?= $option; ?>
        </label>

        <?php return \ob_get_clean();
    }
}
