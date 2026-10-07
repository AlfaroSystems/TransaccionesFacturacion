<?php

namespace App\Http\Requests;
use App\Models\Company;
use App\Rules\Accessible;
use App\Support\BranchAccess;
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
            'id_company' => ['required', new Accessible(Company::class)],
            'name' => 'required|string|max:255',
            'addres' => 'required|string|max:255',
            'id_department' => 'nullable|exists:departments,id_department',
            'id_municipality' => 'nullable|exists:municipalities,id_municipality',
            'id_district' => 'nullable|exists:districts,id_district',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'is_active' => 'boolean',
            // Solo el administrador designa la sucursal del departamento de compras: da acceso
            // a las solicitudes de compra de todas las sucursales de la empresa
            'is_purchasing_department' => BranchAccess::isUnrestricted() ? ['boolean'] : ['prohibited'],
        ];
    }
}