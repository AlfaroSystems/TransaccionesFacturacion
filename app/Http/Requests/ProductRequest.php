<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product') ? $this->route('product')->id : null;

        return [
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'original_code' => [
                'nullable',
                'string',
                'max:100',
            ],
            'internal_code' => [
                'nullable',
                'string',
                'max:100',
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
            ],
            'name' => [
                'required',
                'string',
                'max:200',
            ],
            'size' => [
                'nullable',
                'string',
                'max:100',
            ],
            'dimensions' => [
                'nullable',
                'string',
                'max:100',
            ],
            'presentation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'id_category' => [
                'nullable',
                'exists:categories,id',
            ],
            'id_sub_category' => [
                'nullable',
                'exists:sub_categories,id',
            ],
            'purchase_unit' => [
                'nullable',
                'exists:units,id',
            ],
            'sale_unit' => [
                'nullable',
                'exists:units,id',
            ],
            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'sale_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'min_stock' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Mensajes personalizados de error.
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'El código SKU ingresado ya está en uso por otro producto.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'sale_price.required' => 'El precio de venta es obligatorio.',
        ];
    }
}
