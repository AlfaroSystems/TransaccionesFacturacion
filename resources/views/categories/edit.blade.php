@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <h2>Editar Categoría</h2>

    <form action="{{ route('categories.update', $category) }}" method="POST">

        @csrf
        @method('PUT')

        <div class="mb-3">

            <label class="form-label">
                Nombre
            </label>

            <input 
                type="text"
                name="name"
                class="form-control"
                value="{{ $category->name }}"
                maxlength="100"
                required>

        </div>


        <div class="mb-3">

            <label class="form-label">
                Descripción
            </label>

            <textarea
                name="description"
                class="form-control"
                rows="4">{{ $category->description }}</textarea>

        </div>


        <div class="mb-3">

            <label>

                <input 
                    type="checkbox"
                    name="is_active"
                    {{ $category->is_active ? 'checked' : '' }}>

                Activa

            </label>

        </div>


        <button class="btn btn-success">
            Actualizar
        </button>


        <a href="{{ route('categories.index') }}"
           class="btn btn-secondary">
            Cancelar
        </a>


    </form>

</div>

@endsection