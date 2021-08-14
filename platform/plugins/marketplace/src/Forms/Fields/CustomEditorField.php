<?php

namespace Botble\Marketplace\Forms\Fields;

use Kris\LaravelFormBuilder\Fields\FormField;

class CustomEditorField extends FormField
{

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return 'plugins/marketplace::themes.dashboard.forms.fields.custom-editor';
    }

    /**
     * @param array $options
     * @param bool $showLabel
     * @param bool $showField
     * @param bool $showError
     * @return string
     */
    public function render(array $options = [], $showLabel = true, $showField = true, $showError = true)
    {
        return parent::render($options, $showLabel, $showField, $showError);
    }
}
