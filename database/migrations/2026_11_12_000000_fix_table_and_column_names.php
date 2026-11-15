<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige nombres de tablas y columnas (los datos no cambian):
 *
 * - supliers → suppliers, supliers_contacts → supplier_contacts, id_suplier_contact → id_supplier_contact
 * - companies.addres y branches.addres → address (como suppliers.address)
 * - warehouse_category → warehouse_categories (las tablas van en plural)
 * - products_images → product_images
 * - products.purchase_unit y sale_unit → id_purchase_unit e id_sale_unit (llaves con prefijo id_)
 *
 * Y elimina product_location: no está en el diagrama ni se usa (la existencia por ubicación está
 * en inventory_location_details).
 *
 * Con cada tabla se renombran sus restricciones y su secuencia, para que los nombres del esquema
 * no sigan mostrando el nombre viejo.
 */
return new class extends Migration
{
    private const TABLAS = [
        ['supliers', 'suppliers', 'id_supplier'],
        ['supliers_contacts', 'supplier_contacts', 'id_suplier_contact'],
        ['warehouse_category', 'warehouse_categories', 'id_warehouse_category'],
        ['products_images', 'product_images', 'id_product_image'],
    ];

    public function up(): void
    {
        Schema::dropIfExists('product_location');

        foreach (self::TABLAS as [$antes, $despues, $llave]) {
            $this->renombrarTabla($antes, $despues, $llave);
        }

        Schema::table('supplier_contacts', function (Blueprint $table) {
            $table->renameColumn('id_suplier_contact', 'id_supplier_contact');
        });
        DB::statement('ALTER SEQUENCE IF EXISTS supplier_contacts_id_suplier_contact_seq RENAME TO supplier_contacts_id_supplier_contact_seq');

        foreach (['companies', 'branches'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->renameColumn('addres', 'address'));
        }

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('purchase_unit', 'id_purchase_unit');
            $table->renameColumn('sale_unit', 'id_sale_unit');
        });
        $this->renombrarRestriccion('products', 'products_purchase_unit_foreign', 'products_id_purchase_unit_foreign');
        $this->renombrarRestriccion('products', 'products_sale_unit_foreign', 'products_id_sale_unit_foreign');
    }

    public function down(): void
    {
        $this->renombrarRestriccion('products', 'products_id_purchase_unit_foreign', 'products_purchase_unit_foreign');
        $this->renombrarRestriccion('products', 'products_id_sale_unit_foreign', 'products_sale_unit_foreign');
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('id_purchase_unit', 'purchase_unit');
            $table->renameColumn('id_sale_unit', 'sale_unit');
        });

        foreach (['companies', 'branches'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->renameColumn('address', 'addres'));
        }

        DB::statement('ALTER SEQUENCE IF EXISTS supplier_contacts_id_supplier_contact_seq RENAME TO supplier_contacts_id_suplier_contact_seq');
        Schema::table('supplier_contacts', function (Blueprint $table) {
            $table->renameColumn('id_supplier_contact', 'id_suplier_contact');
        });

        foreach (array_reverse(self::TABLAS) as [$antes, $despues, $llave]) {
            $this->renombrarTabla($despues, $antes, $llave);
        }

        Schema::create('product_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id_product')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations', 'id_location')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Renombra la tabla, sus restricciones ({tabla}_pkey, {tabla}_email_unique, {tabla}_x_foreign...,
     * con el índice de cada una) y su secuencia ({tabla}_{llave}_seq).
     */
    private function renombrarTabla(string $antes, string $despues, string $llave): void
    {
        Schema::rename($antes, $despues);

        $restricciones = DB::table('pg_constraint')
            ->whereRaw('conrelid = ?::regclass', [$despues])
            ->where('conname', 'like', str_replace('_', '\_', $antes).'\_%')
            ->pluck('conname');

        foreach ($restricciones as $nombre) {
            $this->renombrarRestriccion($despues, $nombre, $despues.substr($nombre, strlen($antes)));
        }

        DB::statement("ALTER SEQUENCE IF EXISTS {$antes}_{$llave}_seq RENAME TO {$despues}_{$llave}_seq");
    }

    private function renombrarRestriccion(string $tabla, string $antes, string $despues): void
    {
        $existe = DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = ?::regclass',
            [$antes, $tabla]
        );

        if ($existe) {
            DB::statement("ALTER TABLE {$tabla} RENAME CONSTRAINT {$antes} TO {$despues}");
        }
    }
};
