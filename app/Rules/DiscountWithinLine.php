<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * El descuento de una línea no puede superar su subtotal (cantidad × precio unitario).
 * Se aplica a campos de arreglos como "details.*.discount": la cantidad y el precio
 * se leen de la misma línea.
 */
class DiscountWithinLine implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function __construct(
        private string $quantityField = 'quantity',
        private string $priceField = 'unit_price',
    ) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            return;
        }

        // "details.3.discount" -> "details.3"
        $line = Str::beforeLast($attribute, '.');
        $quantity = Arr::get($this->data, "{$line}.{$this->quantityField}");
        $price = Arr::get($this->data, "{$line}.{$this->priceField}");

        if (! is_numeric($quantity) || ! is_numeric($price)) {
            return;
        }

        $subtotal = (float) $quantity * (float) $price;

        if ((float) $value > round($subtotal, 4)) {
            $fail('El descuento no puede ser mayor que el subtotal de la línea ('.number_format($subtotal, 2).').');
        }
    }
}
