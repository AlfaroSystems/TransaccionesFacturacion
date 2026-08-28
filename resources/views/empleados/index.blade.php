@extends('layouts.app')
@section('title', 'Gestión de Empleados')
@section('content')
<div class="animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight transition-colors duration-300">Gestión de Empleados</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold mt-1">Administra el personal, sus correos de contacto, teléfonos y documentos de identidad.</p>
        </div>

        @can('empleados.crear')
        <button type="button" onclick="openModal('create-empleado-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Crear Nuevo Empleado</span>
        </button>
        @endcan
    </header>

    <!-- Barra de Búsqueda Rápida (Cliente-Side) -->
    <section class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 card-shadow mb-8 transition-colors duration-300">
        <div class="w-full">
            <label for="search-empleados" class="block text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider mb-2">Buscar Empleado</label>
            <div class="relative">
                <input type="text" id="search-empleados" placeholder="Buscar por nombre, correo, teléfono o DUI..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 pl-10 text-sm focus:outline-none focus:border-[#005e66] dark:focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                <div class="absolute left-3.5 top-3.5 text-slate-400 dark:text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Listado de Empleados -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    <th class="py-3 px-6 text-left w-32 pl-10">ID</th>
                    <th class="py-3 px-6">Nombre Completo</th>
                    <th class="py-3 px-6">Correo Electrónico</th>
                    <th class="py-3 px-6 text-center">Teléfono</th>
                    <th class="py-3 px-6 text-center">DUI</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="empleados-table-body">
                @forelse($empleados as $empleado)
                    <tr class="table-row-item group hover:scale-[1.005] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-l-2xl border-l border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-400 dark:text-slate-400 font-bold transition-colors duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center text-blue-500 dark:text-blue-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span>#{{ $empleado->id }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm font-bold text-slate-900 dark:text-slate-100 search-name transition-colors duration-300">
                            {{ $empleado->nombre_completo }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 search-email transition-colors duration-300">
                            {{ $empleado->correo }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 text-center font-semibold search-phone transition-colors duration-300">
                            {{ $empleado->telefono }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-500 dark:text-slate-400 text-center font-medium search-dui transition-colors duration-300">
                            {{ $empleado->dui }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-r-2xl border-r border-y border-slate-100 dark:border-slate-700/80 text-center transition-colors duration-300">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Editar -->
                                @can('empleados.editar')
                                <button type="button" onclick="openEditEmpleadoModal('{{ route('empleados.update', $empleado->id) }}', {{ json_encode($empleado) }})" class="p-2 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-100/50 dark:border-blue-800/60 rounded-xl transition-all" title="Editar Empleado">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan

                                <!-- Eliminar -->
                                @can('empleados.eliminar')
                                <button type="button" onclick="confirmDelete('{{ route('empleados.destroy', $empleado->id) }}', '{{ $empleado->nombre_completo }}')" class="p-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-100/50 dark:border-rose-800/60 rounded-xl transition-all" title="Eliminar Empleado">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 text-center text-slate-400 dark:text-slate-500 font-semibold shadow-sm transition-colors duration-300">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>No se encontraron empleados registrados.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- MODAL DE REGISTRO DE EMPLEADO -->
<div id="create-empleado-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-empleado-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 dark:bg-sky-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Registrar Nuevo Empleado</h2>
                <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Ingresa la información personal del nuevo colaborador.</p>
            </div>
        </div>
        <form action="{{ route('empleados.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">
            <!-- Nombre Completo -->
            <div>
                <label for="nombre_completo" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Nombre Completo</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </span>
                    <input type="text" name="nombre_completo" id="nombre_completo" value="{{ old('modal_type') === 'create' ? old('nombre_completo') : '' }}" placeholder="Ej. Juan Carlos Pérez" class="w-full bg-slate-50 dark:bg-slate-900 border @error('nombre_completo') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('nombre_completo')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label for="correo" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </span>
                    <input type="email" name="correo" id="correo" value="{{ old('modal_type') === 'create' ? old('correo') : '' }}" placeholder="juan.perez@empresa.com" class="w-full bg-slate-50 dark:bg-slate-900 border @error('correo') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('correo')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Teléfono -->
            <div>
                <label for="telefono" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Teléfono de Contacto</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    </span>
                    <input type="text" name="telefono" id="telefono" value="{{ old('modal_type') === 'create' ? old('telefono') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 dark:bg-slate-900 border @error('telefono') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('telefono')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- DUI -->
            <div>
                <label for="dui" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">DUI</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.333 0 4 .667 4 2v1H5v-1c0-1.333 2.667-2 4-2z" /></svg>
                    </span>
                    <input type="text" name="dui" id="dui" value="{{ old('modal_type') === 'create' ? old('dui') : '' }}" placeholder="Ej. 00000000-0" class="w-full bg-slate-50 dark:bg-slate-900 border @error('dui') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('dui')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeModal('create-empleado-modal')" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Empleado
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE EMPLEADO -->
<div id="edit-empleado-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-empleado-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] dark:bg-sky-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Editar Empleado</h2>
                <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Modifica los datos del colaborador seleccionado.</p>
            </div>
        </div>
        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Nombre Completo -->
            <div>
                <label for="edit-nombre_completo" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Nombre Completo</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </span>
                    <input type="text" name="nombre_completo" id="edit-nombre_completo" value="{{ old('modal_type') === 'edit' ? old('nombre_completo') : '' }}" placeholder="Ej. Juan Carlos Pérez" class="w-full bg-slate-50 dark:bg-slate-900 border @error('nombre_completo') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('nombre_completo')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label for="edit-correo" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </span>
                    <input type="email" name="correo" id="edit-correo" value="{{ old('modal_type') === 'edit' ? old('correo') : '' }}" placeholder="juan.perez@empresa.com" class="w-full bg-slate-50 dark:bg-slate-900 border @error('correo') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('correo')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Teléfono -->
            <div>
                <label for="edit-telefono" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Teléfono de Contacto</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    </span>
                    <input type="text" name="telefono" id="edit-telefono" value="{{ old('modal_type') === 'edit' ? old('telefono') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 dark:bg-slate-900 border @error('telefono') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('telefono')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- DUI -->
            <div>
                <label for="edit-dui" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">DUI</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.333 0 4 .667 4 2v1H5v-1c0-1.333 2.667-2 4-2z" /></svg>
                    </span>
                    <input type="text" name="dui" id="edit-dui" value="{{ old('modal_type') === 'edit' ? old('dui') : '' }}" placeholder="Ej. 00000000-0" class="w-full bg-slate-50 dark:bg-slate-900 border @error('dui') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                </div>
                @error('dui')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeModal('edit-empleado-modal')" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditEmpleadoModal(actionUrl, empleado) {
        const modal = document.getElementById('edit-empleado-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = empleado.id;
        document.getElementById('edit-nombre_completo').value = empleado.nombre_completo;
        document.getElementById('edit-correo').value = empleado.correo;
        document.getElementById('edit-telefono').value = empleado.telefono;
        document.getElementById('edit-dui').value = empleado.dui;
        openModal('edit-empleado-modal');
    }
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-empleados');
        const tableRows = document.querySelectorAll('.table-row-item');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();

                tableRows.forEach(row => {
                    const name = row.querySelector('.search-name').textContent.toLowerCase();
                    const email = row.querySelector('.search-email').textContent.toLowerCase();
                    const phone = row.querySelector('.search-phone').textContent.toLowerCase();
                    const dui = row.querySelector('.search-dui').textContent.toLowerCase();

                    if (name.includes(query) || email.includes(query) || phone.includes(query) || dui.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('empleados.update', old('id', 0)) }}";
                const oldEmpleado = {
                    id: "{{ old('id') }}",
                    nombre_completo: "{{ old('nombre_completo') }}",
                    correo: "{{ old('correo') }}",
                    telefono: "{{ old('telefono') }}",
                    dui: "{{ old('dui') }}"
                };
                openEditEmpleadoModal(editRoute, oldEmpleado);
            @else
                openModal('create-empleado-modal');
            @endif
        });
    </script>
@endif
@endsection