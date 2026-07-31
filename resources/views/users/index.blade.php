@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<!-- Contenedor Principal con animación de entrada -->
<div class="animate-fade-in duration-300">
    <!-- Mensajes de Sesión -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm animate-bounce-subtle">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-semibold text-sm">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove();" class="text-emerald-500 hover:text-emerald-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm animate-bounce-subtle">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-semibold text-sm">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove();" class="text-rose-500 hover:text-rose-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 tracking-tight">Gestión de Usuarios</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Administra las cuentas de acceso y sus niveles de permisos.</p>
        </div>

        <button type="button" onclick="openModal('create-user-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-navy-sidebar text-white rounded-full font-bold text-sm hover:bg-navy-active shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Crear Nuevo Usuario</span>
        </button>
    </header>

    <!-- Barra de Búsqueda y Filtros -->
    <section class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow mb-8">
        <form action="{{ route('users.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label for="search" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Buscar</label>
                <div class="relative">
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Buscar por nombre o correo..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 pl-10 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">
                    <div class="absolute left-3.5 top-3.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-48">
                <label for="role" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rol</label>
                <select name="role" id="role" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">
                    <option value="">Todos los Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-48">
                <label for="status" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estado</label>
                <select name="status" id="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">
                    <option value="">Todos los Estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div class="w-full md:w-auto">
                <button type="submit" class="w-full px-5 py-2.5 bg-navy-sidebar text-white rounded-xl text-sm font-bold hover:bg-navy-active transition-all shadow-sm">
                    Filtrar
                </button>
            </div>
            @if(request()->anyFilled(['search', 'role', 'status']))
                <div class="w-full md:w-auto">
                    <a href="{{ route('users.index') }}" class="block w-full px-5 py-2.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-sm font-bold transition-all text-center">
                        Limpiar
                    </a>
                </div>
            @endif
        </form>
    </section>

    <!-- Listado de Usuarios -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="px-6 py-3 pl-10">Usuario</th>
                    <th class="px-6 py-3">Rol</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Fecha Registro</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200">
                        <!-- Datos del Usuario con Avatar Inicial -->
                        <td class="px-6 py-4 bg-white rounded-l-2xl border-l border-y border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm text-navy-sidebar bg-slate-100 uppercase select-none group-hover:bg-[#005e66] group-hover:text-white transition-all">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-sm group-hover:text-[#005e66] transition-colors">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-400 font-semibold">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Rol del Usuario -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100">
                            @forelse($user->roles as $role)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/10 mr-1 last:mr-0">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-50 text-slate-500 ring-1 ring-inset ring-slate-500/10">
                                    Sin Rol
                                </span>
                            @endforelse
                        </td>

                        <!-- Estado del Usuario -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100">
                            @if($user->status === 'active')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <!-- Fecha de Registro -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm text-slate-400 font-semibold">
                            {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/D' }}
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 bg-white rounded-r-2xl border-r border-y border-slate-100 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Botón Editar -->
                                <button type="button" onclick="openEditUserModal('{{ route('users.update', $user) }}', {{ json_encode($user) }}, {{ json_encode($user->roles->pluck('id')->toArray()) }})" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all" title="Editar Usuario">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Botón Eliminar -->
                                <button type="button" onclick="confirmDelete('{{ route('users.destroy', $user) }}', '{{ $user->name }}')" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all" title="Eliminar Usuario">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-slate-600 font-bold">No se encontraron usuarios</h3>
                                <p class="text-slate-400 text-xs mt-1">Prueba a ajustar los criterios de búsqueda o de filtrado.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <!-- Enlaces de Paginación -->
    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 mt-4">
            {{ $users->links() }}
        </div>
    @endif
</div>

<!-- MODAL DE REGISTRO DE USUARIO -->
<div id="create-user-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-2xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-user-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Nuevo Colaborador</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Ingresa los datos para habilitar el acceso al sistema.</p>
            </div>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nombre -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Completo</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Juan Pérez" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('name')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Correo Electrónico -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo Electrónico</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </span>
                        <input type="email" name="email" id="email" value="{{ old('modal_type') === 'create' ? old('email') : '' }}" placeholder="ejemplo@farmacia.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('email')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Seguridad de Acceso</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </span>
                            <input type="password" name="password" id="password" placeholder="Mínimo 8 caracteres" class="w-full bg-slate-50 border @error('password') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        </div>
                        @error('password')
                            @if(old('modal_type') === 'create')
                                <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Confirmar Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </span>
                            <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repite la contraseña" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 pt-4">
                <!-- Roles -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rol de Usuario</label>
                    <div class="space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ (old('modal_type') === 'create' && is_array(old('roles')) && in_array($role->id, old('roles'))) ? 'checked' : '' }} class="rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estado de Acceso</label>
                    <select name="status" id="status" class="w-full bg-slate-50 border @error('status') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('create-user-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE USUARIO -->
<div id="edit-user-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-2xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-user-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Usuario</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica los accesos y credenciales del usuario.</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nombre -->
                <div>
                    <label for="edit-name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Completo</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Juan Pérez" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('name')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Correo Electrónico -->
                <div>
                    <label for="edit-email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo Electrónico</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </span>
                        <input type="email" name="email" id="edit-email" value="{{ old('modal_type') === 'edit' ? old('email') : '' }}" placeholder="ejemplo@farmacia.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('email')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cambiar Contraseña (Opcional)</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Contraseña -->
                    <div>
                        <label for="edit-password" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nueva Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </span>
                            <input type="password" name="password" id="edit-password" placeholder="Dejar en blanco para conservar" class="w-full bg-slate-50 border @error('password') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                        </div>
                        @error('password')
                            @if(old('modal_type') === 'edit')
                                <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                            @endif
                        @enderror
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div>
                        <label for="edit-password_confirmation" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Confirmar Contraseña</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </span>
                            <input type="password" name="password_confirmation" id="edit-password_confirmation" placeholder="Repite la contraseña" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 pt-4">
                <!-- Roles -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rol de Usuario</label>
                    <div class="space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-200" id="edit-roles-container">
                        @foreach($roles as $role)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" id="edit-role-{{ $role->id }}" class="edit-role-checkbox rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Estado de la Cuenta</label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="status" value="inactive">
                        <input type="checkbox" name="status" id="edit-status" value="active" class="sr-only peer" {{ old('modal_type') === 'edit' ? (old('status') === 'active' ? 'checked' : '') : '' }}>
                        <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="text-sm font-semibold text-slate-600" id="edit-status_label">Usuario Activo</span>
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-user-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
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
    function openEditUserModal(actionUrl, user, roleIds) {
        const modal = document.getElementById('edit-user-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = user.id;
        document.getElementById('edit-name').value = user.name;
        document.getElementById('edit-email').value = user.email;
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-password_confirmation').value = '';
        
        // Reset all checkbox states
        const checkboxes = document.querySelectorAll('.edit-role-checkbox');
        checkboxes.forEach(chk => {
            chk.checked = roleIds.includes(parseInt(chk.value));
        });
        
        const statusChk = document.getElementById('edit-status');
        statusChk.checked = user.status === 'active';
        
        const label = document.getElementById('edit-status_label');
        label.textContent = user.status === 'active' ? 'Usuario Activo' : 'Usuario Inactivo';
        
        openModal('edit-user-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const statusChk = document.getElementById('edit-status');
        const label = document.getElementById('edit-status_label');
        if (statusChk && label) {
            statusChk.addEventListener('change', () => {
                label.textContent = statusChk.checked ? 'Usuario Activo' : 'Usuario Inactivo';
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('users.update', old('id', 0)) }}";
                const oldUser = {
                    id: "{{ old('id') }}",
                    name: "{{ old('name') }}",
                    email: "{{ old('email') }}",
                    status: "{{ old('status', 'inactive') }}"
                };
                const oldRoles = {!! json_encode(old('roles', [])) !!}.map(Number);
                openEditUserModal(editRoute, oldUser, oldRoles);
            @else
                openModal('create-user-modal');
            @endif
        });
    </script>
@endif

@endsection
