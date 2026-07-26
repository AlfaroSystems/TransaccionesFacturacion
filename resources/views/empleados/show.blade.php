@extends('layouts.app')

@section('title','Detalle del Empleado')

@section('content')

<div class="container">

    <div class="card shadow">

        <div class="card-header bg-info text-white">

            <h4>Detalle del Empleado</h4>

        </div>

        <div class="card-body">

            <table class="table table-bordered">

                <tr>

                    <th width="250">

                        Nombre Completo

                    </th>

                    <td>

                        {{ $empleado->nombre_completo }}

                    </td>

                </tr>

                <tr>

                    <th>

                        Correo

                    </th>

                    <td>

                        {{ $empleado->correo }}

                    </td>

                </tr>

                <tr>

                    <th>

                        Teléfono

                    </th>

                    <td>

                        {{ $empleado->telefono }}

                    </td>

                </tr>

                <tr>

                    <th>

                        DUI

                    </th>

                    <td>

                        {{ $empleado->dui }}

                    </td>

                </tr>

            </table>

            <a
                href="{{ route('empleados.index') }}"
                class="btn btn-primary">

                Regresar

            </a>

        </div>

    </div>

</div>

@endsection