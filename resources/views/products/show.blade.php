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
                            Activo para Operaciones
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

            <!-- Código de barras y códigos adicionales -->
            <div class="flex flex-wrap gap-2">
                @if($product->original_code)
                    <div class="bg-white/10 backdrop-blur-md border border-white/10 text-white p-3 rounded-xl shadow-md text-center min-w-[130px]">
                        <div class="font-mono text-[10px] font-bold tracking-wider uppercase text-slate-300">CÓDIGO FÁBRICA</div>
                        <div class="font-mono text-sm font-extrabold tracking-wider mt-0.5">{{ $product->original_code }}</div>
                    </div>
                @endif
                @if($product->internal_code)
                    <div class="bg-white/10 backdrop-blur-md border border-white/10 text-white p-3 rounded-xl shadow-md text-center min-w-[130px]">
                        <div class="font-mono text-[10px] font-bold tracking-wider uppercase text-slate-300">CÓDIGO INTERNO</div>
                        <div class="font-mono text-sm font-extrabold tracking-wider mt-0.5">{{ $product->internal_code }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Identificador UUID Global -->
        <div class="px-6 md:px-8 py-3 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Identificador Global (UUID):</span>
                <span id="uuid-text" class="font-mono text-xs text-slate-700 font-semibold bg-white px-2.5 py-1 rounded border border-slate-200 select-all">
                    {{ $product->uuid }}
                </span>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $product->uuid }}'); if(window.sileo) { window.sileo.success('Identificador UUID copiado al portapapeles.', '¡Copiado!'); }"
                    class="text-xs text-[#005e66] hover:text-[#3cb0a4] font-bold flex items-center gap-1 transition-all cursor-pointer">
                📋 Copiar UUID
            </button>
        </div>

        <!-- CUERPO DE LA FICHA EN BLOQUES ESTRUCTURADOS -->
        <div class="p-6 md:p-8 space-y-8">

            <!-- Galería de Imágenes -->
            <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                <div class="flex items-center gap-2.5 text-slate-800 font-bold text-sm mb-4 pb-2 border-b border-slate-200/60">
                    <span class="text-base">🖼️</span>
                    <h3>Galería de Imágenes</h3>
                </div>
                @if($product->images->count() > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                        @foreach($product->images as $img)
                            <a href="{{ asset('storage/' . $img->path) }}" target="_blank" class="group relative block aspect-square rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-sm hover:shadow-md transition-all">
                                <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-all">
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center text-white text-xs font-bold">
                                    🔍 Ampliar
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-slate-400 text-xs font-semibold">
                        No hay imágenes registradas para este producto.
                    </div>
                @endif
            </div>

            <!-- Grid de Bloques Clave -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Clasificación Comercial -->
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

                <!-- 2. Especificaciones Técnicas y Unidades -->
                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2.5 text-slate-800 font-bold text-sm mb-4 pb-2 border-b border-slate-200/60">
                        <span class="text-base">📐</span>
                        <h3>Especificaciones & Unidades</h3>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Presentación:</dt>
                            <dd class="font-semibold text-slate-800 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->presentation ?: 'N/A' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Talla / Tamaño / Dim.:</dt>
                            <dd class="font-semibold text-slate-800 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->size ?: 'N/A' }} {{ $product->dimensions ? '('.$product->dimensions.')' : '' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500 font-medium">Unidad de Compra / Venta:</dt>
                            <dd class="font-bold text-slate-800 bg-white px-3 py-1 rounded-lg border border-slate-200">
                                {{ $product->purchaseUnit->name ?? 'N/A' }} / {{ $product->saleUnit->name ?? 'N/A' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 3. Descripción / Detalles -->
            @if($product->description)
                <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100/80 shadow-xs">
                    <h3 class="text-xs font-extrabold text-blue-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span>📋</span> Descripción / Detalles
                    </h3>
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </p>
                </div>
            @endif

            <!-- 4. Información de Auditoría -->
            <div class="flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 pt-6 border-t border-slate-100 gap-2 px-1">
                <span>Registrado en el sistema: <strong class="text-slate-600">{{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '-' }}</strong></span>
                <span>Última actualización: <strong class="text-slate-600">{{ $product->updated_at ? $product->updated_at->format('d/m/Y H:i') : '-' }}</strong></span>
            </div>
        </div>
    </div>
</div>
@endsection