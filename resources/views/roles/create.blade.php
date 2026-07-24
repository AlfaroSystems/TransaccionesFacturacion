@extends('layouts.app')

@section('title', 'Crear Nuevo Rol')

@section('content')
<div class="animate-fade-in duration-300 max-w-4xl mx-auto">
    <!-- Encabezado de Página -->
    <header class="mb-8 flex items-center gap-4">
        <a href="{{ route('roles.index') }}" class="p-2 bg-white text-slate-400 hover:text-navy-sidebar border border-slate-100 rounded-xl card-shadow transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 tracking-tight">Crear Nuevo Rol</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Define el nombre del rol y asóciale las capacidades del sistema.</p>
        </div>
    </header>

    <!-- Formulario de Creación -->
    <section class="bg-white p-6 md:p-8 rounded-2xl border border-slate-100 card-shadow">
        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            <!-- Información General -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="w-full">
                    <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre del Rol <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Ej: supervisor, moderador" class="w-full bg-slate-50 border @error('name') border-rose-500 @else border-slate-200 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        <span class="text-xs text-rose-500 font-bold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción del Rol</label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}" placeholder="Ej: Permite gestionar artículos e inventarios" class="w-full bg-slate-50 border @error('description') border-rose-500 @else border-slate-200 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold">
                    @error('description')
                        <span class="text-xs text-rose-500 font-bold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Asignación de Permisos -->
            <div class="border-t border-slate-100 pt-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-base font-extrabold text-navy-800">Asignar Permisos</h2>
                        <p class="text-slate-400 text-xs font-semibold mt-1">Selecciona los permisos que tendrá este rol dentro de la aplicación.</p>
                    </div>
                    
                    <button type="button" id="select-all" class="text-xs font-extrabold text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100/50 px-3 py-1.5 rounded-lg transition-all">
                        Seleccionar Todos
                    </button>
                </div>

                <!-- Lista de Permisos dividida por Módulos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Grupo 1: Gestión de Usuarios -->
                    <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100">
                        <h3 class="text-xs font-extrabold text-navy-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Gestión de Usuarios
                        </h3>
                        <div class="space-y-3">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'usuarios.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="permission-checkbox mt-1 rounded text-navy-sidebar focus:ring-navy-sidebar border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Grupo 2: Administración y Auditoría -->
                    <div class="bg-slate-50/50 p-5 rounded-2xl border border-slate-100">
                        <h3 class="text-xs font-extrabold text-navy-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Seguridad y Auditoría
                        </h3>
                        <div class="space-y-3">
                            @foreach($permissions->filter(fn($p) => str_starts_with($p->id, 'roles.') || str_starts_with($p->id, 'bitacora.')) as $permission)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="permission-checkbox mt-1 rounded text-navy-sidebar focus:ring-navy-sidebar border-slate-300 w-4 h-4 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @error('permissions')
                    <span class="text-xs text-rose-500 font-bold mt-3 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('roles.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-bold transition-all">
                    Cancelar
                </a>
                <button type="submit" class="px-5 py-2.5 bg-navy-sidebar hover:bg-navy-active text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all">
                    Guardar Rol
                </button>
            </div>
        </form>
    </section>
</div>

<!-- Javascript interactivo para Seleccionar Todos -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.permission-checkbox');

        selectAllBtn.addEventListener('click', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            selectAllBtn.textContent = allChecked ? 'Seleccionar Todos' : 'Deseleccionar Todos';
        });

        // Evento para actualizar texto del botón si se marcan a mano
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                selectAllBtn.textContent = allChecked ? 'Deseleccionar Todos' : 'Seleccionar Todos';
            });
        });
    });
</script>
@endsection
