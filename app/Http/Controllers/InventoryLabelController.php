<?php

namespace App\Http\Controllers;

use App\Models\InventoryLabelPrint;
use App\Models\InventoryLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

/**
 * Etiquetas de los lotes: cada impresión queda registrada (cuántas, cuándo y quién); una
 * reimpresión exige el motivo, para saber por qué hay más etiquetas de un lote.
 */
class InventoryLabelController extends Controller
{
    public function create(InventoryLot $lot)
    {
        Gate::authorize('inventory.etiquetas');

        $lot->load(['product.purchaseUnit', 'labelPrints' => fn ($q) => $q->with('user')->latest('id_inventory_label_print')]);

        return view('inventory.labels', compact('lot'));
    }

    public function store(Request $request, InventoryLot $lot)
    {
        Gate::authorize('inventory.etiquetas');

        $reimpresion = $lot->labelPrints()->exists();

        $validated = $request->validate([
            'quantity_printed' => ['required', 'integer', 'min:1', 'max:500'],
            'reprint_reason' => [$reimpresion ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'reprint_reason.required' => 'Las etiquetas de este lote ya se imprimieron: indique el motivo de la reimpresión.',
        ]);

        $print = $lot->labelPrints()->create([
            'quantity_printed' => $validated['quantity_printed'],
            'print_date' => now(),
            'id_user' => Auth::id(),
            'reprint_reason' => $reimpresion ? $validated['reprint_reason'] : null,
        ]);

        return redirect()->route('inventory.labels.print', $print);
    }

    /**
     * Hoja de etiquetas de una impresión registrada, lista para imprimir. El código de barras
     * (Code 128) lleva el SKU del producto, su código interno: el lote y la fecha van en texto.
     */
    public function show(InventoryLabelPrint $print)
    {
        Gate::authorize('inventory.etiquetas');

        // El lote debe ser visible para el usuario (filtro por sucursal)
        $lot = InventoryLot::with('product.purchaseUnit')->findOrFail($print->id_inventory_lot);

        $barcode = (new TypeCode128)->getBarcode((string) $lot->product?->sku);
        $barcodeSvg = (new SvgRenderer)
            ->setSvgType(SvgRenderer::TYPE_SVG_INLINE)
            ->render($barcode, $barcode->getWidth() * 2, 60);

        return view('inventory.labels_print', compact('print', 'lot', 'barcodeSvg'));
    }
}
