<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_category_id'   => 'sometimes|exists:product_categories,id',
            'name'                  => 'sometimes|string|max:255',
            'price'                 => 'sometimes|numeric|min:0',
            'stock'                 => 'sometimes|integer|min:0',
        ];
    }
}
