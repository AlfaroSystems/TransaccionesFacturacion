<?php

use App\Models\Branch;
use App\Models\CashShift;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

// =============================================================================
// Caja: abrir con monto, registrar egresos, cerrar con arqueo (diferencia contra
// el efectivo esperado = apertura + ventas en efectivo - egresos).
// =============================================================================

function cajaEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $company = Company::create(['name' => "Empresa {$tag}"]);
    $sucursal = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);

    $role = Role::create(['name' => 'rol-'.Str::lower(Str::random(8))]);
    foreach (['cash.ver', 'cash.abrir', 'cash.cerrar', 'cash.egreso'] as $permiso) {
        Permission::firstOrCreate(['id_permission' => $permiso], ['name' => $permiso]);
    }
    $role->permissions()->sync(['cash.ver', 'cash.abrir', 'cash.cerrar', 'cash.egreso']);
    $user = User::factory()->create(['id_branch' => $sucursal->id_branch]);
    $user->roles()->attach($role->id_role, ['assigned_at' => now()]);

    return (object) ['sucursal' => $sucursal, 'user' => $user];
}

it('abre una caja y bloquea una segunda en la misma sucursal', function () {
    $e = cajaEscenario();

    $this->actingAs($e->user)->post(route('cash.open'), ['opening_amount' => 50])->assertSessionHasNoErrors();
    expect(CashShift::openForBranch($e->sucursal->id_branch))->not->toBeNull();

    // No se puede abrir una segunda
    $this->post(route('cash.open'), ['opening_amount' => 10])->assertSessionHasErrors();
    expect(CashShift::queryAllBranches()->where('id_branch', $e->sucursal->id_branch)->count())->toBe(1);
});

it('registra egresos que restan del efectivo esperado', function () {
    $e = cajaEscenario();
    $this->actingAs($e->user)->post(route('cash.open'), ['opening_amount' => 100]);
    $shift = CashShift::openForBranch($e->sucursal->id_branch);

    $this->post(route('cash.outflow', $shift), ['amount' => 30, 'concept' => 'Pago mensajería'])->assertSessionHasNoErrors();

    $shift->refresh();
    expect((float) $shift->total_outflows)->toBe(30.0);
    expect($shift->expectedCash())->toBe(70.0); // 100 apertura - 30 egresos (sin ventas)
});

it('cierra la caja calculando la diferencia del arqueo', function () {
    $e = cajaEscenario();
    $this->actingAs($e->user)->post(route('cash.open'), ['opening_amount' => 100]);
    $shift = CashShift::openForBranch($e->sucursal->id_branch);
    $shift->update(['total_cash' => 200, 'total_sales' => 200]); // simula ventas en efectivo

    // Esperado = 100 + 200 = 300; si cuentan 295 hay faltante de 5
    $this->post(route('cash.close', $shift), ['counted_amount' => 295])->assertSessionHasNoErrors();

    $shift->refresh();
    expect($shift->status)->toBe(CashShift::STATUS_CLOSED);
    expect((float) $shift->difference)->toBe(-5.0);
    expect(CashShift::openForBranch($e->sucursal->id_branch))->toBeNull();
});
