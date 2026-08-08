@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto px-4 py-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Editar Subcategoría
        </h1>
    </div>

    <div class="bg-white shadow rounded-lg p-6">

        <form method="POST"
              action="{{ route('sub-categories.update', $subCategory) }}">

            @csrf
            @method('PUT')

            <div class="mb-4">

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Categoría
                </label>

                <select name="id_category"
                        required
                        class="w-full border-gray-300 rounded-lg">

                    @foreach($categories as $category)

                        <option value="{{ $category->id_category }}"
                            {{ old('id_category', $subCategory->id_category) == $category->id_category ? 'selected' : '' }}>

                            {{ $category->name }}

                        </option>

                    @endforeach

                </select>

                @error('id_category')
                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="mb-4">

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $subCategory->name) }}"
                       required
                       class="w-full border-gray-300 rounded-lg">

            </div>

            <div class="mb-4">

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Descripción
                </label>

                <textarea name="description"
                          rows="4"
                          class="w-full border-gray-300 rounded-lg">{{ old('description', $subCategory->description) }}</textarea>

            </div>

            <div class="mb-6">

                <label class="flex items-center gap-2">

                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', $subCategory->is_active) ? 'checked' : '' }}>

                    <span>
                        Subcategoría activa
                    </span>

                </label>

            </div>

            <div class="flex justify-end gap-3">

                <a href="{{ route('sub-categories.index') }}"
                   class="px-4 py-2 bg-gray-200 rounded-lg">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                    Actualizar
                </button>

            </div>

        </form>

    </div>

</div>

@endsection