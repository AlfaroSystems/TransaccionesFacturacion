@extends('layouts.app')
@section('title', $employee ? 'Editar Empleado' : 'Nuevo Empleado')

@php
    $inputClass = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $labelClass = 'block text-xs font-bold text-slate-400 uppercase mb-1';
    $activo = old('is_active', $employee?->is_active ?? true);
@endphp

@section('content')
<div class="w-full max-w-3xl space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Empleados</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $employee ? $employee->fullName() : 'Nuevo' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">{{ $employee ? 'Editar Empleado' : 'Nuevo Empleado' }}</h1>
        </div>
        <a href="{{ route('employees.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver</a>
    </div>

    <form method="POST" action="{{ $employee ? route('employees.update', $employee) : route('employees.store') }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @if($employee)
            @method('PUT')
        @endif

        @foreach([
            ['first_name', 'Nombres *', 'text', true],
            ['last_name', 'Apellidos *', 'text', true],
            ['dui', 'DUI', 'text', false],
            ['position', 'Cargo', 'text', false],
            ['phone', 'Teléfono', 'text', false],
            ['email', 'Correo', 'email', false],
        ] as [$campo, $etiqueta, $tipo, $requerido])
            <div>
                <label for="{{ $campo }}" class="{{ $labelClass }}">{{ $etiqueta }}</label>
                <input type="{{ $tipo }}" id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $employee?->$campo) }}" @required($requerido)
                    @if($campo === 'dui') placeholder="00000000-0" @endif
                    class="{{ $inputClass }} @error($campo) border-rose-400 @enderror">
                @error($campo)
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div>
            <label for="id_branch" class="{{ $labelClass }}">Sucursal *</label>
            <select id="id_branch" name="id_branch" required class="{{ $inputClass }} @error('id_branch') border-rose-400 @enderror">
                <option value="">-- Elija la sucursal --</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id_branch }}" @selected((string) old('id_branch', $employee?->id_branch) === (string) $branch->id_branch)>{{ $branch->name }}</option>
                @endforeach
            </select>
            @error('id_branch')
                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-end">
            <label class="flex items-center gap-3 cursor-pointer py-2.5">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($activo) class="w-4 h-4 rounded border-slate-300 text-[#005e66]">
                <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Empleado activo</span>
            </label>
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('employees.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Cancelar</a>
            <button type="submit" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">{{ $employee ? 'Guardar cambios' : 'Registrar empleado' }}</button>
        </div>
    </form>
</div>
@endsection
