<?php

namespace App\Models\Concerns;

/**
 * Documento cuyo estado se vuelve a leer, bloqueado, antes de cambiarlo.
 *
 * El modelo se cargó al empezar la petición; mientras tanto otro usuario pudo confirmarlo,
 * anularlo o editarlo. Las reglas de estado se revisan después de refreshWithLock(), dentro
 * de la misma transacción, para que dos cambios a la vez no partan del mismo estado viejo.
 */
trait RefreshesWithLock
{
    /**
     * Bloquea la fila hasta el fin de la transacción y recarga sus atributos. Sin scopes: el
     * acceso al registro ya se validó al cargarlo.
     */
    public function refreshWithLock(): static
    {
        $fila = $this->newQueryWithoutScopes()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        $this->setRawAttributes($fila->getAttributes(), true);

        return $this;
    }
}
