<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'    => 'nullable|string',
            'limit'     => 'nullable|integer|min:1|max:100'
        ];
    }
}
