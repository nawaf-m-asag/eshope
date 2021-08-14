<?php

namespace Botble\Marketplace\Http\Requests;

use Botble\Support\Http\Requests\Request;

class SettingRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'  => 'required|max:120|min:2',
            'email' => 'required|max:60|min:6|email|unique:ec_customers,email,' . $this->user('customer')->id,
            'phone' => 'required',
            'slug'  => 'max:255',
        ];
    }
}
