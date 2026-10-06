<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Antes de que RefreshDatabase migre (y vacíe) la base, verifica que sea la de
     * tests. Evita borrar la base de desarrollo si la configuración quedó cacheada
     * o phpunit.xml apunta a otra base.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "Los tests solo pueden correr sobre una base terminada en _testing (base actual: {$database}). ".
                'Ejecuta "php artisan config:clear" y revisa DB_DATABASE en phpunit.xml.'
            );
        }

        return parent::setUpTraits();
    }
}
