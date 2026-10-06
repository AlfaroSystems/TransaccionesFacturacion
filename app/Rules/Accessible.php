<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Como "exists", pero consulta mediante Eloquent para que se apliquen los scopes
 * globales: el registro debe existir y ser visible para el usuario actual (por
 * ejemplo, pertenecer a su sucursal).
 */
class Accessible implements ValidationRule
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  (Closure(Builder): void)|null  $constraint  condición adicional opcional
     */
    public function __construct(
        private string $model,
        private ?string $column = null,
        private ?Closure $constraint = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value)) {
            $fail('validation.exists')->translate();

            return;
        }

        $query = $this->model::query()
            ->where($this->column ?? (new $this->model)->getKeyName(), $value);

        if ($this->constraint) {
            ($this->constraint)($query);
        }

        if (! $query->exists()) {
            $fail('validation.exists')->translate();
        }
    }
}
