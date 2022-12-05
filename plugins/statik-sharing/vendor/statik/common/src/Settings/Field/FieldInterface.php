<?php

declare(strict_types=1);

namespace Statik\Common\Settings\Field;

/**
 * Interface FieldInterface.
 */
interface FieldInterface
{
    /**
     * @var array attributes without `data` prefix
     */
    public const NOT_DATA_ATTRS = ['class', 'type', 'required', 'disabled', 'rows', 'min', 'max'];

    /**
     * Generate HTML of single. Render 3 columns for use in the HTML Table element.
     * Method support a lot of properties that could be rendered with the field,
     * eq. classes, data attributes.
     */
    public function generateFieldsetHtml(): string;

    /**
     * Generate HTML only of the single input field.
     */
    public function generateFieldHtml(): string;
}
