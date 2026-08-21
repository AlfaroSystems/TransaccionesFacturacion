@extends('layouts.app')

@section('title', 'Unidades de Medida')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- MENSAJE DE ÉXITO --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7" />
                </svg>

                <span class="font-semibold text-sm">
                    {{ session('success') }}
                </span>
            </div>

            <button type="button"
                    onclick="this.parentElement.remove();"
                    class="text-emerald-500 hover:text-emerald-800">

                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>

            </button>
        </div>
    @endif


    {{-- MENSAJE DE ERROR --}}
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>

                <span class="font-semibold text-sm">
                    {{ session('error') }}
                </span>
            </div>

            <button type="button"
                    onclick="this.parentElement.remove();"
                    class="text-rose-500 hover:text-rose-800">

                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M6 18L18 6M6 6l12 12" />
                </svg>

            </button>
        </div>
    @endif


    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">

            <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192-3 1.732-3z" />
                </svg>

                <span class="font-bold text-sm">
                    Se encontraron errores
                </span>
            </div>

            <ul class="list-disc list-inside text-sm text-rose-600 ml-8">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif


    {{-- ENCABEZADO --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 tracking-tight">
                Gestión de Unidades
            </h1>

            <p class="text-slate-400 text-sm font-semibold mt-1">
                Administra las unidades de medida utilizadas en el sistema.
            </p>
        </div>


        @can('units.crear')
            <button type="button"
                    onclick="openModal('create-unit-modal')"
                    class="flex items-center justify-center gap-2 px-5 py-3 bg-navy-sidebar text-white rounded-full font-bold text-sm hover:bg-navy-active shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">

                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2.5"
                          d="M12 4v16m8-8H4" />
                </svg>

                <span>Crear Nueva Unidad</span>

            </button>
        @endcan

    </header>


    {{-- BÚSQUEDA Y FILTROS --}}
    <section class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow mb-8">

        <form action="{{ route('units.index') }}"
              method="GET"
              class="flex flex-col md:flex-row gap-4 items-end">

            {{-- Buscar --}}
            <div class="flex-1 w-full">

                <label for="search"
                       class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Buscar
                </label>

                <div class="relative">

                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nombre o tipo..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 pl-10 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">

                    <div class="absolute left-3.5 top-3.5 text-slate-400">

                        <svg class="w-4 h-4"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>

                    </div>

                </div>

            </div>


            {{-- Tipo --}}
            <div class="w-full md:w-48">

                <label for="type"
                       class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Tipo
                </label>

                <select name="type"
                        id="type"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">

                    <option value="">
                        Todos los Tipos
                    </option>

                    <option value="unidad"
                        {{ request('type') === 'unidad' ? 'selected' : '' }}>
                        Unidad
                    </option>

                    <option value="peso"
                        {{ request('type') === 'peso' ? 'selected' : '' }}>
                        Peso
                    </option>

                    <option value="volumen"
                        {{ request('type') === 'volumen' ? 'selected' : '' }}>
                        Volumen
                    </option>

                    <option value="longitud"
                        {{ request('type') === 'longitud' ? 'selected' : '' }}>
                        Longitud
                    </option>

                </select>

            </div>


            {{-- Estado --}}
            <div class="w-full md:w-48">

                <label for="status"
                       class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                    Estado
                </label>

                <select name="status"
                        id="status"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700">

                    <option value="">
                        Todos los Estados
                    </option>

                    <option value="active"
                        {{ request('status') === 'active' ? 'selected' : '' }}>
                        Activa
                    </option>

                    <option value="inactive"
                        {{ request('status') === 'inactive' ? 'selected' : '' }}>
                        Inactiva
                    </option>

                </select>

            </div>


            {{-- Filtrar --}}
            <div class="w-full md:w-auto">

                <button type="submit"
                        class="w-full px-5 py-2.5 bg-navy-sidebar text-white rounded-xl text-sm font-bold hover:bg-navy-active transition-all shadow-sm">
                    Filtrar
                </button>

            </div>


            {{-- Limpiar --}}
            @if(request()->anyFilled(['search', 'type', 'status']))

                <div class="w-full md:w-auto">

                    <a href="{{ route('units.index') }}"
                       class="block w-full px-5 py-2.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-sm font-bold transition-all text-center">

                        Limpiar

                    </a>

                </div>

            @endif

        </form>

    </section>


    {{-- LISTADO --}}
    <section class="overflow-x-auto">

        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">

            <thead>

                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">

                    <th class="px-6 py-3 pl-10">
                        Unidad
                    </th>

                    <th class="px-6 py-3">
                        Tipo
                    </th>

                    <th class="px-6 py-3">
                        Estado
                    </th>

                    <th class="px-6 py-3">
                        Fecha Registro
                    </th>

                    <th class="px-6 py-3 text-right">
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($units as $unit)

                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200">

                        {{-- UNIDAD --}}
                        <td class="px-6 py-4 bg-white rounded-l-2xl border-l border-y border-slate-100">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm text-navy-sidebar bg-slate-100 uppercase select-none group-hover:bg-[#005e66] group-hover:text-white transition-all">

                                    <svg class="w-5 h-5"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />

                                    </svg>

                                </div>


                                <div>

                                    <div class="font-bold text-slate-800 text-sm group-hover:text-[#005e66] transition-colors">

                                        {{ $unit->name }}

                                    </div>

                                    <div class="text-xs text-slate-400 font-semibold">

                                        ID: {{ $unit->id }}

                                    </div>

                                </div>

                            </div>

                        </td>


                        {{-- TIPO --}}
                        <td class="px-6 py-4 bg-white border-y border-slate-100">

                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/10">

                                {{ ucfirst($unit->type) }}

                            </span>

                        </td>


                        {{-- ESTADO --}}
                        <td class="px-6 py-4 bg-white border-y border-slate-100">

                            @if($unit->is_active)

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/10">

                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>

                                    Activa

                                </span>

                            @else

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10">

                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>

                                    Inactiva

                                </span>

                            @endif

                        </td>


                        {{-- FECHA --}}
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm text-slate-400 font-semibold">

                            {{ $unit->created_at ? $unit->created_at->format('d/m/Y H:i') : 'N/D' }}

                        </td>


                        {{-- ACCIONES --}}
                        <td class="px-6 py-4 bg-white rounded-r-2xl border-r border-y border-slate-100 text-right">

                            <div class="flex items-center justify-end gap-2">

                                {{-- EDITAR --}}
                                @can('units.editar')

                                    <button type="button"
                                            onclick="openEditUnitModal(
                                                '{{ route('units.update', $unit) }}',
                                                {{ json_encode($unit) }}
                                            )"
                                            class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all"
                                            title="Editar Unidad">

                                        <svg class="w-4 h-4"
                                             fill="none"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24">

                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />

                                        </svg>

                                    </button>

                                @endcan


                                {{-- DESACTIVAR --}}
                                @can('units.desactivar')

                                    @if($unit->is_active)

                                        <button type="button"
                                                onclick="confirmDelete('{{ route('units.destroy', $unit) }}', '{{ addslashes($unit->name) }}')"
                                                class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all"
                                                title="Desactivar Unidad">

                                            <svg class="w-4 h-4"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">

                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />

                                            </svg>

                                        </button>

                                    @endif

                                @endcan

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5"
                            class="text-center py-12 bg-white rounded-2xl border border-slate-100 shadow-sm">

                            <div class="flex flex-col items-center justify-center">

                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">

                                    <svg class="w-6 h-6"
                                         fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />

                                    </svg>

                                </div>

                                <h3 class="text-slate-600 font-bold">
                                    No se encontraron unidades
                                </h3>

                                <p class="text-slate-400 text-xs mt-1">
                                    Prueba a ajustar los criterios de búsqueda o filtrado.
                                </p>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </section>


    {{-- PAGINACIÓN --}}
    @if($units->hasPages())

        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 mt-4">

            {{ $units->links() }}

        </div>

    @endif

</div>


{{-- ========================================================= --}}
{{-- MODAL CREAR UNIDAD --}}
{{-- ========================================================= --}}

@can('units.crear')

<div id="create-unit-modal"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
     onclick="if(event.target === this) closeModal('create-unit-modal')">

    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-8 transform scale-95 transition-all">

            {{-- ENCABEZADO --}}
            <div class="flex items-center gap-4 mb-6">

                <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">

                    <svg class="w-6 h-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2.5"
                              d="M12 4v16m8-8H4" />

                    </svg>

                </div>

                <div>

                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">
                        Registrar Nueva Unidad
                    </h2>

                    <p class="text-slate-400 text-sm font-semibold mt-1">
                        Ingresa los datos de la unidad de medida.
                    </p>

                </div>

            </div>


            {{-- FORMULARIO --}}
            <form action="{{ route('units.store') }}"
                  method="POST"
                  class="space-y-5">

                @csrf

                <input type="hidden"
                       name="modal_type"
                       value="create">


                {{-- Nombre --}}
                <div>

                    <label for="unit-name"
                           class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Nombre de la Unidad
                    </label>

                    <input type="text"
                           name="name"
                           id="unit-name"
                           value="{{ old('modal_type') === 'create' ? old('name') : '' }}"
                           placeholder="Ej. Kilogramo"
                           class="w-full bg-slate-50 border @error('name') border-rose-300 @else border-slate-200 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold"
                           required>

                    @error('name')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">
                                {{ $message }}
                            </p>
                        @endif
                    @enderror

                </div>


                {{-- Tipo --}}
                <div>

                    <label for="unit-type"
                           class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Tipo
                    </label>

                    <select name="type"
                            id="unit-type"
                            class="w-full bg-slate-50 border @error('type') border-rose-300 @else border-slate-200 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold"
                            required>

                        <option value="">
                            Seleccionar tipo
                        </option>

                        <option value="unidad"
                            {{ old('modal_type') === 'create' && old('type') === 'unidad' ? 'selected' : '' }}>
                            Unidad
                        </option>

                        <option value="peso"
                            {{ old('modal_type') === 'create' && old('type') === 'peso' ? 'selected' : '' }}>
                            Peso
                        </option>

                        <option value="volumen"
                            {{ old('modal_type') === 'create' && old('type') === 'volumen' ? 'selected' : '' }}>
                            Volumen
                        </option>

                        <option value="longitud"
                            {{ old('modal_type') === 'create' && old('type') === 'longitud' ? 'selected' : '' }}>
                            Longitud
                        </option>

                    </select>

                    @error('type')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">
                                {{ $message }}
                            </p>
                        @endif
                    @enderror

                </div>


                {{-- Estado --}}
                <div class="border-t border-slate-100 pt-5">

                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                        Estado de la Unidad
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">

                        <input type="hidden"
                               name="is_active"
                               value="0">

                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="sr-only peer"
                               {{ old('modal_type') === 'create'
                                    ? (old('is_active', '1') ? 'checked' : '')
                                    : 'checked' }}>

                        <div class="relative w-11 h-6 bg-slate-200 rounded-full
                                    peer-focus:outline-none
                                    peer-checked:bg-emerald-500
                                    after:content-['']
                                    after:absolute
                                    after:top-[2px]
                                    after:start-[2px]
                                    after:bg-white
                                    after:border
                                    after:rounded-full
                                    after:h-5
                                    after:w-5
                                    after:transition-all
                                    peer-checked:after:translate-x-full">
                        </div>

                        <span class="text-sm font-semibold text-slate-600">
                            Unidad activa
                        </span>

                    </label>

                </div>


                {{-- BOTONES --}}
                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100">

                    <button type="button"
                            onclick="closeModal('create-unit-modal')"
                            class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all">

                        Cancelar

                    </button>


                    <button type="submit"
                            class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">

                        <svg class="w-4 h-4"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 13l4 4L19 7" />

                        </svg>

                        Guardar Unidad

                    </button>

                </div>

            </form>

        </div>

</div>

@endcan



{{-- ========================================================= --}}
{{-- MODAL EDITAR UNIDAD --}}
{{-- ========================================================= --}}

@can('units.editar')

<div id="edit-unit-modal"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
     onclick="if(event.target === this) closeModal('edit-unit-modal')">

    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-8 transform scale-95 transition-all">

            {{-- ENCABEZADO --}}
            <div class="flex items-center gap-4 mb-6">

                <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">

                    <svg class="w-6 h-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />

                    </svg>

                </div>

                <div>

                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">
                        Editar Unidad
                    </h2>

                    <p class="text-slate-400 text-sm font-semibold mt-1">
                        Modifica los datos de la unidad de medida.
                    </p>

                </div>

            </div>


            {{-- FORMULARIO --}}
            <form id="edit-unit-form"
                  action=""
                  method="POST"
                  class="space-y-5">

                @csrf

                @method('PUT')

                <input type="hidden"
                       name="modal_type"
                       value="edit">

                <input type="hidden"
                       name="id"
                       id="edit-unit-id"
                       value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">


                {{-- Nombre --}}
                <div>

                    <label for="edit-unit-name"
                           class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Nombre de la Unidad
                    </label>

                    <input type="text"
                           name="name"
                           id="edit-unit-name"
                           value="{{ old('modal_type') === 'edit' ? old('name') : '' }}"
                           placeholder="Ej. Kilogramo"
                           class="w-full bg-slate-50 border @error('name') border-rose-300 @else border-slate-200 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold"
                           required>

                    @error('name')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">
                                {{ $message }}
                            </p>
                        @endif
                    @enderror

                </div>


                {{-- Tipo --}}
                <div>

                    <label for="edit-unit-type"
                           class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                        Tipo
                    </label>

                    <select name="type"
                            id="edit-unit-type"
                            class="w-full bg-slate-50 border @error('type') border-rose-300 @else border-slate-200 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold"
                            required>

                        <option value="">
                            Seleccionar tipo
                        </option>

                        <option value="unidad">
                            Unidad
                        </option>

                        <option value="peso">
                            Peso
                        </option>

                        <option value="volumen">
                            Volumen
                        </option>

                        <option value="longitud">
                            Longitud
                        </option>

                    </select>

                    @error('type')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">
                                {{ $message }}
                            </p>
                        @endif
                    @enderror

                </div>


                {{-- Estado --}}
                <div class="border-t border-slate-100 pt-5">

                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                        Estado de la Unidad
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">

                        <input type="hidden"
                               name="is_active"
                               value="0">

                        <input type="checkbox"
                               name="is_active"
                               id="edit-unit-active"
                               value="1"
                               class="sr-only peer">

                        <div class="relative w-11 h-6 bg-slate-200 rounded-full
                                    peer-focus:outline-none
                                    peer-checked:bg-emerald-500
                                    after:content-['']
                                    after:absolute
                                    after:top-[2px]
                                    after:start-[2px]
                                    after:bg-white
                                    after:border
                                    after:rounded-full
                                    after:h-5
                                    after:w-5
                                    after:transition-all
                                    peer-checked:after:translate-x-full">
                        </div>

                        <span class="text-sm font-semibold text-slate-600">
                            Unidad activa
                        </span>

                    </label>

                </div>


                {{-- BOTONES --}}
                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100">

                    <button type="button"
                            onclick="closeModal('edit-unit-modal')"
                            class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all">

                        Cancelar

                    </button>


                    <button type="submit"
                            class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">

                        <svg class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>

                        Guardar Cambios

                    </button>

                </div>

            </form>

        </div>

</div>

@endcan



{{-- ========================================================= --}}
{{-- JAVASCRIPT DE UNIDADES --}}
{{-- ========================================================= --}}

<script>

    function openEditUnitModal(route, unit) {

        const form = document.getElementById('edit-unit-form');

        const id = document.getElementById('edit-unit-id');

        const name = document.getElementById('edit-unit-name');

        const type = document.getElementById('edit-unit-type');

        const active = document.getElementById('edit-unit-active');


        if (!form || !id || !name || !type || !active) {
            console.error('No se encontraron los elementos del modal de unidades.');
            return;
        }


        form.action = route;

        id.value = unit.id ?? '';

        name.value = unit.name ?? '';

        type.value = unit.type ?? '';

        active.checked = Boolean(unit.is_active);


        openModal('edit-unit-modal');
    }


    {{-- Si hubo error de validación, abrir nuevamente el modal correspondiente --}}
    @if($errors->any())

        window.addEventListener('DOMContentLoaded', function () {

            @if(old('modal_type') === 'edit')

                const editRoute = "{{ route('units.update', old('id', 0)) }}";

                const oldUnit = {
                    id: "{{ old('id') }}",
                    name: @json(old('name')),
                    type: @json(old('type')),
                    is_active: "{{ old('is_active', '0') }}" == "1"
                };

                openEditUnitModal(editRoute, oldUnit);

            @else

                openModal('create-unit-modal');

            @endif

        });

    @endif

</script>

@endsection