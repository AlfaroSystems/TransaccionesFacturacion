<?php

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

function usuarioConPermisosDeSubcategorias(array $permisos): User
{
    $role = Role::create(['name' => 'subcategorias-'.Str::lower(Str::random(8))]);

    foreach ($permisos as $permiso) {
        Permission::firstOrCreate(['id_permission' => $permiso], ['name' => $permiso]);

        if (! Gate::has($permiso)) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }

    $role->permissions()->sync($permisos);

    $user = User::factory()->create();
    $user->roles()->attach($role->id_role, ['assigned_at' => now()]);

    return $user;
}

test('crear, ver y editar subcategorías piden su permiso', function () {
    $category = Category::create(['name' => 'Categoría']);
    $sub = SubCategory::create(['id_category' => $category->id_category, 'name' => 'Subcategoría']);

    $this->actingAs(usuarioConPermisosDeSubcategorias([]));
    $this->get(route('subcategories.create'))->assertForbidden();
    $this->get(route('subcategories.show', $sub))->assertForbidden();
    $this->get(route('subcategories.edit', $sub))->assertForbidden();

    $this->actingAs(usuarioConPermisosDeSubcategorias(['subcategories.ver', 'subcategories.crear', 'subcategories.editar']));
    $this->get(route('subcategories.create'))->assertRedirect(route('subcategories.index'));
    $this->get(route('subcategories.show', $sub))->assertRedirect(route('subcategories.index'));
    $this->get(route('subcategories.edit', $sub))->assertRedirect(route('subcategories.index'));
});
