@extends('layouts.app')
@section('title', 'Ficha de Producto: ' . $product->name)
@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado de Navegación y Acciones -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('products.index') }}" class="hover:text-blue-600 transition">Catálogo</a>
                <span>/</span>
                <span class="text-slate-700">Ficha Técnica</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight flex items-center gap-3">
                <span>{{ $product->name }}</span>
            </h1>
        </div>
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <a href="{{ route('products.index') }}"
                class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-semibold px-4 py-2.5 rounded-xl transition text-sm flex items-center gap-2 shadow-sm">
                ← Volver
            </a>
            <a href="{{ route('products.index', ['edit' => $product->id]) }}"
                class="bg-[#005e66] hover:bg-[#3cb0a4] text-white font-semibold px-5 py-2.5 rounded-xl transition text-sm flex items-center gap-2 shadow-lg">
                ✏️ Editar Producto
            </a>
        </div>
    </div>

    <!-- TARJETA PRINCIPAL: FICHA TÉCNICA -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        
        <!-- Header de la Ficha -->
        <div class="p-6 md:p-8 bg-gradient-to-r from-slate-900 to-navy-sidebar text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="font-mono bg-blue-500/20 border border-blue-400/30 text-blue-200 px-3 py-1 rounded-lg text-sm font-bold tracking-wider">
                        SKU: {{ $product->sku }}
                    </span>
                    @if($product->is_active)
                        <span class="bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs px-3 py-1 rounded-full font-bold inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Activo para Dispensación
                        </span>
                    @else
                        <span class="bg-rose-500/20 border border-rose-400/30 text-rose-300 text-xs px-3 py-1 rounded-full font-bold inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            Inactivo
                        </span>
                    @endif
                </div>
                <h2 class="text-2xl md:text-3xl font-extrabold text-white">{{ $product->name }}</h2>
            </div>

            <!-- Código de barras simulado -->
            @if($product->barcode)
                <div class="bg-white text-slate-900 p-3 rounded-xl shadow-md text-center min-w-[170px]">
                    <div class="font-mono text-xs font-bold tracking-[0.3em] uppercase mb-1">BARCODE</div>
                    <div class="font-mono text-base font-extrabold tracking-wider">{{ $product->barcode }}</div>
                </div>
            @endif
        </div>

        <!-- Identificador UUID Global -->
        <div class="px-6 md:px-8 py-3 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Identificador Global (UUID):</span>
                <span id="uuid-text" class="font-mono text-xs text-slate-700 font-semibold bg-white px-2.5 py-1 rounded border border-slate-200 select-all">
                    {{ $product->uuid }}
                </span>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $product->uuid }}'); alert('UUID copiado al portapapeles');"
                    class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                📋 Copiar UUID
            </button>
        </div>

        <!-- CUERPO DE LA FICHA EN BLOQUES ESTRUCTURADOS -->
        <div class="p-6 md:p-8 space-y-8">
            <!-- Grid de 4 Bloques Clave -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Clasificación Farmacéutica / Comercial -->
                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2.5 text-slate-800 font-bold text-sm mb-4 pb-2 border-b border-slate-200/60">
                        <span class="text-base">🏷️</span>
                        <h3>Clasificación & Categoría</h3>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Categoría Principal:</dt>
                            <dd class="font-bold text-slate-800 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->category->name ?? 'No asignada' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Subcategoría:</dt>
                            <dd class="font-semibold text-slate-700 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->subCategory->name ?? 'No asignada' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- 2. Unidades de Medida -->
                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2.5 text-slate-800 font-bold text-sm mb-4 pb-2 border-b border-slate-200/60">
                        <span class="text-base">📦</span>
                        <h3>Unidades de Medida</h3>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Unidad de Compra (Ingreso):</dt>
                            <dd class="font-bold text-slate-800 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->purchaseUnit->name ?? 'No asignada' }}
                                @if(isset($product->purchaseUnit->abbreviation))
                                    <span class="text-xs text-slate-400 font-normal">({{ $product->purchaseUnit->abbreviation }})</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Unidad de Venta (Dispensación):</dt>
                            <dd class="font-bold text-slate-800 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->saleUnit->name ?? 'No asignada' }}
                                @if(isset($product->saleUnit->abbreviation))
                                    <span class="text-xs text-slate-400 font-normal">({{ $product->saleUnit->abbreviation }})</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- 3. Finanzas & Precios -->
                @php
                    $cost = (float) $product->purchase_price;
                    $price = (float) $product->sale_price;
                    $margin = $price > 0 && $cost > 0 ? (($price - $cost) / $price) * 100 : 0;
                    $unitProfit = $price - $cost;
                @endphp
                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2.5 text-slate-800 font-bold text-sm mb-4 pb-2 border-b border-slate-200/60">
                        <span class="text-base">💵</span>
                        <h3>Estructura de Precios</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
                            <span class="text-xs text-slate-400 font-medium block">Precio de Compra</span>
                            <span class="text-lg font-extrabold text-slate-700">${{ number_format($cost, 2) }}</span>
                        </div>
                        <div class="bg-white p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/20">
                            <span class="text-xs text-emerald-600 font-bold block">Precio de Venta</span>
                            <span class="text-xl font-extrabold text-emerald-600">${{ number_format($price, 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-between items-center text-xs text-slate-500 pt-2 border-t border-slate-200/50">
                        <span>Margen comercial estimado:</span>
                        <span class="font-bold text-slate-700">{{ number_format($margin, 1) }}% (Ganancia: ${{ number_format($unitProfit, 2) }}/ud)</span>
                    </div>
                </div>

                <!-- 4. Control de Inventario & Almacén -->
                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2.5 text-slate-800 font-bold text-sm mb-4 pb-2 border-b border-slate-200/60">
                        <span class="text-base">📊</span>
                        <h3>Existencias en Almacén</h3>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="text-xs text-slate-400 block">Stock Actual</span>
                            <span class="text-2xl font-black {{ $product->is_low_stock ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $product->stock }}
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">{{ $product->saleUnit->abbreviation ?? 'uds' }}</span>
                        </div>

                        <div class="text-right">
                            <span class="text-xs text-slate-400 block">Stock Mínimo (Alerta)</span>
                            <span class="text-lg font-bold text-slate-600">{{ $product->min_stock }}</span>
                            <span class="text-xs text-slate-400 font-semibold">{{ $product->saleUnit->abbreviation ?? 'uds' }}</span>
                        </div>
                    </div>

                    <!-- Barra de nivel de existencias -->
                    @php
                        $stockPercent = $product->min_stock > 0 ? min(100, round(($product->stock / ($product->min_stock * 2)) * 100)) : 100;
                    @endphp
                    <div class="w-full bg-slate-200 rounded-full h-2 mt-3 overflow-hidden">
                        <div class="h-2 rounded-full {{ $product->is_low_stock ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $stockPercent }}%"></div>
                    </div>
                    <div class="mt-2 text-right">
                        @if($product->is_low_stock)
                            <span class="text-xs font-bold text-rose-600">⚠️ Requiere reabastecimiento</span>
                        @else
                            <span class="text-xs font-bold text-emerald-600">✓ Nivel de existencias saludable</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 5. Descripción / Indicaciones Farmacéuticas -->
            @if($product->description)
                <div class="bg-blue-50/40 p-6 rounded-2xl border border-blue-100">
                    <h3 class="text-xs font-extrabold text-blue-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span>📋</span> Descripción / Indicaciones Terapéuticas
                    </h3>
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </p>
                </div>
            @endif

            <!-- 6. Información de Auditoría -->
            <div class="flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 pt-6 border-t border-slate-100 gap-2">
                <span>Registrado en el sistema: <strong class="text-slate-600">{{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '-' }}</strong></span>
                <span>Última actualización: <strong class="text-slate-600">{{ $product->updated_at ? $product->updated_at->format('d/m/Y H:i') : '-' }}</strong></span>
            </div>
        </div>
    </div>
</div>
@endsection