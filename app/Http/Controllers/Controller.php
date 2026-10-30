<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Fecha (AAAA-MM-DD) de un filtro de listado, o null si no viene o no es válida: un valor
     * escrito a mano en la URL no debe llegar a la consulta, donde PostgreSQL lo rechazaría
     * con un error.
     */
    protected function fechaDelFiltro(Request $request, string $campo): ?string
    {
        $valor = $request->input($campo);

        if (! is_string($valor)) {
            return null;
        }

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return $fecha && $fecha->format('Y-m-d') === $valor ? $valor : null;
    }
}
