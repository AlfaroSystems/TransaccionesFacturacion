@extends('layouts.app')
@section('title', 'Gestión de Roles y Permisos')
@section('content')
<div class="animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight">Roles y Permisos</h1>
            <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Configura las etiquetas de roles y asóciales permisos del sistema.</p>
        </div>
        @can('roles.administrar')
            <button type="button" onclick="openModal('create-role-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] dark:bg-sky-600 text-white rounded-full font-bold text-sm hover:bg-[#3cb0a4] shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Crear Nuevo Rol</span>
            </button>
        @endcan
    </header>

    <!-- Listado de Roles -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="px-6 py-3 pl-10">Rol</th>
                    <th class="px-6 py-3">Descripción</th>
                    <th class="px-6 py-3 text-center">Permisos</th>
                    <th class="px-6 py-3 text-center">Usuarios asignados</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200">
                        <td class="px-6 py-4 bg-white rounded-l-2xl border-l border-y border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold uppercase tracking-wider border border-blue-100">
                                    {{ $role->name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-slate-400 font-semibold max-w-xs truncate">
                            {{ $role->description ?? 'Sin descripción' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold">
                                {{ $role->permissions_count }} permisos
                            </span>
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center">
                            <span class="px-2.5 py-1 bg-navy-sidebar/10 text-navy-sidebar rounded-full text-xs font-bold">
                                {{ $role->users_count }} usuarios
                            </span>
                        </td>
                        <td class="px-6 py-4 bg-white rounded-r-2xl border-r border-y border-slate-100 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('roles.administrar')
                                    @if($role->name === 'admin')
                                        <!-- Rol Admin Protegido (No editable ni eliminable) -->
                                        <span class="px-3 py-1.5 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-not-allowed border border-slate-200" title="Rol del sistema protegido: posee acceso total permanente y no se puede modificar ni eliminar.">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Protegido
                                        </span>
                                    @else
                                        <!-- Editar -->
                                        <button type="button" onclick="openEditRoleModal('{{ route('roles.update', $role) }}', {{ json_encode($role) }}, {{ json_encode($role->permissions->pluck('id')->toArray()) }})" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all" title="Editar Rol">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Eliminar -->
                                        <button type="button" onclick="confirmDelete('{{ route('roles.destroy', $role) }}', '{{ $role->name }}')" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all" title="Eliminar Rol">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400 font-semibold italic">Solo Lectura</span>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 bg-white rounded-2xl border border-slate-100 text-center text-slate-400 font-semibold shadow-sm animate-pulse">
                            No hay roles registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
        
    <!-- Paginación -->
    @if($roles->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 mt-4">
            {{ $roles->links() }}
        </div>
    @endif
</div>

<!-- MODAL DE REGISTRO DE ROL -->
<div id="create-role-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-4xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-role-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Nuevo Rol</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Define el nombre del rol y asóciale las capacidades del sistema.</p>
            </div>
        </div>

        <form action="{{ route('roles.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="modal_type" value="create">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nombre -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre del Rol <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej: supervisor, moderador" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Descripción -->
                <div>
                    <label for="description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción del Rol</label>
                    <input type="text" name="description" id="description" value="{{ old('modal_type') === 'create' ? old('description') : '' }}" placeholder="Ej: Permite gestionar artículos e inventarios" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Asignación de Permisos -->
            <div class="border-t border-slate-100 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Asignar Permisos</h3>
                        <p class="text-slate-400 text-xs font-semibold mt-1">Selecciona los permisos que tendrá este rol dentro de la aplicación.</p>
                    </div>
                    <button type="button" id="create-select-all" class="text-xs font-extrabold text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100/50 px-3 py-1.5 rounded-lg transition-all">
                        Seleccionar Todos
                    </button>
                </div>

                <!-- Lista de Permisos por Módulos -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Grupo 1: Gestión de Usuarios -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            Gestión de Usuarios
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'usuarios.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 2: Gestión de Empleados -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Gestión de Empleados
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'empleados.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 3: Empresa y Sucursales -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Configuración de Empresa y Sucursales
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'branches.') || str_starts_with($p->id, 'companies.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 4: Almacenes e Inventarios -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                            Gestión de Almacenes y Categorías
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'warehouses.') || str_starts_with($p->id, 'warehouse_categories.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 5: Ubicaciones -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Ubicaciones y Mapa
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'locations.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 6: Administración y Auditoría -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Seguridad y Auditoría
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'roles.') || str_starts_with($p->id, 'bitacora.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 7: Catálogo de Productos -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Catálogo de Productos y Unidades
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'products.') || str_starts_with($p->id, 'categories.') || str_starts_with($p->id, 'subcategories.') || str_starts_with($p->id, 'units.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 8: Gestión de Proveedores -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                            Gestión de Proveedores
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'suppliers.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="create-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('create-role-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Rol
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE ROL -->
<div id="edit-role-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-4xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-role-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Rol</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica los detalles del rol y sus permisos de acceso.</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nombre -->
                <div>
                    <label for="edit-name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre del Rol <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej: supervisor, moderador" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Descripción -->
                <div>
                    <label for="edit-description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción del Rol</label>
                    <input type="text" name="description" id="edit-description" value="{{ old('modal_type') === 'edit' ? old('description') : '' }}" placeholder="Ej: Permite gestionar artículos e inventarios" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Asignación de Permisos -->
            <div class="border-t border-slate-100 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Asignar Permisos</h3>
                        <p class="text-slate-400 text-xs font-semibold mt-1">Selecciona los permisos que tendrá este rol dentro de la aplicación.</p>
                    </div>
                    <button type="button" id="edit-select-all" class="text-xs font-extrabold text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100/50 px-3 py-1.5 rounded-lg transition-all">
                        Seleccionar Todos
                    </button>
                </div>

                <!-- Lista de Permisos por Módulos -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Grupo 1: Gestión de Usuarios -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            Gestión de Usuarios
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'usuarios.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 2: Gestión de Empleados -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Gestión de Empleados
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'empleados.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 3: Empresa y Sucursales -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Configuración de Empresa y Sucursales
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'branches.') || str_starts_with($p->id, 'companies.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 4: Almacenes e Inventarios -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                            Gestión de Almacenes y Categorías
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'warehouses.') || str_starts_with($p->id, 'warehouse_categories.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 5: Ubicaciones -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                            Ubicaciones y Mapa
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'locations.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 6: Administración y Auditoría -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Seguridad y Auditoría
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'roles.') || str_starts_with($p->id, 'bitacora.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 7: Catálogo de Productos -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Catálogo de Productos y Unidades
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'products.') || str_starts_with($p->id, 'categories.') || str_starts_with($p->id, 'subcategories.') || str_starts_with($p->id, 'units.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 8: Gestión de Proveedores -->
                    <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                            Gestión de Proveedores
                        </h4>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'suppliers.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="edit-permission-{{ str_replace('.', '-', $permission->id) }}" class="edit-permission-checkbox mt-0.5 rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-role-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditRoleModal(actionUrl, role, permissionIds) {
        const modal = document.getElementById('edit-role-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = role.id;
        document.getElementById('edit-name').value = role.name;
        document.getElementById('edit-description').value = role.description || '';
        
        // El rol admin está protegido contra cambio de nombre
        const nameInput = document.getElementById('edit-name');
        if (role.name === 'admin') {
            nameInput.disabled = true;
            nameInput.classList.add('bg-slate-100', 'cursor-not-allowed');
        } else {
            nameInput.disabled = false;
            nameInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
        }

        // Reset all checkboxes for permissions
        const checkboxes = document.querySelectorAll('.edit-permission-checkbox');
        checkboxes.forEach(chk => {
            chk.checked = permissionIds.includes(chk.value);
        });

        updateSelectAllButtonText('edit-select-all', checkboxes);
        openModal('edit-role-modal');
    }

    function updateSelectAllButtonText(btnId, checkboxes) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        btn.textContent = allChecked ? 'Deseleccionar Todos' : 'Seleccionar Todos';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Setup "Select All" for Create modal
        const createSelectAll = document.getElementById('create-select-all');
        const createCheckboxes = document.querySelectorAll('.create-permission-checkbox');
        if (createSelectAll) {
            createSelectAll.addEventListener('click', () => {
                const allChecked = Array.from(createCheckboxes).every(cb => cb.checked);
                createCheckboxes.forEach(cb => cb.checked = !allChecked);
                createSelectAll.textContent = allChecked ? 'Seleccionar Todos' : 'Deseleccionar Todos';
            });
            createCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => updateSelectAllButtonText('create-select-all', createCheckboxes));
            });
        }

        // Setup "Select All" for Edit modal
        const editSelectAll = document.getElementById('edit-select-all');
        const editCheckboxes = document.querySelectorAll('.edit-permission-checkbox');
        if (editSelectAll) {
            editSelectAll.addEventListener('click', () => {
                const allChecked = Array.from(editCheckboxes).every(cb => cb.checked);
                editCheckboxes.forEach(cb => cb.checked = !allChecked);
                editSelectAll.textContent = allChecked ? 'Seleccionar Todos' : 'Deseleccionar Todos';
            });
            editCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => updateSelectAllButtonText('edit-select-all', editCheckboxes));
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('roles.update', old('id', 0)) }}";
                const oldRole = {
                    id: "{{ old('id') }}",
                    name: "{{ old('name') }}",
                    description: "{{ old('description') }}"
                };
                const oldPermissions = {!! json_encode(old('permissions', [])) !!};
                openEditRoleModal(editRoute, oldRole, oldPermissions);
            @else
                openModal('create-role-modal');
            @endif
        });
    </script>
@endif
@endsection