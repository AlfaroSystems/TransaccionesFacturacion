@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <h2>Crear Categoría</h2>

    <form action="{{ route('categories.store') }}" method="POST">

        @csrf

        <div class="mb-3">
            <label class="form-label">
                Nombre
            </label>

            <input 
                type="text"
                name="name"
                class="form-control"
                required
                maxlength="100">
        </div>


        <div class="mb-3">

            <label class="form-label">
                Descripción
            </label>

            <textarea
                name="description"
                class="form-control"
                rows="4"></textarea>

        </div>


        <div class="mb-3">

            <label>
                <input 
                    type="checkbox"
                    name="is_active"
                    checked>

                Activa
            </label>

        </div>


        <button class="btn btn-primary">
            Guardar
        </button>


        <a href="{{ route('categories.index') }}"
           class="btn btn-secondary">
            Cancelar
        </a>

    </form>

</div>

@endsection