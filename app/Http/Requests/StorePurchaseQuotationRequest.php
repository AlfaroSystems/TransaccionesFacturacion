<?php

namespace App\Http\Requests;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;

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
     * Reglas de validación para crear solicitudes de cotización.
     */
    public function rules(): array
    {
        return [
            'id_purchase_request' => [
                'required',
                'exists:purchase_requests,id_purchase_request',
                function ($attribute, $value, $fail) {
                    $pr = PurchaseRequest::find($value);
                    if (!$pr || $pr->status !== 'approved') {
                        $fail('La solicitud de compra seleccionada debe estar en estado aprobada (approved).');
                    }
                },
            ],
            'supplier_ids' => ['required', 'array', 'min:1'],
            'supplier_ids.*' => ['required', 'integer', 'exists:suppliers,id_supplier'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_purchase_request_detail' => [
                'required',
                'integer',
                'exists:purchase_request_details,id_purchase_request_detail',
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes de validación personalizados en español.
     */
    public function messages(): array
    {
        return [
            'id_purchase_request.required' => 'Debe seleccionar una solicitud de compra aprobada.',
            'id_purchase_request.exists' => 'La solicitud de compra seleccionada no existe.',
            'supplier_ids.required' => 'Debe seleccionar al menos un proveedor para convocar.',
            'supplier_ids.min' => 'Debe seleccionar al menos un proveedor para convocar.',
            'supplier_ids.*.exists' => 'Uno de los proveedores seleccionados no es válido.',
            'items.required' => 'La solicitud de compra debe contener al menos un producto a cotizar.',
            'items.min' => 'Debe cotizar al menos un producto.',
            'items.*.quantity.required' => 'La cantidad a cotizar es obligatoria para cada ítem.',
            'items.*.quantity.min' => 'La cantidad a cotizar debe ser mayor a 0.',
        ];
    }
}
