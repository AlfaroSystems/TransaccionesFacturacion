@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Crear Sucursal</h1>

    <form action="{{ route('branches.store') }}" method="POST">

        @csrf

        <!-- Seleccionar empresa -->
        <div class="mb-3">
            <label class="form-label">
                Empresa
            </label>

            <select name="company_id" class="form-control">

                <option value="">
                    Seleccione una empresa
                </option>

                @foreach($companies as $company)

                    <option value="{{ $company->id }}">
                        {{ $company->name }}
                    </option>

                @endforeach

            </select>
        </div>


        <!-- Nombre de sucursal -->
        <div class="mb-3">

            <label class="form-label">
                Nombre de la sucursal
            </label>

            <input type="text"
                   name="name"
                   class="form-control">

        </div>


        <!-- Dirección -->
        <div class="mb-3">

            <label class="form-label">
                Dirección
            </label>

            <input type="text"
                   name="address"
                   class="form-control">

        </div>


        <!-- Teléfono -->
        <div class="mb-3">

            <label class="form-label">
                Teléfono
            </label>

            <input type="text"
                   name="phone"
                   class="form-control">

        </div>


        <!-- Correo -->
        <div class="mb-3">

            <label class="form-label">
                Correo electrónico
            </label>

            <input type="email"
                   name="email"
                   class="form-control">

        </div>


        <!-- Estado -->
        <div class="mb-3">

            <label class="form-label">
                Estado
            </label>

            <select name="is_active" class="form-control">

                <option value="1">
                    Activa
                </option>

                <option value="0">
                    Inactiva
                </option>

            </select>

        </div>


        <button type="submit" class="btn btn-success">
            Guardar Sucursal
        </button>


        <a href="{{ route('branches.index') }}"
           class="btn btn-secondary">
            Cancelar
        </a>


    </form>

</div>

@endsection
