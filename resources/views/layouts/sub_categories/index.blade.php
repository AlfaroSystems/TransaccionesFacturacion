@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-6">

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Subcategorías
            </h1>

            <p class="text-gray-500">
                Administración de subcategorías
            </p>
        </div>

        <a href="{{ route('sub-categories.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            + Nueva Subcategoría
        </a>

    </div>


    {{-- Mensaje de éxito --}}
    @if(session('success'))

        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>

    @endif


    {{-- Filtro por categoría --}}
    <div class="bg-white shadow rounded-lg p-4 mb-6">

        <form method="GET"
              action="{{ route('sub-categories.index') }}"
              class="flex gap-4 items-end">

            <div class="flex-1">

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Filtrar por categoría
                </label>

                <select name="id_category"
                        class="w-full border-gray-300 rounded-lg">

                    <option value="">
                        Todas las categorías
                    </option>

                    @foreach($categories as $category)

                        <option value="{{ $category->id_category }}"
                            {{ request('id_category') == $category->id_category ? 'selected' : '' }}>

                            {{ $category->name }}

                        </option>

                    @endforeach

                </select>

            </div>


            <button type="submit"
                    class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg">
                Filtrar
            </button>


            <a href="{{ route('sub-categories.index') }}"
               class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                Limpiar
            </a>

        </form>

    </div>


    {{-- Tabla --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-gray-100">

                <tr>

                    <th class="px-6 py-3 text-left">
                        ID
                    </th>

                    <th class="px-6 py-3 text-left">
                        Categoría
                    </th>

                    <th class="px-6 py-3 text-left">
                        Subcategoría
                    </th>

                    <th class="px-6 py-3 text-left">
                        Descripción
                    </th>

                    <th class="px-6 py-3 text-left">
                        Estado
                    </th>

                    <th class="px-6 py-3 text-left">
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y">

                @forelse($subCategories as $subCategory)

                    <tr>

                        {{-- ID --}}
                        <td class="px-6 py-4">
                            {{ $subCategory->id }}
                        </td>


                        {{-- Categoría --}}
                        <td class="px-6 py-4">

                            {{ $subCategory->category?->name ?? 'Sin categoría' }}

                            {{-- Advertencia si la categoría padre está inactiva --}}
                            @if($subCategory->category && !$subCategory->category->is_active)

                                <div class="text-xs text-red-600 mt-1 category-warning">

                                    ⚠ Esta categoría está inactiva.
                                    Esta subcategoría no estará accesible.

                                </div>

                            @endif

                        </td>


                        {{-- Subcategoría --}}
                        <td class="px-6 py-4 font-medium">
                            {{ $subCategory->name }}
                        </td>


                        {{-- Descripción --}}
                        <td class="px-6 py-4">

                            {{ $subCategory->description ?? 'Sin descripción' }}

                        </td>


                        {{-- Estado --}}
                        <td class="px-6 py-4">

                            @if($subCategory->is_active)

                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                    Activa
                                </span>

                            @else

                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">
                                    Inactiva
                                </span>

                            @endif

                        </td>


                        {{-- Acciones --}}
                        <td class="px-6 py-4">

                            <div class="flex gap-2">

                                <a href="{{ route('sub-categories.edit', $subCategory) }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    Editar
                                </a>


                                <form method="POST"
                                      action="{{ route('sub-categories.destroy', $subCategory) }}"
                                      onsubmit="return confirm('¿Deseas eliminar esta subcategoría?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800">
                                        Eliminar
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td colspan="6"
                            class="px-6 py-8 text-center text-gray-500">

                            No hay subcategorías registradas.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- JavaScript --}}
@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const warnings = document.querySelectorAll('.category-warning');

    warnings.forEach(function (warning) {

        warning.addEventListener('click', function () {

            alert(
                'La categoría padre está inactiva. ' +
                'Sus subcategorías asociadas también quedarán inaccesibles.'
            );

        });

    });

});

</script>

@endpush

@endsection