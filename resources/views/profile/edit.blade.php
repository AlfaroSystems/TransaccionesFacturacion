@extends('layouts.app')
@section('title', 'Mi Perfil')

@php
    $inputClass = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $labelClass = 'block text-xs font-bold text-slate-400 uppercase mb-1';
    $cardClass = 'bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-6 space-y-4';
    $buttonClass = 'px-5 py-2.5 rounded-xl text-sm font-bold bg-[#005e66] hover:bg-[#004d54] text-white';
@endphp

@section('content')
<div class="w-full max-w-3xl space-y-6 animate-fade-in duration-300">
    <div>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Cuenta</span>
            <span class="text-slate-400 dark:text-slate-600">•</span>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $user->username }}</span>
        </div>
        <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Mi Perfil</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Su nombre, correo y contraseña. La sucursal y el rol los asigna el administrador en Usuarios.
        </p>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="{{ $cardClass }}">
        @csrf
        @method('PATCH')

        <div>
            <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Datos de la cuenta</h2>
            <p class="text-xs text-slate-400 mt-0.5">Con este correo inicia sesión.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="username" class="{{ $labelClass }}">Nombre *</label>
                <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required autocomplete="name"
                    class="{{ $inputClass }} @error('username') border-rose-400 @enderror">
                @error('username')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="{{ $labelClass }}">Correo *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                    class="{{ $inputClass }} @error('email') border-rose-400 @enderror">
                @error('email')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            @if (session('status') === 'profile-updated')
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">✓ Datos guardados</span>
            @endif
            <button type="submit" class="{{ $buttonClass }}">Guardar datos</button>
        </div>
    </form>

    <form method="POST" action="{{ route('password.update') }}" class="{{ $cardClass }}">
        @csrf
        @method('PUT')

        <div>
            <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Cambiar contraseña</h2>
            <p class="text-xs text-slate-400 mt-0.5">Use una contraseña larga que no use en otros sitios.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach([
                ['current_password', 'Contraseña actual', 'current-password'],
                ['password', 'Nueva contraseña', 'new-password'],
                ['password_confirmation', 'Confirmar nueva', 'new-password'],
            ] as [$campo, $etiqueta, $autocompletar])
                <div>
                    <label for="update_password_{{ $campo }}" class="{{ $labelClass }}">{{ $etiqueta }}</label>
                    <input type="password" id="update_password_{{ $campo }}" name="{{ $campo }}" autocomplete="{{ $autocompletar }}"
                        class="{{ $inputClass }} @if($errors->updatePassword->has($campo)) border-rose-400 @endif">
                    @foreach($errors->updatePassword->get($campo) as $mensaje)
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $mensaje }}</p>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-end gap-3">
            @if (session('status') === 'password-updated')
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">✓ Contraseña actualizada</span>
            @endif
            <button type="submit" class="{{ $buttonClass }}">Cambiar contraseña</button>
        </div>
    </form>
</div>
@endsection
