@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Gestión de Sucursales</h1>

    <a href="{{ route('branches.create') }}" class="btn btn-primary mb-3">
        Nueva Sucursal
    </a>


    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif


    <table class="table table-bordered">

        <thead>
            <tr>
                <th>Empresa</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>


        <tbody>

            @foreach($branches as $branch)

            <tr>

                <td>
                    {{ $branch->company->name ?? 'Sin empresa' }}
                </td>

                <td>
                    {{ $branch->name }}
                </td>

                <td>
                    {{ $branch->address }}
                </td>

                <td>
                    {{ $branch->phone }}
                </td>

                <td>
                    {{ $branch->email }}
                </td>

                <td>

                    @if($branch->is_active)
                        Activa
                    @else
                        Inactiva
                    @endif

                </td>


                <td>

                    <a href="{{ route('branches.edit', $branch) }}"
                       class="btn btn-warning btn-sm">
                        Editar
                    </a>


                    <form action="{{ route('branches.destroy', $branch) }}"
                          method="POST"
                          style="display:inline">

                        @csrf
                        @method('DELETE')

                        <button class="btn btn-danger btn-sm">
                            Eliminar
                        </button>

                    </form>

                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

</div>

@endsection