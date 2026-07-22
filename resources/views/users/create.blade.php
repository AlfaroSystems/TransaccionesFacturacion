@extends('layouts.app')

@section('title', 'Crear Usuario')

@section('content')
<div class="max-w-2xl mx-auto animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-8">
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1 text-slate-400 hover:text-navy-sidebar text-sm font-bold transition-colors mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            <span>Volver al Listado</span>
        </a>
        <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 tracking-tight">Crear Nuevo Usuario</h1>
        <p class="text-slate-400 text-sm font-semibold mt-1">Registra una nueva cuenta de usuario y define sus accesos.</p>
    </header>

    <!-- Formulario -->
    <section class="bg-white p-8 rounded-2xl border border-slate-100 card-shadow">
        <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Completo</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Ej. Juan Pérez" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-navy-sidebar @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('name')
                    <p class="text-xs text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Correo Electrónico -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo Electrónico</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="correo@ejemplo.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-navy-sidebar @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('email')
                    <p class="text-xs text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Rol -->
                <div>
                    <label for="role" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Rol del Usuario</label>
                    <select name="role" id="role" class="w-full bg-slate-50 border @error('role') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-navy-sidebar @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Usuario / Lector</option>
                        <option value="editor" {{ old('role') === 'editor' ? 'selected' : '' }}>Editor</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                    </select>
                    @error('role')
                        <p class="text-xs text-rose-500 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado -->
                <div>
                    <label for="status" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estado Inicial</label>
                    <select name="status" id="status" class="w-full bg-slate-50 border @error('status') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-navy-sidebar @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                    @error('status')
                        <p class="text-xs text-rose-500 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Contraseña</label>
                    <input type="password" name="password" id="password" placeholder="Mínimo 8 caracteres" class="w-full bg-slate-50 border @error('password') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-navy-sidebar @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('password')
                        <p class="text-xs text-rose-500 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Repite la contraseña" class="w-full bg-slate-50 border border-slate-200 focus:border-navy-sidebar rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="border-t border-slate-100 pt-6 flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-bold transition-all text-center">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-navy-sidebar text-white hover:bg-navy-active rounded-xl text-sm font-bold transition-all shadow-sm">
                    Guardar Usuario
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
