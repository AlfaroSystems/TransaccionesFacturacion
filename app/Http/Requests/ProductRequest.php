<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtener las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id_product;

        return [
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId, 'id_product'),
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
                'exists:categories,id_category',
            ],
            'id_sub_category' => [
                'nullable',
                'exists:sub_categories,id_sub_category',
            ],
            'purchase_unit' => [
                'nullable',
                'exists:units,id_unit',
            ],
            'sale_unit' => [
                'nullable',
                'exists:units,id_unit',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'images' => [
                'nullable',
                'array',
            ],
            'images.*' => [
                'nullable',
                'image',
                // Sin SVG: puede contener scripts y se sirve desde el disco público
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
            ],
        ];
    }

    /**
     * Mensajes personalizados de error para la validación.
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'El código SKU ingresado ya está en uso por otro producto.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'images.*.image' => 'Los archivos seleccionados deben ser imágenes válidas.',
            'images.*.mimes' => 'Las imágenes deben estar en formato JPEG, PNG, JPG, GIF o WEBP.',
            'images.*.max' => 'Cada imagen no debe superar los 5MB de tamaño.',
        ];
    }
}