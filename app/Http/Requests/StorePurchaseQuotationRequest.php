<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\PurchaseRequest;
use App\Models\Warehouse;
use App\Rules\Accessible;
use App\Support\BranchAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear solicitudes de cotización: solicitudes de compra
     * aprobadas (se cotizan completas) y/o productos sin solicitud de compra.
     */
    public function rules(): array
    {
        return [
            'purchase_requests' => ['nullable', 'array', 'required_without:products'],
            'purchase_requests.*' => [
                'required',
                'integer',
                'distinct',
                new Accessible(PurchaseRequest::class),
                function ($attribute, $value, $fail) {
                    $pr = PurchaseRequest::find($value);
                    if ($pr && $pr->status !== PurchaseRequest::STATUS_APPROVED) {
                        $fail("La solicitud {$pr->purchase_request_code} debe estar aprobada por el departamento de compras y sin cotizar.");
                    }
                },
            ],
            ...$this->productRules(),
        ];
    }

    /**
     * Productos sin solicitud de compra y su destino (sucursal y bodega que los recibe).
     * El departamento de compras puede elegir cualquier sucursal de su empresa; los demás,
     * las que pueden ver.
     */
    protected function productRules(): array
    {
        $anyCompanyBranch = BranchAccess::isPurchasingDepartment();

        return [
            'products' => ['nullable', 'array'],
            'products.*.id_product' => ['required', 'integer', 'distinct', 'exists:products,id_product'],
            'products.*.id_unit' => ['required', 'integer', 'exists:units,id_unit'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],

            'id_branch' => [
                'nullable',
                'required_with:products',
                'integer',
                $anyCompanyBranch
                    ? Rule::exists('branches', 'id_branch')->where('id_company', BranchAccess::companyId())
                    : new Accessible(Branch::class),
            ],
            'id_warehouse' => [
                'nullable',
                'required_with:products',
                'integer',
                $anyCompanyBranch
                    ? Rule::exists('warehouses', 'id_warehouse')->where('id_branch', $this->input('id_branch'))
                    : new Accessible(Warehouse::class, constraint: fn ($q) => $q->where('id_branch', $this->input('id_branch'))),
            ],
        ];
    }

    /**
     * Mensajes de validación personalizados en español.
     */
    public function messages(): array
    {
        return [
            'purchase_requests.required_without' => 'Seleccione al menos una solicitud de compra aprobada o agregue productos.',
            'purchase_requests.*.distinct' => 'Una solicitud de compra está seleccionada más de una vez.',

            'products.*.id_product.required' => 'Seleccione el producto de cada fila.',
            'products.*.id_product.distinct' => 'Un producto aparece en más de una fila; indique la cantidad total en una sola.',
            'products.*.id_unit.required' => 'Seleccione la unidad de cada producto.',
            'products.*.quantity.required' => 'Indique la cantidad de cada producto.',
            'products.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',

            'id_branch.required_with' => 'Seleccione la sucursal que recibe los productos sin solicitud de compra.',
            'id_branch.exists' => 'La sucursal seleccionada no es válida.',
            'id_warehouse.required_with' => 'Seleccione la bodega que recibe los productos sin solicitud de compra.',
            'id_warehouse.exists' => 'La bodega seleccionada no es válida.',
        ];
    }

    /**
     * Nombres amigables para los atributos.
     */
    public function attributes(): array
    {
        return [
            'products.*.id_product' => 'producto',
            'products.*.id_unit' => 'unidad',
            'products.*.quantity' => 'cantidad',
            'id_branch' => 'sucursal de destino',
            'id_warehouse' => 'bodega de destino',
        ];
    }
}
