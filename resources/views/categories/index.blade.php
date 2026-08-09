@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Categorías</h2>

        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            Nueva Categoría
        </a>
    </div>


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <!-- Buscador -->
    <form method="GET" action="{{ route('categories.index') }}" class="mb-3">
        <div class="input-group">
            <input 
                type="text" 
                name="search" 
                class="form-control"
                placeholder="Buscar categoría..."
                value="{{ request('search') }}"
            >

            <button class="btn btn-secondary">
                Buscar
            </button>
        </div>
    </form>


    <table class="table table-bordered table-striped">

        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>


        <tbody>

        @forelse($categories as $category)

            <tr>

                <td>
                    {{ $category->id_category ?? $category->id }}
                </td>

                <td>
                    {{ $category->name }}
                </td>

                <td>
                    {{ $category->description }}
                </td>


                <td>

                    @if($category->is_active)
                        <span class="badge bg-success">
                            Activa
                        </span>
                    @else
                        <span class="badge bg-danger">
                            Inactiva
                        </span>
                    @endif

                </td>


                <td>

                    <a href="{{ route('categories.edit',$category) }}"
                       class="btn btn-warning btn-sm">
                        Editar
                    </a>


                    <form action="{{ route('categories.toggle',$category) }}"
                          method="POST"
                          class="d-inline">

                        @csrf
                        @method('PATCH')

                        <button class="btn btn-info btn-sm">
                            Cambiar estado
                        </button>

                    </form>


                    <form action="{{ route('categories.destroy',$category) }}"
                          method="POST"
                          class="d-inline">

                        @csrf
                        @method('DELETE')

                        <button class="btn btn-danger btn-sm"
                                onclick="return confirm('¿Eliminar categoría?')">
                            Eliminar
                        </button>

                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="5" class="text-center">
                    No hay categorías registradas
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

</div>

@endsection