<?php

namespace Botble\Marketplace\Forms\Fields;

use Kris\LaravelFormBuilder\Fields\FormField;

class CustomImagesField extends FormField
{

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return 'plugins/marketplace::themes.dashboard.forms.fields.custom-images';
    }
}
