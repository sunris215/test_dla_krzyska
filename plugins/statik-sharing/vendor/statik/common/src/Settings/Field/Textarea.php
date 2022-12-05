<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

/**
 * Class Textarea.
 */
class Textarea extends AbstractField
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

        <textarea id="<?= "{$this->namespace}-{$this->name}"; ?>"
                  name="<?= "{$this->namespace}[{$this->name}.value]"; ?>"
                  <?= $this->generateFieldAttributes(); ?>><?= $this->value; ?></textarea>

        <?php return \ob_get_clean();
    }
}
