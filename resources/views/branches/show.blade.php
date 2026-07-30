@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Detalle de Sucursal</h1>


    <div class="card">

        <div class="card-body">


            <p>
                <strong>Empresa:</strong>
                {{ $branch->company->name ?? 'Sin empresa' }}
            </p>


            <p>
                <strong>Nombre:</strong>
                {{ $branch->name }}
            </p>


            <p>
                <strong>Dirección:</strong>
                {{ $branch->address }}
            </p>


            <p>
                <strong>Teléfono:</strong>
                {{ $branch->phone }}
            </p>


            <p>
                <strong>Email:</strong>
                {{ $branch->email }}
            </p>


            <p>
                <strong>Estado:</strong>

                @if($branch->is_active)
                    Activa
                @else
                    Inactiva
                @endif

            </p>


            <a href="{{ route('branches.index') }}"
               class="btn btn-secondary">
                Volver
            </a>


            <a href="{{ route('branches.edit', $branch) }}"
               class="btn btn-warning">
                Editar
            </a>


        </div>

    </div>

</div>

@endsection