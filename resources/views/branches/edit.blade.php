@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Editar Sucursal</h1>


    <form action="{{ route('branches.update', $branch) }}" method="POST">

        @csrf
        @method('PUT')


        <!-- Empresa -->
        <div class="mb-3">

            <label class="form-label">
                Empresa
            </label>

            <select name="company_id" class="form-control">

                @foreach($companies as $company)

                    <option value="{{ $company->id }}"
                        {{ $branch->company_id == $company->id ? 'selected' : '' }}>

                        {{ $company->name }}

                    </option>

                @endforeach

            </select>

        </div>


        <!-- Nombre -->
        <div class="mb-3">

            <label class="form-label">
                Nombre de la sucursal
            </label>

            <input type="text"
                   name="name"
                   class="form-control"
                   value="{{ $branch->name }}">

        </div>


        <!-- Dirección -->
        <div class="mb-3">

            <label class="form-label">
                Dirección
            </label>

            <input type="text"
                   name="address"
                   class="form-control"
                   value="{{ $branch->address }}">

        </div>


        <!-- Teléfono -->
        <div class="mb-3">

            <label class="form-label">
                Teléfono
            </label>

            <input type="text"
                   name="phone"
                   class="form-control"
                   value="{{ $branch->phone }}">

        </div>


        <!-- Correo -->
        <div class="mb-3">

            <label class="form-label">
                Correo electrónico
            </label>

            <input type="email"
                   name="email"
                   class="form-control"
                   value="{{ $branch->email }}">

        </div>


        <!-- Estado -->
        <div class="mb-3">

            <label class="form-label">
                Estado
            </label>


            <select name="is_active" class="form-control">


                <option value="1"
                    {{ $branch->is_active == 1 ? 'selected' : '' }}>
                    Activa
                </option>


                <option value="0"
                    {{ $branch->is_active == 0 ? 'selected' : '' }}>
                    Inactiva
                </option>


            </select>

        </div>


        <button type="submit" class="btn btn-primary">
            Actualizar Sucursal
        </button>


        <a href="{{ route('branches.index') }}"
           class="btn btn-secondary">
            Cancelar
        </a>


    </form>


</div>

@endsection