<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

use Statik\Common\Helper\Callback;
use Statik\Common\Settings\GeneratorInterface;

/**
 * Class Select.
 */
class Select extends AbstractField
{
    /** @var array|callable */
    private $availableValues;

    private bool $isAsync;

    /**
     * Select constructor.
     */
    public function __construct(string $name, array $structure, GeneratorInterface $generator)
    {
        parent::__construct($name, $structure, $generator);

        $this->availableValues = $structure['values'] ?? [];
        $this->isAsync = isset($this->properties['async']) && true === $this->properties['async'];
    }

    /**
     * {@inheritdoc}
     */
    public function generateFieldHtml(): string
    {
        $options = $before = $after = '';

        if ($this->isAsync) {
            $this->properties['async'] = "{$this->name}.asyncField";
            $this->properties['current-value'] = $this->value;
            $this->properties['data-disabled'] = 'disabled';

            $before .= '<div class="loader-wrapper">';
            $after .= '</div>';

            $this->config->set("{$this->name}.asyncField", $this->availableValues);
            $this->config->save();
        }

        if (isset($this->availableValues) && [] !== $this->availableValues) {
            if (null === $this->value || null === ($this->properties['required'] ?? null)) {
                $options .= '<option value selected>' . \__('Select...', 'statik') . '</option>';
            }

            $values = $this->isAsync ? [] : Callback::getResults($this->availableValues);
        } else {
            $label = \__('No values available', 'statik');
            $options .= "<option value selected hidden>{$label}</option>";
            $this->properties['disabled'] = 'disabled';
        }

        foreach ($values ?? [] as $key => $option) {
            $selected = \selected($this->value, $key, false);
            $options .= "<option value=\"{$key}\" {$selected}>{$option}</option>";
        }

        return $before . $this->getFieldHtml($options) . $after;
    }

    /**
     * Generate field HTML.
     */
    private function getFieldHtml(string $options): string
    {
        \ob_start(); ?>

        <select id="<?= "{$this->namespace}-{$this->name}"; ?>"
                name="<?= "{$this->namespace}[{$this->name}.value]"; ?>"
            <?= $this->generateFieldAttributes(); ?>>
            <?= $options; ?>
        </select>

        <?php return \ob_get_clean();
    }
}
