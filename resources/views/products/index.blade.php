@extends('layouts.app')

@section('title', 'Catálogo de Productos')

@section('content')

<div class="max-w-7xl mx-auto space-y-6">

    <!-- Mensajes Flash -->
    @if(session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-2xl shadow-sm flex items-center justify-between transition-all">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold">✓</div>
                <p class="font-semibold text-sm">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold text-lg p-1">✕</button>
        </div>
    @endif

    <!-- Encabezado Principal -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-blue-100 text-blue-800 rounded-lg">Farmacia La Merced</span>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">Módulo de Inventario</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight mt-1">
                Catálogo de Medicamentos & Productos
            </h1>
            <p class="text-slate-500 text-sm mt-0.5">
                Consulte, filtre y gestione el catálogo general, precios y niveles de existencias.
            </p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <a href="{{ route('products.create') }}"
               class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition-all flex items-center justify-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuevo Medicamento / Producto</span>
            </a>
        </div>
    </div>

    <!-- Tarjetas de Métricas Rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                💊
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Catálogo</span>
                <span class="text-xl font-extrabold text-slate-800">{{ $products->total() }}</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                ✓
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Activos</span>
                <span class="text-xl font-extrabold text-emerald-600">
                    {{ \App\Models\Product::where('is_active', true)->count() }}
                </span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                ⚠️
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bajo Stock</span>
                <span class="text-xl font-extrabold text-rose-600">
                    {{ \App\Models\Product::whereColumn('stock', '<=', 'min_stock')->count() }}
                </span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold">
                🏷️
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Categorías</span>
                <span class="text-xl font-extrabold text-purple-600">{{ $categories->count() }}</span>
            </div>
        </div>
    </div>

    <!-- =========================================================================
     *  BUSCADOR MULTICRITERIO (PANEL SUPERIOR DE FILTROS)
     * ========================================================================= -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 transition-all">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Filtros de Búsqueda Multicriterio</h2>
        </div>

        <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

            <!-- 1. Búsqueda por Texto (SKU / Nombre) -->
            <div class="md:col-span-5">
                <label for="search" class="block text-xs font-bold text-slate-600 uppercase mb-1.5 flex items-center gap-1">
                    <span>🔍</span> Búsqueda por SKU o Nombre
                </label>
                <div class="relative">
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ej. Paracetamol, PRD-2026, amoxicilina..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- 2. Filtro por Categoría -->
            <div class="md:col-span-3">
                <label for="id_category" class="block text-xs font-bold text-slate-600 uppercase mb-1.5 flex items-center gap-1">
                    <span>🏷️</span> Categoría
                </label>
                <select id="id_category"
                        name="id_category"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('id_category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Filtro por Estado (Activo / Inactivo) -->
            <div class="md:col-span-2">
                <label for="is_active" class="block text-xs font-bold text-slate-600 uppercase mb-1.5 flex items-center gap-1">
                    <span>⚡</span> Estado
                </label>
                <select id="is_active"
                        name="is_active"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Solo Activos</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Solo Inactivos</option>
                </select>
            </div>

            <!-- Botones de Acción -->
            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit"
                        class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition text-sm flex items-center justify-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Filtrar</span>
                </button>

                @if(request()->hasAny(['search', 'id_category', 'is_active']))
                    <a href="{{ route('products.index') }}"
                       title="Restablecer filtros"
                       class="bg-slate-100 hover:bg-slate-200 text-slate-600 p-2.5 rounded-xl transition text-sm font-semibold flex items-center justify-center">
                        ✕
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Tabla del Catálogo -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/80 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">SKU / Identificador</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Medicamento / Producto</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Precio Venta</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Existencias</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Estado</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                    <tr class="hover:bg-blue-50/40 transition">
                        <!-- SKU & UUID -->
                        <td class="px-6 py-4 align-middle">
                            <span class="font-mono bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-blue-200 inline-block">
                                {{ $product->sku }}
                            </span>
                            <span class="text-[11px] text-slate-400 font-mono block truncate max-w-[130px] mt-1" title="{{ $product->uuid }}">
                                {{ $product->uuid }}
                            </span>
                        </td>

                        <!-- Nombre & Código de barras -->
                        <td class="px-6 py-4 align-middle">
                            <a href="{{ route('products.show', $product->id) }}" class="font-bold text-slate-800 hover:text-blue-600 transition text-sm block">
                                {{ $product->name }}
                            </a>
                            @if($product->barcode)
                                <span class="text-xs text-slate-400 font-mono flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                                    {{ $product->barcode }}
                                </span>
                            @endif
                        </td>

                        <!-- Categoría y Subcategoría -->
                        <td class="px-6 py-4 align-middle text-xs">
                            <span class="font-semibold text-slate-700 block">{{ $product->category->name ?? 'Sin categoría' }}</span>
                            <span class="text-slate-400 block">{{ $product->subCategory->name ?? '-' }}</span>
                        </td>

                        <!-- Precios -->
                        <td class="px-6 py-4 align-middle text-right">
                            <span class="font-extrabold text-slate-800 text-sm block">
                                ${{ number_format($product->sale_price, 2) }}
                            </span>
                            <span class="text-[11px] text-slate-400 block">
                                Costo: ${{ number_format($product->purchase_price, 2) }}
                            </span>
                        </td>

                        <!-- Stock -->
                        <td class="px-6 py-4 align-middle text-center">
                            @if($product->is_low_stock)
                                <span class="bg-rose-100 text-rose-800 text-xs px-2.5 py-1 rounded-full font-extrabold inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    {{ $product->stock }} (Bajo)
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded-full font-bold">
                                    {{ $product->stock }} {{ $product->saleUnit->abbreviation ?? 'uds' }}
                                </span>
                            @endif
                        </td>

                        <!-- Estado -->
                        <td class="px-6 py-4 align-middle text-center">
                            @if($product->is_active)
                                <span class="bg-emerald-100 text-emerald-800 text-xs px-3 py-1 rounded-full font-bold">
                                    Activo
                                </span>
                            @else
                                <span class="bg-slate-100 text-slate-500 text-xs px-3 py-1 rounded-full font-bold">
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 align-middle text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('products.show', $product->id) }}"
                                   class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition"
                                   title="Consultar Ficha Técnica">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('products.edit', $product->id) }}"
                                   class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition"
                                   title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('products.destroy', $product->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Está seguro de eliminar el producto \'{{ $product->name }}\'?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition"
                                            title="Eliminar">
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
                        <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-2xl">
                                🔍
                            </div>
                            <p class="text-base font-bold text-slate-700">No se encontraron productos coincidentes</p>
                            <p class="text-xs text-slate-400 mt-1">Intente ajustar los criterios de búsqueda o limpie los filtros.</p>
                            <div class="mt-4">
                                <a href="{{ route('products.index') }}" class="text-xs font-bold text-blue-600 hover:underline">
                                    Limpiar todos los filtros
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
