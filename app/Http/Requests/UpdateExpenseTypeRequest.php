<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $expenseType = $this->route('expense_type');

        $id = is_object($expenseType)
            ? $expenseType->id_expense_type
            : $expenseType;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('expense_types', 'name')
                    ->ignore($id, 'id_expense_type'),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede tener más de 150 caracteres.',
            'name.unique' => 'Ya existe un tipo de gasto con este nombre.',

            'description.string' => 'La descripción debe ser texto.',

            'is_active.boolean' => 'El estado seleccionado no es válido.',
        ];
    }
}