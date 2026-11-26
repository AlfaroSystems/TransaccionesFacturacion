@extends('layouts.app')
@section('title', 'Ficha de Producto: ' . $product->name)
@section('content')
<div class="w-full space-y-6 animate-fade-in">
    <x-ui.page-header
        eyebrow="Catálogo"
        module="Ficha Técnica"
        :back="route('products.index')"
        :title="$product->name">
        <x-slot:actions>
            <x-ui.button :href="route('products.index')" variant="secondary" icon="arrow-left">Volver</x-ui.button>
            <x-ui.button :href="route('products.index', ['edit' => $product->id_product])" icon="pencil">Editar Producto</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- TARJETA PRINCIPAL: FICHA TÉCNICA -->
    <div class="ui-surface overflow-hidden">
        
        <!-- Header de la Ficha -->
        <div class="p-6 md:p-8 bg-linear-to-r/srgb from-slate-900 to-navy-sidebar text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
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
        <div class="px-6 md:px-8 py-3 bg-slate-50 border-b border-line flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Identificador Global (UUID):</span>
                <span id="uuid-text" class="font-mono text-xs text-slate-700 font-semibold bg-surface px-2.5 py-1 rounded-sm border border-line select-all">
                    {{ $product->uuid }}
                </span>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $product->uuid }}'); if(window.sileo) { window.sileo.success('Identificador UUID copiado al portapapeles.', '¡Copiado!'); }"
                    class="text-xs text-primary dark:text-accent hover:text-[#3cb0a4] font-bold flex items-center gap-1 transition-all cursor-pointer">
                📋 Copiar UUID
            </button>
        </div>

        <!-- CUERPO DE LA FICHA EN BLOQUES ESTRUCTURADOS -->
        <div class="p-6 md:p-8 space-y-8">

            <!-- Galería de Imágenes -->
            <div class="bg-sunken p-5 rounded-2xl border border-line">
                <div class="flex items-center gap-2.5 text-body font-bold text-sm mb-4 pb-2 border-b border-line">
                    <span class="text-base">🖼️</span>
                    <h3>Galería de Imágenes</h3>
                </div>
                @if($product->images->count() > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                        @foreach($product->images as $img)
                            <div onclick="openGlobalImageModal('{{ asset('storage/' . $img->path) }}', 'Imagen de {{ addslashes($product->name) }}')" class="group relative block aspect-square rounded-2xl overflow-hidden border border-line bg-surface shadow-xs hover:shadow-md transition-all cursor-pointer" title="Ampliar imagen de {{ addslashes($product->name) }}">
                                <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-all">
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center text-white text-xs font-bold">
                                    🔍 Ampliar
                                </div>
                            </div>
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
                <div class="bg-sunken p-5 rounded-2xl border border-line">
                    <div class="flex items-center gap-2.5 text-body font-bold text-sm mb-4 pb-2 border-b border-line">
                        <span class="text-base">🏷️</span>
                        <h3>Clasificación & Categoría</h3>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <dt class="text-muted font-medium">Categoría Principal:</dt>
                            <dd class="font-bold text-slate-800 bg-surface px-3 py-1 rounded-lg border border-line">
                                {{ $product->category->name ?? 'No asignada' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-muted font-medium">Subcategoría:</dt>
                            <dd class="font-semibold text-slate-700 bg-surface px-3 py-1 rounded-lg border border-line">
                                {{ $product->subCategory->name ?? 'No asignada' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- 2. Especificaciones Técnicas y Unidades -->
                <div class="bg-sunken p-5 rounded-2xl border border-line">
                    <div class="flex items-center gap-2.5 text-body font-bold text-sm mb-4 pb-2 border-b border-line">
                        <span class="text-base">📐</span>
                        <h3>Especificaciones & Unidades</h3>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <dt class="text-muted font-medium">Presentación:</dt>
                            <dd class="font-semibold text-slate-800 bg-surface px-3 py-1 rounded-lg border border-line">
                                {{ $product->presentation ?: 'N/A' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-muted font-medium">Talla / Tamaño / Dim.:</dt>
                            <dd class="font-semibold text-slate-800 bg-surface px-3 py-1 rounded-lg border border-line">
                                {{ $product->size ?: 'N/A' }} {{ $product->dimensions ? '('.$product->dimensions.')' : '' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-muted font-medium">Unidad de Compra / Venta:</dt>
                            <dd class="font-bold text-slate-800 bg-surface px-3 py-1 rounded-lg border border-line">
                                {{ $product->purchaseUnit->name ?? 'N/A' }} / {{ $product->saleUnit->name ?? 'N/A' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center gap-3">
                            <dt class="text-muted font-medium" title="Costo de la compra más reciente: con descuento, sin IVA, más gastos que forman parte del costo; o el del retaceo aplicado">Último Costo:</dt>
                            <dd class="font-bold text-slate-800 bg-surface px-3 py-1 rounded-lg border border-line text-right">
                                @if($product->last_cost !== null)
                                    <span class="font-mono">${{ number_format($product->last_cost, 4) }}</span>
                                    @php($origen = $product->lastCostPurchaseDetail)
                                    <span class="block text-[11px] font-semibold text-slate-400">
                                        {{ $origen?->unit?->name ? 'por '.$origen->unit->name.' · ' : '' }}{{ $origen?->purchase?->purchase_code ? $origen->purchase->purchase_code.' · ' : '' }}{{ $product->last_cost_date?->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-semibold">Sin compras completadas</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- 3. Descripción / Detalles -->
            @if($product->description)
                <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100/80">
                    <h3 class="text-xs font-extrabold text-blue-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span>📋</span> Descripción / Detalles
                    </h3>
                    <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </p>
                </div>
            @endif

            <!-- 4. Información de Auditoría -->
            <div class="flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 pt-6 border-t border-line gap-2 px-1">
                <span>Registrado en el sistema: <strong class="text-slate-600">{{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '-' }}</strong></span>
                <span>Última actualización: <strong class="text-slate-600">{{ $product->updated_at ? $product->updated_at->format('d/m/Y H:i') : '-' }}</strong></span>
            </div>
        </div>
    </div>
</div>
@endsection