<?php

namespace App\Http\Requests;

use App\Models\PurchaseRequest;
use App\Rules\Accessible;
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
     * Reglas de validación para crear solicitudes de cotización.
     */
    public function rules(): array
    {
        return [
            'id_purchase_request' => [
                'required',
                new Accessible(PurchaseRequest::class),
                function ($attribute, $value, $fail) {
                    $pr = PurchaseRequest::find($value);
                    if (!$pr || $pr->status !== PurchaseRequest::STATUS_APPROVED) {
                        $fail('La solicitud de compra seleccionada debe estar aprobada por el departamento de compras y sin cotizar.');
                    }
                },
            ],
            'items' => ['required', 'array', 'min:1'],
            // Cada ítem debe pertenecer a la solicitud de compra seleccionada
            'items.*.id_purchase_request_detail' => [
                'required',
                'integer',
                Rule::exists('purchase_request_details', 'id_purchase_request_detail')
                    ->where('id_purchase_request', $this->input('id_purchase_request')),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
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
            'items.required' => 'La solicitud de compra debe contener al menos un producto a cotizar.',
            'items.min' => 'Debe cotizar al menos un producto.',
            'items.*.quantity.required' => 'La cantidad a cotizar es obligatoria para cada ítem.',
            'items.*.quantity.min' => 'La cantidad a cotizar debe ser mayor a 0.',
        ];
    }
}
