<?php

namespace App\Http\Requests;
use App\Models\Company;
use App\Rules\Accessible;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Solo empresas visibles para el usuario (la suya, si no es administrador)
            'company_id' => ['required', new Accessible(Company::class)],
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'district_id' => 'nullable|exists:districts,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'is_active' => 'boolean'
        ];
    }
}