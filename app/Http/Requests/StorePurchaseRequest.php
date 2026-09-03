<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'id_branch' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'id_warehouse' => [
                'required',
                'integer',
                'exists:warehouses,id',
            ],

            'request_date' => [
                'required',
                'date',
            ],

            'required_date' => [
                'required',
                'date',
                'after_or_equal:request_date',
            ],

            'justification' => [
                'required',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'details' => [
                'required',
                'array',
                'min:1',
            ],

            'details.*.id_product' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'details.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'details.*.id_unit' => [
                'required',
                'integer',
                'exists:units,id',
            ],

            'details.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'details.*.notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'id_branch.required' => 'Debe seleccionar una sucursal.',
            'id_branch.exists' => 'La sucursal seleccionada no es válida.',

            'id_warehouse.required' => 'Debe seleccionar una bodega.',
            'id_warehouse.exists' => 'La bodega seleccionada no es válida.',

            'request_date.required' => 'La fecha de solicitud es obligatoria.',
            'request_date.date' => 'La fecha de solicitud no es válida.',

            'required_date.required' => 'La fecha requerida es obligatoria.',
            'required_date.date' => 'La fecha requerida no es válida.',
            'required_date.after_or_equal' =>
                'La fecha requerida no puede ser anterior a la fecha de solicitud.',

            'justification.required' => 'La justificación es obligatoria.',

            'details.required' => 'Debe agregar al menos un producto.',
            'details.array' => 'Los productos de la solicitud no son válidos.',
            'details.min' => 'Debe agregar al menos un producto.',

            'details.*.id_product.required' =>
                'Debe seleccionar un producto en cada fila.',
            'details.*.id_product.exists' =>
                'Uno de los productos seleccionados no existe.',

            'details.*.quantity.required' =>
                'Debe indicar la cantidad de cada producto.',
            'details.*.quantity.numeric' =>
                'La cantidad debe ser un valor numérico.',
            'details.*.quantity.gt' =>
                'La cantidad debe ser mayor que cero.',

            'details.*.id_unit.required' =>
                'Debe seleccionar una unidad de medida.',
            'details.*.id_unit.exists' =>
                'Una de las unidades seleccionadas no existe.',
        ];
    }

    /**
     * Nombres amigables para los atributos.
     */
    public function attributes(): array
    {
        return [
            'id_branch' => 'sucursal',
            'id_warehouse' => 'bodega',
            'request_date' => 'fecha de solicitud',
            'required_date' => 'fecha requerida',
            'justification' => 'justificación',
            'notes' => 'notas',

            'details.*.id_product' => 'producto',
            'details.*.quantity' => 'cantidad',
            'details.*.id_unit' => 'unidad de medida',
            'details.*.description' => 'descripción',
            'details.*.notes' => 'notas del producto',
        ];
    }
}