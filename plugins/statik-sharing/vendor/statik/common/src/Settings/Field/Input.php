<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

/**
 * Class Input.
 */
class Input extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    public function generateFieldHtml(): string
    {
        if (false === isset($this->properties['type'])) {
            $this->properties['type'] = 'text';
        }

        return $this->getFieldHtml();
    }

    /**
     * Generate field HTML.
     */
    private function getFieldHtml(): string
    {
        \ob_start(); ?>

        <input id="<?= "{$this->namespace}-{$this->name}"; ?>"
               name="<?= "{$this->namespace}[{$this->name}.value]"; ?>"
               value="<?= \filter_var($this->value, \FILTER_SANITIZE_STRING); ?>"
            <?= $this->generateFieldAttributes(); ?>>

        <?php return \ob_get_clean();
    }
}
