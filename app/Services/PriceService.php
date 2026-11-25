<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Precios de venta por producto, con historial. Cambiar el precio no pisa el anterior: cierra el
 * vigente (valid_until = ahora) y abre uno nuevo (valid_from = ahora, valid_until = NULL). Así
 * queda el registro de qué precio tenía el producto y desde/hasta cuándo.
 */
class PriceService
{
    /**
     * Asigna o cambia el precio de venta del producto. La primera vez crea el precio vigente;
     * después cierra el anterior y abre el nuevo. Devuelve el precio vigente resultante.
     */
    public function cambiarPrecio(Product $product, float $precio, ?string $notas = null): ProductPrice
    {
        return DB::transaction(function () use ($product, $precio, $notas) {
            $precio = round($precio, 4);

            if ($precio < 0) {
                throw ValidationException::withMessages(['price' => 'El precio no puede ser negativo.']);
            }

            // Se bloquea el vigente: dos cambios a la vez no deben dejar dos precios vigentes
            $vigente = ProductPrice::query()
                ->where('id_product', $product->id_product)
                ->whereNull('valid_until')
                ->lockForUpdate()
                ->first();

            if ($vigente && abs((float) $vigente->price - $precio) < 0.00001) {
                throw ValidationException::withMessages(['price' => 'El precio es igual al vigente ('.$this->formato($vigente->price).'): no hay nada que cambiar.']);
            }

            $ahora = now();

            if ($vigente) {
                $vigente->update(['valid_until' => $ahora]);
            }

            return ProductPrice::create([
                'id_product' => $product->id_product,
                'price' => $precio,
                'currency' => 'USD',
                'valid_from' => $ahora,
                'valid_until' => null,
                'id_user' => Auth::id(),
                'notes' => $notas,
            ]);
        });
    }

    private function formato(float|string $valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }
}
