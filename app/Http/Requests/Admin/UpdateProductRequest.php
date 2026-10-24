<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'sku' => [
                'required',
                'string',
                'max:40',
                Rule::unique('products', 'sku')->ignore($this->route('product')),
            ],
        ];
    }
}
