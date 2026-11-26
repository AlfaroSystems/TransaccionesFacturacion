@extends('layouts.app')
@section('title', $branch->name)

@php
    $labelClass = 'block text-[11px] font-bold text-slate-400 uppercase';
    $ubicacion = collect([$branch->district?->name, $branch->municipality?->name, $branch->department?->name])->filter()->join(', ');
@endphp

@section('content')
<div class="w-full max-w-4xl space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-primary-soft text-on-primary-soft dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Sucursales</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $branch->company->name ?? 'Sin empresa' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-primary dark:text-accent dark:text-teal-400 tracking-tight mt-1">{{ $branch->name }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2">
                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold {{ $branch->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-sunken text-muted dark:bg-slate-800 dark:text-slate-400' }}">
                    {{ $branch->is_active ? '● Activa' : '○ Inactiva' }}
                </span>
                @if($branch->is_purchasing_department)
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                        Departamento de compras: lo comprado entra a sus bodegas
                    </span>
                @endif
            </div>
        </div>
        <a href="{{ route('branches.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver</a>
    </div>

    <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs p-6 grid grid-cols-1 md:grid-cols-2 gap-5 text-sm text-slate-700 dark:text-slate-200">
        <div>
            <span class="{{ $labelClass }}">Dirección</span>
            {{ $branch->address ?: '—' }}
        </div>
        <div>
            <span class="{{ $labelClass }}">Distrito, municipio y departamento</span>
            {{ $ubicacion ?: '—' }}
        </div>
        <div>
            <span class="{{ $labelClass }}">Teléfono</span>
            {{ $branch->phone ?: '—' }}
        </div>
        <div>
            <span class="{{ $labelClass }}">Correo</span>
            {{ $branch->email ?: '—' }}
        </div>
        @if($branch->description)
            <div class="md:col-span-2">
                <span class="{{ $labelClass }}">Descripción</span>
                {{ $branch->description }}
            </div>
        @endif
    </div>

    <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs p-6 space-y-3">
        <h2 class="text-base font-extrabold text-slate-800 dark:text-slate-100">Bodegas</h2>
        @forelse($branch->warehouses as $warehouse)
            <div class="flex items-center justify-between text-sm border-b border-line dark:border-slate-800 pb-2 last:border-0">
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $warehouse->name }}</span>
                <span class="text-xs {{ $warehouse->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">{{ $warehouse->is_active ? 'Activa' : 'Inactiva' }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">La sucursal no tiene bodegas.</p>
        @endforelse
    </div>
</div>
@endsection
