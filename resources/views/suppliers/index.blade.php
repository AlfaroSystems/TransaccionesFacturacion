@extends('layouts.app')
 
@section('title', 'Proveedores')
 
@section('content')
<div class="animate-fade-in duration-300">
 
    {{-- Mensajes --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <span class="font-semibold text-sm">{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">✕</button>
        </div>
    @endif
 
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <span class="font-semibold text-sm">{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">✕</button>
        </div>
    @endif
 
    {{-- Encabezado --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[#005e66]">
                Gestión de Proveedores
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                Administración de proveedores y sus contactos.
            </p>
        </div>
 
        <a href="{{ route('suppliers.create') }}"
            class="px-6 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold shadow transition-all">
            + Nuevo Proveedor
        </a>
    </header>
 
    {{-- Buscador --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('suppliers.index') }}">
            <div class="flex flex-col md:flex-row gap-4">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar por nombre, país o correo del contacto..."
                    class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:border-[#005e66]">
 
                <button
                    type="submit"
                    class="px-6 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-xl font-bold">
                    Buscar
                </button>
 
                @if(request('search'))
                    <a
                        href="{{ route('suppliers.index') }}"
                        class="px-6 py-3 bg-slate-200 hover:bg-slate-300 rounded-xl font-bold">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>
 
    {{-- Tabla --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs uppercase font-bold text-slate-500">
                            Nombre
                        </th>
                        <th class="px-6 py-4 text-left text-xs uppercase font-bold text-slate-500">
                            País
                        </th>
                        <th class="px-6 py-4 text-left text-xs uppercase font-bold text-slate-500">
                            Correo
                        </th>
                        <th class="px-6 py-4 text-left text-xs uppercase font-bold text-slate-500">
                            Teléfono
                        </th>
                        <th class="px-6 py-4 text-center text-xs uppercase font-bold text-slate-500">
                            Contactos
                        </th>
                        <th class="px-6 py-4 text-center text-xs uppercase font-bold text-slate-500">
                            Acciones
                        </th>
                    </tr>
                </thead>
 
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">
                                    {{ $supplier->name }}
                                </div>
                            </td>
 
                            <td class="px-6 py-4 text-slate-600">
                                {{ $supplier->country }}
                            </td>
 
                            <td class="px-6 py-4 text-slate-600">
                                {{ $supplier->email }}
                            </td>
 
                            <td class="px-6 py-4 text-slate-600">
                                {{ $supplier->phone ?? '-' }}
                            </td>
 
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-cyan-100 text-cyan-800 text-xs font-bold">
                                    {{ $supplier->contacts->count() }}
                                </span>
                            </td>
 
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    {{-- Ver --}}
                                    <a
                                        href="{{ route('suppliers.show', $supplier) }}"
                                        class="px-3 py-2 rounded-lg bg-sky-100 hover:bg-sky-200 text-sky-700 text-xs font-bold">
                                        Ver
                                    </a>
 
                                    {{-- Editar --}}
                                    <a
                                        href="{{ route('suppliers.edit', $supplier) }}"
                                        class="px-3 py-2 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-700 text-xs font-bold">
                                        Editar
                                    </a>
 
                                    {{-- Eliminar --}}
                                    <form
                                        action="{{ route('suppliers.destroy', $supplier) }}"
                                        method="POST"
                                        onsubmit="return confirm('¿Desea eliminar este proveedor?')">
                                        @csrf
                                        @method('DELETE')
 
                                        <button
                                            class="px-3 py-2 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-6 py-12 text-center text-slate-400 font-semibold">
                                No hay proveedores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
 
        {{-- Paginación --}}
        @if($suppliers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
 
</div>
@endsection