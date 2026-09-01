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
                'mimes:jpeg,png,jpg,gif,webp,svg',
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
            'images.*.mimes' => 'Las imágenes deben estar en formato JPEG, PNG, JPG, GIF, WEBP o SVG.',
            'images.*.max' => 'Cada imagen no debe superar los 5MB de tamaño.',
        ];
    }
}