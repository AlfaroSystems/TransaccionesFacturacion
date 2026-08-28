@extends('layouts.app')
@section('title', 'Detalle de Proveedor')
@section('content')
<div class="animate-fade-in duration-300">
    {{-- Encabezado --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">
                    {{ $supplier->name }}
                </h1>
                @if($supplier->is_active)
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full border border-emerald-200">
                        Activo
                    </span>
                @else
                    <span class="px-3 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-full border border-rose-200">
                        Inactivo
                    </span>
                @endif
            </div>
            <p class="text-slate-400 text-sm font-semibold mt-1">
                Ficha de detalle del proveedor y sus contactos comerciales asociados.
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('suppliers.index') }}"
                class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold text-sm transition-all">
                ← Regresar
            </a>
            <button type="button"
                onclick="openModal('edit-supplier-modal-{{ $supplier->id_supplier }}')"
                class="px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md transition-all">
                Editar Proveedor
            </button>
        </div>
    </header>

    {{-- Datos Generales --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 mb-6">
        <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider mb-5">
            Datos Generales
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Código</p>
                <p class="text-sm font-extrabold text-[#005e66] font-mono">{{ $supplier->code ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Nombre del Proveedor</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">País</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->country }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Correo Electrónico</p>
                <p class="text-sm font-semibold text-slate-700 font-mono">{{ $supplier->email }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Teléfono</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Sitio Web</p>
                @if($supplier->website)
                    <a href="{{ $supplier->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm font-semibold text-[#005e66] hover:underline break-all">
                        {{ $supplier->website }}
                    </a>
                @else
                    <p class="text-sm font-semibold text-slate-700">-</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Estado</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Registrado el</p>
                <p class="text-sm font-semibold text-slate-700">
                    {{ $supplier->created_at?->format('d/m/Y h:i A') ?? '-' }}
                </p>
            </div>
        </div>

        @if($supplier->address)
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Dirección</p>
                <p class="text-sm font-semibold text-slate-700 leading-relaxed">{{ $supplier->address }}</p>
            </div>
        @endif
    </div>

    {{-- Contactos Asociados --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider">
                Contactos Asociados
            </h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-cyan-100 text-cyan-800 text-xs font-bold">
                {{ $supplier->contacts->count() }} {{ Str::plural('contacto', $supplier->contacts->count()) }}
            </span>
        </div>

        @if($supplier->contacts->isEmpty())
            <p class="text-center text-slate-400 font-semibold py-10">
                Este proveedor no tiene contactos registrados.
            </p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($supplier->contacts as $contact)
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-[#005e66] text-white flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($contact->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 text-sm">
                                    {{ $contact->full_name }}
                                </p>
                                @if(isset($contact->is_active))
                                    <span class="text-[10px] font-bold uppercase {{ $contact->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ $contact->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-slate-500">
                                <span class="font-bold text-slate-400 uppercase">Tel:</span>
                                {{ $contact->phone }}
                            </p>
                            <p class="text-xs text-slate-500">
                                <span class="font-bold text-slate-400 uppercase">Correo:</span>
                                {{ $contact->email ?? '-' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection