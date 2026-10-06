@extends('layouts.app')
@section('title', 'Gestión de Usuarios')
@section('content')
<!-- Contenedor Principal con animación de entrada -->
<div class="animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight">Gestión de Usuarios</h1>
            <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Administra las cuentas de acceso, asignación por sucursal y niveles de permisos.</p>
        </div>
        @can('usuarios.crear')
            <button type="button" onclick="openModal('create-user-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] dark:bg-sky-600 text-white rounded-full font-bold text-sm hover:bg-[#3cb0a4] shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Crear Nuevo Usuario</span>
            </button>
        @endcan
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
            <div class="w-full md:w-44">
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
                <label for="id_branch" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sucursal</label>
                <select name="id_branch" id="id_branch" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">
                    <option value="">Todas las Sucursales</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id_branch }}" {{ request('id_branch') == $branch->id_branch ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-40">
                <label for="is_active" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estado</label>
                <select name="is_active" id="is_active" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">
                    <option value="">Todos los Estados</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full px-5 py-2.5 bg-navy-sidebar text-white rounded-xl text-sm font-bold hover:bg-navy-active transition-all shadow-sm">
                    Filtrar
                </button>
            </div>
            @if(request()->anyFilled(['search', 'role', 'id_branch', 'is_active']))
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
                    <th class="px-6 py-3">Sucursal</th>
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
                                    {{ substr($user->username, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800 text-sm group-hover:text-[#005e66] transition-colors">{{ $user->username }}</div>
                                    <div class="text-xs text-slate-400 font-semibold">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Sucursal del Usuario -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100">
                            @if($user->branch)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/10">
                                    <svg class="w-3.5 h-3.5 text-sky-500 shrink-0" width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; min-height: 14px; max-height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span>{{ $user->branch->name }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-50 text-slate-400 border border-slate-200">
                                    Sin Sucursal
                                </span>
                            @endif
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
                            @if($user->is_active)
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
                            @php
                                // Solo un administrador puede modificar a otro administrador
                                $isProtectedUser = $user->hasRole('admin') && ! auth()->user()->isAdmin();
                            @endphp
                            <div class="flex items-center justify-end gap-2">
                                @if($isProtectedUser)
                                    <span class="text-xs text-slate-400 font-semibold italic">Protegido</span>
                                @else
                                @can('usuarios.editar')
                                    <!-- Botón Editar -->
                                    <button type="button" onclick="openEditUserModal('{{ route('users.update', $user) }}', {{ json_encode($user) }}, {{ json_encode($user->roles->pluck('id_role')->toArray()) }})" class="p-2.5 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 font-semibold text-xs transition-all flex items-center justify-center" title="Editar Usuario">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endcan

                                @can('usuarios.eliminar')
                                    @if(auth()->id() !== $user->id_user)
                                        @if($user->is_active)
                                            <!-- Botón Desactivar -->
                                            <button type="button" onclick="confirmDelete('{{ route('users.destroy', $user) }}', 'Usuario {{ addslashes($user->username) }}', false)" class="p-2.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all flex items-center justify-center" title="Desactivar Usuario">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </button>
                                        @else
                                            <!-- Botón Reactivar -->
                                            <button type="button" onclick="confirmDelete('{{ route('users.destroy', $user) }}', 'Usuario {{ addslashes($user->username) }}', true)" class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-semibold text-xs transition-all flex items-center justify-center" title="Reactivar Usuario">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        @endif
                                    @endif
                                @endcan

                                @cannot('usuarios.editar')
                                    @cannot('usuarios.eliminar')
                                        <span class="text-xs text-slate-400 font-semibold italic">Solo Lectura</span>
                                    @endcannot
                                @endcannot
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 bg-white rounded-2xl border border-slate-100 shadow-sm">
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
                    <label for="username" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Completo</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <input type="text" name="username" id="username" value="{{ old('modal_type') === 'create' ? old('username') : '' }}" placeholder="Ej. Juan Pérez" class="w-full bg-slate-50 border @error('username') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('username')
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
                        <input type="email" name="email" id="email" value="{{ old('modal_type') === 'create' ? old('email') : '' }}" placeholder="ejemplo@empresa.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('email')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Asignación de Sucursal -->
            <div>
                <label for="create-branch-select" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sucursal Asignada</label>
                <select name="id_branch" id="create-branch-select" class="w-full bg-slate-50 border @error('id_branch') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                    <option value="">Todas / Sin Sucursal Específica</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id_branch }}" {{ (old('modal_type') === 'create' && old('id_branch') == $branch->id_branch) ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('id_branch')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
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
                            <input type="password" name="password" id="password" placeholder="Mínimo 8 caracteres" class="w-full bg-slate-50 border @error('password') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-10 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                            <button type="button" onclick="togglePasswordInput('password', this)" class="absolute inset-y-0 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer" style="right: 14px;" title="Mostrar/Ocultar contraseña">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
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
                            <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repite la contraseña" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl pl-10 pr-10 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                            <button type="button" onclick="togglePasswordInput('password_confirmation', this)" class="absolute inset-y-0 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer" style="right: 14px;" title="Mostrar/Ocultar contraseña">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 pt-4">
                <!-- Roles -->
                <div>
                    <label for="create-role-select" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rol de Usuario</label>
                    <select name="roles[]" id="create-role-select" class="w-full bg-slate-50 border @error('roles') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        <option value="">Seleccionar rol</option>
                        @foreach($assignableRoles as $role)
                            <option value="{{ $role->id_role }}" {{ (old('modal_type') === 'create' && is_array(old('roles')) && in_array($role->id_role, old('roles'))) ? 'selected' : '' }}>
                                {{ strtoupper($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('roles')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label for="create-is_active" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estado de Acceso</label>
                    <select name="is_active" id="create-is_active" class="w-full bg-slate-50 border @error('is_active') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
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
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica los accesos, sucursal y credenciales del usuario.</p>
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
                        <input type="text" name="username" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('username') : '' }}" placeholder="Ej. Juan Pérez" class="w-full bg-slate-50 border @error('username') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('username')
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
                        <input type="email" name="email" id="edit-email" value="{{ old('modal_type') === 'edit' ? old('email') : '' }}" placeholder="ejemplo@empresa.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    </div>
                    @error('email')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Asignación de Sucursal -->
            <div>
                <label for="edit-branch-select" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sucursal Asignada</label>
                <select name="id_branch" id="edit-branch-select" class="w-full bg-slate-50 border @error('id_branch') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                    <option value="">Todas / Sin Sucursal Específica</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id_branch }}">
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('id_branch')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
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
                            <input type="password" name="password" id="edit-password" placeholder="Dejar en blanco para conservar" class="w-full bg-slate-50 border @error('password') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl pl-10 pr-10 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                            <button type="button" onclick="togglePasswordInput('edit-password', this)" class="absolute inset-y-0 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer" style="right: 14px;" title="Mostrar/Ocultar contraseña">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
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
                            <input type="password" name="password_confirmation" id="edit-password_confirmation" placeholder="Repite la contraseña" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl pl-10 pr-10 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                            <button type="button" onclick="togglePasswordInput('edit-password_confirmation', this)" class="absolute inset-y-0 flex items-center text-slate-400 hover:text-slate-600 transition-colors cursor-pointer" style="right: 14px;" title="Mostrar/Ocultar contraseña">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-100 pt-4">
                <!-- Roles -->
                <div>
                    <label for="edit-role-select" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rol de Usuario</label>
                    <select name="roles[]" id="edit-role-select" class="w-full bg-slate-50 border @error('roles') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        <option value="">Seleccionar rol</option>
                        @foreach($assignableRoles as $role)
                            <option value="{{ $role->id_role }}">
                                {{ strtoupper($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('roles')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                    <p id="edit-self-access-note" class="hidden text-slate-400 text-xs mt-1 font-semibold ml-2">No puedes modificar tus propios roles ni desactivar tu cuenta.</p>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Estado de la Cuenta</label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" id="edit-status-hidden" value="0">
                        <input type="checkbox" name="is_active" id="edit-status" value="1" class="sr-only peer" {{ old('modal_type') === 'edit' ? (old('is_active') === '1' ? 'checked' : '') : '' }}>
                        <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 peer-disabled:opacity-50"></div>
                        <span class="text-sm font-semibold text-slate-600" id="edit-status_label">Usuario Activo</span>
                    </label>
                    @error('is_active')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
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
        document.getElementById('edit-id').value = user.id_user;
        document.getElementById('edit-name').value = user.username;
        document.getElementById('edit-email').value = user.email;
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-password_confirmation').value = '';
        
        // Seleccionar sucursal
        const branchSelect = document.getElementById('edit-branch-select');
        if (branchSelect) {
            branchSelect.value = user.id_branch || '';
        }

        // Seleccionar rol en el desplegable
        const roleSelect = document.getElementById('edit-role-select');
        if (roleSelect) {
            roleSelect.value = (roleIds && roleIds.length > 0) ? roleIds[0] : '';
        }

        const statusChk = document.getElementById('edit-status');
        statusChk.checked = user.is_active;

        // Un usuario no puede cambiar sus propios roles ni su estado: los campos
        // deshabilitados no se envían y el servidor conserva los valores actuales.
        const isSelf = Number(user.id_user) === {{ auth()->id() }};
        if (roleSelect) {
            roleSelect.disabled = isSelf;
        }
        statusChk.disabled = isSelf;
        document.getElementById('edit-status-hidden').disabled = isSelf;
        document.getElementById('edit-self-access-note').classList.toggle('hidden', !isSelf);
        
        const label = document.getElementById('edit-status_label');
        label.textContent = user.is_active ? 'Usuario Activo' : 'Usuario Inactivo';
        
        openModal('edit-user-modal');
    }

    function togglePasswordInput(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const eyeOpen = btn.querySelector('.eye-open');
        const eyeClosed = btn.querySelector('.eye-closed');
        if (eyeOpen && eyeClosed) {
            eyeOpen.classList.toggle('hidden', isPassword);
            eyeClosed.classList.toggle('hidden', !isPassword);
        }
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
                    id_user: "{{ old('id') }}",
                    username: "{{ old('username') }}",
                    email: "{{ old('email') }}",
                    id_branch: "{{ old('id_branch') }}",
                    is_active: {{ old('is_active') === '1' ? 'true' : 'false' }}
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