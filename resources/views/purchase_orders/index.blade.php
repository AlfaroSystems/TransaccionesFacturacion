@extends('layouts.app')
@section('content')
<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Órdenes de Compra
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Emita y gestione las órdenes de compra de la empresa.
                </p>
            </div>
            <button
                type="button"
                onclick="mostrarCrear()"
                class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Orden
            </button>
        </div>
        @if(session('success'))
            <div class="mb-5 p-4 rounded-lg bg-green-100 border border-green-300 text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-5 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-5 p-4 rounded-lg bg-red-100 border border-red-300 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div id="ordersIndex">
            {{-- Tarjetas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $purchase_orders->total() ?? $purchase_orders->count() }}
                            </p>
                        </div>
                        <div class="p-3 bg-indigo-100 rounded-lg">
                            <span class="text-indigo-600 text-xl">📋</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Borradores</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $purchase_orders->where('status', 'draft')->count() }}
                            </p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <span class="text-yellow-600 text-xl">✎</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Emitidas</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $purchase_orders->where('status', 'issued')->count() }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <span class="text-blue-600 text-xl">📤</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Completadas</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ $purchase_orders->where('status', 'completed')->count() }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <span class="text-green-600 text-xl">✓</span>
                        </div>
                    </div>
                </div>

            </div>
            {{-- Tabla --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Listado de Órdenes de Compra
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Código
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Proveedor
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Sucursal
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Fecha
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Total
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-600">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($purchase_orders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-indigo-600">
                                        {{ $order->purchase_order_code }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ optional($order->supplier)->name ?? 'Sin proveedor' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ optional($order->branch)->name ?? 'Sin sucursal' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold">
                                        {{ $order->currency }}
                                        {{ number_format($order->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusClasses = [
                                                'draft' => 'bg-yellow-100 text-yellow-800',
                                                'issued' => 'bg-blue-100 text-blue-800',
                                                'partial_received' => 'bg-orange-100 text-orange-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                            $statusNames = [
                                                'draft' => 'Borrador',
                                                'issued' => 'Emitida',
                                                'partial_received' => 'Recepción parcial',
                                                'completed' => 'Completada',
                                                'cancelled' => 'Cancelada',
                                            ];
                                        @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $statusNames[$order->status] ?? $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            {{-- VER --}}
                                            <a
                                                href="{{ route('purchase_orders.show', $order->id_purchase_order) }}"
                                                class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                                                title="Ver orden">
                                            </a>
                                            {{-- EDITAR SOLO BORRADOR --}}
                                            @if($order->status === 'draft')
                                                <a
                                                    href="{{ route('purchase_orders.edit', $order->id_purchase_order) }}"
                                                    class="px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200"
                                                    title="Editar">
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-4xl mb-3">
                                        </div>
                                        <p class="text-gray-500">
                                            No hay órdenes de compra registradas.
                                        </p>
                                        <button
                                            type="button"
                                            onclick="mostrarCrear()"
                                            class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                            Crear primera orden
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($purchase_orders, 'links'))
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $purchase_orders->links() }}
                    </div>
                @endif
            </div>
        </div>
        <div id="orderCreate" class="hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                {{-- Cabecera --}}
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">
                            Nueva Orden de Compra
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Registre una orden directamente o importe una cotización aprobada.
                        </p>
                    </div>
                    <button
                        type="button"
                        onclick="mostrarListado()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        ← Volver
                    </button>
                </div>
                <form
                    id="purchaseOrderForm"
                    action="{{ route('purchase_orders.store') }}"
                    method="POST"
                    class="p-6">
                    @csrf
                    <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-xl p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-indigo-800">
                                    Importar Cotización
                                </h3>
                                <p class="text-sm text-indigo-600 mt-1">
                                    Seleccione una cotización aprobada para cargar automáticamente
                                    proveedor, productos y precios.
                                </p>
                            </div>
                            <div class="w-full md:w-80">
                                <select
                                    id="quotationSelect"
                                    name="id_purchase_quotation"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">
                                        -- Seleccionar cotización --
                                    </option>
                                    @if(isset($purchase_quotations))
                                        @foreach($purchase_quotations as $quotation)

                                            <option value="{{ $quotation->id_purchase_quotation }}">
                                                {{ $quotation->quotation_code ?? ('Cotización #' . $quotation->id_purchase_quotation) }}
                                            </option>

                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div id="quotationLoading"
                             class="hidden mt-4 text-sm text-indigo-700">
                            Cargando cotización...
                        </div>
                    </div>
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            Datos de la Orden
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            {{-- PROVEEDOR --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Proveedor *
                                </label>
                                <select
                                    name="id_supplier"
                                    id="id_supplier"
                                    required
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">
                                        Seleccione un proveedor
                                    </option>
                                    @foreach($suppliers ?? [] as $supplier)
                                        <option value="{{ $supplier->id }}">
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- SUCURSAL --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Sucursal *
                                </label>
                                <select
                                    name="id_branch"
                                    id="id_branch"
                                    required
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">
                                        Seleccione una sucursal
                                    </option>
                                    @foreach($branches ?? [] as $branch)
                                        <option value="{{ $branch->id }}">
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- BODEGA --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Bodega *
                                </label>
                                <select
                                    name="id_warehouse"
                                    id="id_warehouse"
                                    required
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">
                                        Seleccione una bodega
                                    </option>
                                    @foreach($warehouses ?? [] as $warehouse)
                                        <option value="{{ $warehouse->id }}">
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- FECHA ORDEN --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha de Orden *
                                </label>
                                <input
                                    type="datetime-local"
                                    name="order_date"
                                    value="{{ old('order_date', now()->format('Y-m-d\TH:i')) }}"
                                    required
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            {{-- FECHA ESPERADA --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha Esperada *
                                </label>
                                <input
                                    type="datetime-local"
                                    name="expected_date"
                                    value="{{ old('expected_date') }}"
                                    required
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            {{-- MONEDA --}}
                            <div>
                                   <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Moneda *
                                </label>
                                <select
                                    name="currency"
                                    id="currency"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="USD" selected>
                                        USD - Dólares
                                    </option>
                                </select>
                            </div>
                            {{-- CONDICIONES DE PAGO --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Condiciones de Pago *
                                </label>
                                <input
                                    type="text"
                                    name="payment_terms"
                                    value="{{ old('payment_terms') }}"
                                    required
                                    placeholder="Ejemplo: Crédito 30 días"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Productos
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Agregue los productos que desea comprar.
                                </p>
                            </div>
                            <button
                                type="button"
                                onclick="agregarProducto()"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                + Agregar producto
                            </button>
                        </div>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="w-full text-sm" id="productsTable">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-3 py-3 text-left">
                                            Producto
                                        </th>
                                        <th class="px-3 py-3 text-left">
                                            Cantidad
                                        </th>
                                        <th class="px-3 py-3 text-left">
                                            Unidad
                                        </th>
                                        <th class="px-3 py-3 text-left">
                                            Precio
                                        </th>
                                        <th class="px-3 py-3 text-left">
                                            Descuento
                                        </th>
                                        <th class="px-3 py-3 text-left">
                                            Impuesto %
                                        </th>
                                        <th class="px-3 py-3 text-right">
                                            Total
                                        </th>
                                        <th class="px-3 py-3">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Gastos Adicionales
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Transporte, envío, seguros u otros gastos.
                                </p>
                            </div>
                            <button
                                type="button"
                                onclick="agregarGasto()"
                                class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                                + Agregar gasto
                            </button>
                        </div>
                        <div id="expenseRows" class="space-y-3">
                        </div>
                    </div>
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Notas
                        </label>
                        <textarea
                            name="notes"
                            rows="3"
                            placeholder="Observaciones de la orden..."
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                    </div>
                    <div class="flex justify-end">
                        <div class="w-full md:w-96 bg-gray-50 rounded-xl border border-gray-200 p-5">
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">
                                    Subtotal
                                </span>
                                <span id="subtotalDisplay" class="font-medium">
                                    $0.00
                                </span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">
                                    Descuento
                                </span>
                                <span id="discountDisplay" class="font-medium">
                                    $0.00
                                </span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">
                                    Impuesto
                                </span>
                                <span id="taxDisplay" class="font-medium">
                                    $0.00
                                </span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-600">
                                    Gastos adicionales
                                </span>
                                <span id="expensesDisplay" class="font-medium">
                                    $0.00
                                </span>
                            </div>
                            <div class="border-t border-gray-300 mt-3 pt-3 flex justify-between">
                                <span class="text-lg font-bold text-gray-800">
                                    TOTAL
                                </span>
                                <span id="totalDisplay"
                                      class="text-xl font-bold text-indigo-600">
                                    $0.00
                                </span>
                            </div>
                        </div>
                    </div>
                    {{-- Campos ocultos para totales --}}
                    <input type="hidden" name="subtotal" id="subtotal">
                    <input type="hidden" name="discount" id="discount">
                    <input type="hidden" name="tax" id="tax">
                    <input type="hidden" name="additional_expenses" id="additional_expenses">
                    <input type="hidden" name="total" id="total">
                    {{-- BOTONES --}}
                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-3">
                        <button
                            type="button"
                            onclick="mostrarListado()"
                            class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Guardar Orden
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div id="orderShow" class="hidden">
            @if(isset($purchase_order))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                      {{-- Cabecera --}}
                    <div class="px-6 py-5 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">
                                Orden de Compra
                            </p>
                            <h2 class="text-2xl font-bold text-gray-800">
                                {{ $purchase_order->purchase_order_code }}
                            </h2>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                onclick="window.print()"
                                class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                                🖨 Imprimir
                            </button>
                            <button
                                type="button"
                                onclick="mostrarListado()"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                ← Volver
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        {{-- Datos generales --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Proveedor
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ optional($purchase_order->supplier)->name ?? 'Sin proveedor' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Sucursal
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ optional($purchase_order->branch)->name ?? 'Sin sucursal' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Bodega
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ optional($purchase_order->warehouse)->name ?? 'Sin bodega' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Estado
                                </p>
                                <span class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $statusClasses[$purchase_order->status] ?? 'bg-gray-100 text-gray-700' }}">

                                    {{ $statusNames[$purchase_order->status] ?? $purchase_order->status }}

                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Fecha de Orden
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ \Carbon\Carbon::parse($purchase_order->order_date)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Fecha Esperada
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ \Carbon\Carbon::parse($purchase_order->expected_date)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Moneda
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ $purchase_order->currency }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">
                                    Condiciones de Pago
                                </p>
                                <p class="font-semibold text-gray-800 mt-1">
                                    {{ $purchase_order->payment_terms }}
                                </p>
                            </div>
                        </div>
                        {{-- Productos --}}
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                Productos
                            </h3>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left">
                                                Producto
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Cantidad
                                            </th>
                                            <th class="px-4 py-3 text-left">
                                                Unidad
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Precio
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Descuento
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Impuesto
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($details ?? [] as $detail)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    {{ optional($detail->product)->name ?? 'Producto #' . $detail->id_product }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    {{ number_format($detail->quantity, 4) }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    {{ optional($detail->unit)->name ?? 'Unidad' }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    {{ number_format($detail->unit_price, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    {{ number_format($detail->discount, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    {{ number_format($detail->tax_amount, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold">
                                                    {{ number_format($detail->total, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                                    No hay productos registrados.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- Gastos --}}
                        @if(isset($expenses) && count($expenses))
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                    Gastos Adicionales
                                </h3>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left">
                                                    Tipo
                                                </th>
                                                <th class="px-4 py-3 text-left">
                                                    Descripción
                                                </th>
                                                <th class="px-4 py-3 text-right">
                                                    Monto
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($expenses as $expense)
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        {{ optional($expense->expenseType)->name ?? 'Gasto' }}
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        {{ $expense->description }}
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        {{ $purchase_order->currency }}
                                                        {{ number_format($expense->amount, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                        {{-- Totales --}}
                        <div class="flex justify-end">
                            <div class="w-full md:w-96">
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600">
                                        Subtotal
                                    </span>
                                    <span>
                                        {{ $purchase_order->currency }}
                                        {{ number_format($purchase_order->subtotal, 2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600">
                                        Descuento
                                    </span>
                                    <span>
                                        {{ $purchase_order->currency }}
                                        {{ number_format($purchase_order->discount, 2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600">
                                        Impuesto
                                    </span>
                                    <span>
                                        {{ $purchase_order->currency }}
                                        {{ number_format($purchase_order->tax, 2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600">
                                        Gastos adicionales
                                    </span>
                                    <span>
                                        {{ $purchase_order->currency }}
                                        {{ number_format($purchase_order->additional_expenses, 2) }}
                                    </span>
                                </div>
                                <div class="border-t-2 border-gray-800 mt-3 pt-3 flex justify-between">
                                    <span class="text-xl font-bold">
                                        TOTAL
                                    </span>
                                    <span class="text-xl font-bold text-indigo-600">
                                        {{ $purchase_order->currency }}
                                        {{ number_format($purchase_order->total, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        {{-- Notas --}}
                        @if($purchase_order->notes)
                            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm font-semibold text-gray-700">
                                    Notas
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $purchase_order->notes }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                    <p class="text-gray-500">
                        Seleccione una orden para visualizarla.
                    </p>
                    <button
                        type="button"
                        onclick="mostrarListado()"
                        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">
                        Volver al listado
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
<script>
    function mostrarListado() {
        document.getElementById('ordersIndex').classList.remove('hidden');
        document.getElementById('orderCreate').classList.add('hidden');
        document.getElementById('orderShow').classList.add('hidden');
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    function mostrarCrear() {
        document.getElementById('ordersIndex').classList.add('hidden');
        document.getElementById('orderCreate').classList.remove('hidden');
        document.getElementById('orderShow').classList.add('hidden');
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        if (document.querySelectorAll('#productRows tr').length === 0) {
            agregarProducto();
        }
    }
    function mostrarDetalle() {
        document.getElementById('ordersIndex').classList.add('hidden');
        document.getElementById('orderCreate').classList.add('hidden');
        document.getElementById('orderShow').classList.remove('hidden');
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    let productIndex = 0;
    function agregarProducto(data = {}) {
        const tbody = document.getElementById('productRows');
        const index = productIndex++;
        const row = document.createElement('tr');
        row.className = 'product-row border-b';
        row.innerHTML = `
            <td class="px-3 py-3 min-w-[220px]">
                <select
                    name="details[${index}][id_product]"
                    required
                    class="product-select w-full rounded-lg border-gray-300 text-sm">
                    <option value="">
                        Seleccione producto
                    </option>
                    @foreach($products ?? [] as $product)
                        <option
                            value="{{ $product->id }}"
                            ${String(data.id_product ?? '') === String({{ $product->id }}) ? 'selected' : ''}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="px-3 py-3 min-w-[120px]">
                <input
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    name="details[${index}][quantity]"
                    value="${data.quantity ?? 1}"
                    required
                    oninput="calcularTotales()"
                    class="quantity w-full rounded-lg border-gray-300 text-sm">
            </td>
            <td class="px-3 py-3 min-w-[150px]">
                <select
                    name="details[${index}][id_unit]"
                    required
                    class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">
                        Seleccione
                    </option>
                    @foreach($units ?? [] as $unit)
                        <option
                            value="{{ $unit->id }}"
                            ${String(data.id_unit ?? '') === String({{ $unit->id }}) ? 'selected' : ''}>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="px-3 py-3 min-w-[130px]">
                <input
                    type="number"
                    step="0.0001"
                    min="0"
                    name="details[${index}][unit_price]"
                    value="${data.unit_price ?? 0}"
                    required
                    oninput="calcularTotales()"
                    class="unit-price w-full rounded-lg border-gray-300 text-sm">
            </td>
            <td class="px-3 py-3 min-w-[130px]">
                <input
                    type="number"
                    step="0.0001"
                    min="0"
                    name="details[${index}][discount]"
                    value="${data.discount ?? 0}"
                    oninput="calcularTotales()"
                    class="line-discount w-full rounded-lg border-gray-300 text-sm">
            </td>
            <td class="px-3 py-3 min-w-[120px]">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    name="details[${index}][tax_rate]"
                    value="${data.tax_rate ?? 13}"
                    oninput="calcularTotales()"
                    class="tax-rate w-full rounded-lg border-gray-300 text-sm">
            </td>
            <td class="px-3 py-3 text-right min-w-[120px]">
                <span class="line-total font-semibold">
                    $0.00
                </span>
                <input
                    type="hidden"
                    name="details[${index}][subtotal]"
                    class="line-subtotal"
                    value="0">
                <input
                    type="hidden"
                    name="details[${index}][tax_amount]"
                    class="line-tax"
                    value="0">
                <input
                    type="hidden"
                    name="details[${index}][total]"
                    class="line-total-input"
                    value="0">
            </td>
            <td class="px-3 py-3 text-center">
                <button
                    type="button"
                    onclick="eliminarProducto(this)"
                    class="px-2 py-1 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                    ✕
                </button>
            </td>
        `;
        tbody.appendChild(row);
        calcularTotales();
    }
    function eliminarProducto(button) {
         const row = button.closest('tr');
        row.remove();
        calcularTotales();
    }
    let expenseIndex = 0;
    function agregarGasto(data = {}) {
        const container = document.getElementById('expenseRows');
        const index = expenseIndex++;
        const row = document.createElement('div');
        row.className =
            'expense-row grid grid-cols-1 md:grid-cols-4 gap-3 items-end p-4 bg-gray-50 rounded-lg border border-gray-200';
        row.innerHTML = `
            <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tipo de gasto
                </label>
                <select
                    name="expenses[${index}][id_expense_type]"
                    required
                    class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">
                        Seleccione
                    </option>
                    @foreach($expenseTypes ?? [] as $expenseType)
                        <option
                            value="{{ $expenseType->id_expense_type }}"
                            ${String(data.id_expense_type ?? '') === String({{ $expenseType->id_expense_type }}) ? 'selected' : ''}>
                            {{ $expenseType->name }}
                        </option>
                    @endforeach
                </select>
            </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Descripción
                </label>
                <input
                    type="text"
                    name="expenses[${index}][description]"
                    value="${data.description ?? ''}"
                    required
                    placeholder="Descripción"
                    class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Monto
                </label>
                <input
                    type="number"
                    step="0.0001"
                    min="0"
                    name="expenses[${index}][amount]"
                    value="${data.amount ?? 0}"
                    required
                    oninput="calcularTotales()"
                    class="expense-amount w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <button
                    type="button"
                    onclick="eliminarGasto(this)"
                    class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                    ✕ Eliminar
                </button>
            </div>
        `;
        container.appendChild(row);
        calcularTotales();
    }
    function eliminarGasto(button) {
        const row = button.closest('.expense-row');
        row.remove();
        calcularTotales();
    }
    function calcularTotales() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        document.querySelectorAll('.product-row').forEach(row => {
            const quantity =
                parseFloat(row.querySelector('.quantity')?.value) || 0;
            const unitPrice =
                parseFloat(row.querySelector('.unit-price')?.value) || 0;
            const lineDiscount =
                parseFloat(row.querySelector('.line-discount')?.value) || 0;
            const taxRate =
                parseFloat(row.querySelector('.tax-rate')?.value) || 0;
            const gross = quantity * unitPrice;
            const lineSubtotal = Math.max(
                gross - lineDiscount,
                0
            );
            const lineTax =
                lineSubtotal * (taxRate / 100);
            const lineTotal =
                lineSubtotal + lineTax;
            subtotal += gross;
            discount += lineDiscount;
            tax += lineTax;
            const lineSubtotalInput =
                row.querySelector('.line-subtotal');
            const lineTaxInput =
                row.querySelector('.line-tax');
            const lineTotalInput =
                row.querySelector('.line-total-input');
            const lineTotalDisplay =
                row.querySelector('.line-total');
            if (lineSubtotalInput) {
                lineSubtotalInput.value =
                    lineSubtotal.toFixed(4);
            }
            if (lineTaxInput) {
                lineTaxInput.value =
                    lineTax.toFixed(4);
            }
            if (lineTotalInput) {
                lineTotalInput.value =
                    lineTotal.toFixed(4);
            }
            if (lineTotalDisplay) {
                lineTotalDisplay.textContent =
                    '$' + lineTotal.toFixed(2);
            }
        });
        let additionalExpenses = 0;

        document.querySelectorAll('.expense-amount').forEach(input => {

            additionalExpenses +=
                parseFloat(input.value) || 0;
        });
        const total =
            subtotal -
            discount +
            tax +
            additionalExpenses;
        document.getElementById('subtotal').value =
            subtotal.toFixed(4);
        document.getElementById('discount').value =
            discount.toFixed(4);
        document.getElementById('tax').value =
            tax.toFixed(4);
        document.getElementById('additional_expenses').value =
            additionalExpenses.toFixed(4);
        document.getElementById('total').value =
            total.toFixed(4);
        document.getElementById('subtotalDisplay').textContent =
            '$' + subtotal.toFixed(2);
        document.getElementById('discountDisplay').textContent =
            '$' + discount.toFixed(2);
        document.getElementById('taxDisplay').textContent =
            '$' + tax.toFixed(2);
        document.getElementById('expensesDisplay').textContent =
            '$' + additionalExpenses.toFixed(2);
        document.getElementById('totalDisplay').textContent =
            '$' + total.toFixed(2);
    }
    document.getElementById('quotationSelect')?.addEventListener(
        'change',
        function () {
            const quotationId = this.value;
            if (!quotationId) {
                return;
            }
            const loading =
                document.getElementById('quotationLoading');
            loading.classList.remove('hidden');
            fetch(
                `{{ url('purchase_orders/quotation-data') }}/${quotationId}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            )
            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        'No se pudo cargar la cotización.'
                    );
                }
                return response.json();
            })
            .then(data => {
                /*
                 * PROVEEDOR
                 */
                if (data.id_supplier) {

                    document.getElementById('id_supplier').value =
                        data.id_supplier;

                }
                /*
                 * PRODUCTOS
                 */
                document.getElementById('productRows').innerHTML = '';
                productIndex = 0;
                if (Array.isArray(data.details)) {
                    data.details.forEach(detail => {
                        agregarProducto(detail);
                    });
                }
                /*
                 * Si no trae productos,
                 * dejar una fila vacía.
                 */
                if (
                    document.querySelectorAll('#productRows tr').length === 0
                ) {
                    agregarProducto();

                }
                calcularTotales();
                loading.textContent =
                    '✓ Cotización importada correctamente.';
            })
            .catch(error => {
                console.error(error);
                loading.textContent =
                    'No fue posible importar la cotización.';
            })
            .finally(() => {
                setTimeout(() => {
                    loading.classList.add('hidden');
                    loading.textContent =
                        'Cargando cotización...';
                }, 3000);
            });
        }
    );
    document.addEventListener('DOMContentLoaded', function () {
        calcularTotales();
        @if(isset($purchase_order))
            mostrarDetalle();
        @else
            mostrarListado();
        @endif
    });
</script>
<style>
    @media print {
        body {
            background: white !important;
        }
        nav,
        header,
        aside,
        button,
        .no-print {
            display: none !important;
        }
        #ordersIndex,
        #orderCreate {
            display: none !important;
        }
        #orderShow {
            display: block !important;
        }
        .shadow-sm,
        .shadow,
        .shadow-md,
        .shadow-lg {
            box-shadow: none !important;
        }
        .border {
            border-color: #ddd !important;
        }
    }
</style>
@endsection