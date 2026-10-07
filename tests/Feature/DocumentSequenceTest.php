<?php

use App\Support\DocumentSequence;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('sequence_test_docs', function (Blueprint $table) {
        $table->id();
        $table->string('code', 50)->unique();
    });
});

function siguienteCodigo(string $prefix = 'DOC-2026-'): string
{
    $code = DocumentSequence::next($prefix, 'sequence_test_docs', 'code');
    DB::table('sequence_test_docs')->insert(['code' => $code]);

    return $code;
}

test('los códigos son consecutivos y cada prefijo lleva su propia cuenta', function () {
    expect(siguienteCodigo())->toBe('DOC-2026-0001')
        ->and(siguienteCodigo())->toBe('DOC-2026-0002')
        ->and(siguienteCodigo('DOC-2027-'))->toBe('DOC-2027-0001')
        ->and(siguienteCodigo())->toBe('DOC-2026-0003');
});

test('el contador arranca desde el código más alto que ya existe', function () {
    DB::table('sequence_test_docs')->insert([
        ['code' => 'DOC-2026-0007'],
        ['code' => 'DOC-2026-0041'],
        ['code' => 'DOC-2026-0012'],
        ['code' => 'DOC-2026-BORRADOR'],
        ['code' => 'OTRO-2026-0099'],
    ]);

    expect(siguienteCodigo())->toBe('DOC-2026-0042')
        ->and(siguienteCodigo())->toBe('DOC-2026-0043');
});

test('no reutiliza el número de un documento eliminado', function () {
    siguienteCodigo();
    $ultimo = siguienteCodigo();
    DB::table('sequence_test_docs')->where('code', $ultimo)->delete();

    expect(siguienteCodigo())->toBe('DOC-2026-0003');
});

test('si la transacción se revierte, el número se vuelve a usar', function () {
    siguienteCodigo();

    try {
        DB::transaction(function () {
            siguienteCodigo();
            throw new RuntimeException('falla al guardar el documento');
        });
    } catch (RuntimeException) {
    }

    expect(siguienteCodigo())->toBe('DOC-2026-0002');
});

test('pasa de 9999 sin repetir números', function () {
    DB::table('sequence_test_docs')->insert(['code' => 'DOC-2026-9999']);

    expect(siguienteCodigo())->toBe('DOC-2026-10000')
        ->and(siguienteCodigo())->toBe('DOC-2026-10001');
});
