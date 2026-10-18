<?php

namespace App\Http\Requests;

/**
 * Productos de una solicitud de cotización en borrador:
 *
 * - lines[producto-unidad][quantity]: productos de solicitudes de compra que se conservan,
 *   con su cantidad total (los que no vienen se quitan).
 * - products: todos los productos sin solicitud de compra, con su destino.
 */
class UpdatePurchaseQuotationRequest extends StorePurchaseQuotationRequest
{
    public function rules(): array
    {
        return [
            'lines' => ['nullable', 'array'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            ...$this->productRules(),
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'lines.*.quantity.required' => 'Indique la cantidad de cada producto.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
        ];
    }
}
