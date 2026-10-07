<?php

namespace App\Http\Requests;

use App\Models\PurchaseRequest;
use App\Rules\Accessible;
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
     * Reglas de validación para crear solicitudes de cotización: una o varias solicitudes
     * de compra aprobadas, que se cotizan completas.
     */
    public function rules(): array
    {
        return [
            'purchase_requests' => ['required', 'array', 'min:1'],
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
        ];
    }

    /**
     * Mensajes de validación personalizados en español.
     */
    public function messages(): array
    {
        return [
            'purchase_requests.required' => 'Debe seleccionar al menos una solicitud de compra aprobada.',
            'purchase_requests.min' => 'Debe seleccionar al menos una solicitud de compra aprobada.',
            'purchase_requests.*.distinct' => 'Una solicitud de compra está seleccionada más de una vez.',
        ];
    }
}
