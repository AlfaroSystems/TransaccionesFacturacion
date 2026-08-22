<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Category;
use App\Models\AuditLog;

it('logs creation, update, and deletion of different models', function () {
    // 1. Crear un usuario y verificar que se guarda en la bitácora
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'id_record' => $user->id,
        'controller' => 'Console/System',
        'action' => 'Console/System',
    ]);

    // Obtener la entrada del log de creación del usuario
    $userCreateLog = AuditLog::where('id_record', $user->id)
        ->where('original_data', null)
        ->first();

    expect($userCreateLog)->not->toBeNull();
    expect($userCreateLog->modified_data['name'])->toBe('John Doe');

    // 2. Crear una Empresa y verificar que se guarda en la bitácora
    $company = Company::create([
        'name' => 'Empresa de Prueba S.A. de C.V.',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'id_record' => $company->id,
        'controller' => 'Console/System',
        'action' => 'Console/System',
    ]);

    // 3. Crear una Categoría, actualizarla y verificar que se guarda la actualización
    $category = Category::create([
        'name' => 'Electrónica',
    ]);

    $category->update([
        'name' => 'Electrodomésticos',
    ]);

    // Verificar que existe el log de actualización
    $updateLog = AuditLog::where('id_record', $category->id)
        ->whereNotNull('original_data')
        ->first();

    expect($updateLog)->not->toBeNull();
    expect($updateLog->original_data['name'])->toBe('Electrónica');
    expect($updateLog->modified_data['name'])->toBe('Electrodomésticos');

    // 4. Eliminar la Categoría y verificar el log de eliminación
    $category->delete();

    $deleteLog = AuditLog::where('id_record', $category->id)
        ->whereNull('modified_data')
        ->first();

    expect($deleteLog)->not->toBeNull();
    expect($deleteLog->original_data['name'])->toBe('Electrodomésticos');
});
