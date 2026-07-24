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

        <a href="{{ route('users.create') }}" class="flex items-center justify-center gap-2 px-5 py-3 bg-navy-sidebar text-white rounded-full font-bold text-sm hover:bg-navy-active shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Crear Nuevo Usuario</span>
        </a>
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
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                    <option value="editor" {{ request('role') === 'editor' ? 'selected' : '' }}>Editor</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Usuario</option>
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

            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="flex-1 md:flex-initial px-5 py-2.5 bg-navy-sidebar text-white rounded-xl text-sm font-bold hover:bg-navy-active transition-all shadow-sm">
                    Filtrar
                </button>
                @if(request()->anyFilled(['search', 'role', 'status']))
                    <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-sm font-bold transition-all text-center">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </section>

    <!-- Listado de Usuarios -->
    <section class="bg-white rounded-2xl border border-slate-100 card-shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Fecha Registro</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/30 transition-colors group">
                            <!-- Datos del Usuario con Avatar Inicial -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm text-navy-sidebar bg-slate-100 uppercase select-none group-hover:bg-navy-sidebar group-hover:text-white transition-all">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm group-hover:text-navy-sidebar transition-colors">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-400 font-semibold">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Rol del Usuario -->
                            <td class="px-6 py-4">
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
                            <td class="px-6 py-4">
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
                            <td class="px-6 py-4 text-sm text-slate-400 font-semibold">
                                {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/D' }}
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Botón Editar -->
                                    <a href="{{ route('users.edit', $user) }}" class="p-2 text-slate-400 hover:text-navy-sidebar hover:bg-slate-100 rounded-xl transition-all" title="Editar Usuario">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>

                                    <!-- Botón Eliminar -->
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Eliminar Usuario">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
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
        </div>

        <!-- Enlaces de Paginación -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
