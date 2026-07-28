@extends('layouts.app')

@section('title', 'Bodegas')

@section('content')

<div class="max-w-7xl mx-auto">


    <!-- Encabezado -->

    <div class="flex justify-between items-center mb-8">


        <div>

            <h1 class="text-4xl font-bold text-slate-800">

                Bodegas

            </h1>


            <p class="text-gray-500 mt-2">

                Administración de bodegas registradas.

            </p>


        </div>





        <a href="{{ route('warehouses.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition">


            + Nueva Bodega


        </a>



    </div>







    @if(session('success'))


        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl">


            {{ session('success') }}


        </div>


    @endif







    <!-- Tabla -->


    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">



        <table class="w-full">



            <thead class="bg-gray-100">


                <tr>


                    <th class="px-6 py-4 text-center font-bold text-gray-700">

                        ID

                    </th>


                    <th class="px-6 py-4 text-left font-bold text-gray-700">

                        Sucursal

                    </th>


                    <th class="px-6 py-4 text-left font-bold text-gray-700">

                        Categoría

                    </th>


                    <th class="px-6 py-4 text-left font-bold text-gray-700">

                        Nombre

                    </th>


                    <th class="px-6 py-4 text-left font-bold text-gray-700">

                        Descripción

                    </th>


                    <th class="px-6 py-4 text-center font-bold text-gray-700">

                        Estado

                    </th>


                    <th class="px-6 py-4 text-center font-bold text-gray-700">

                        Acciones

                    </th>


                </tr>


            </thead>







            <tbody>



            @forelse($warehouses as $warehouse)



                <tr class="border-b hover:bg-blue-50 transition">





                    <td class="px-6 py-4 text-center">

                        {{ $warehouse->id }}

                    </td>






                    <td class="px-6 py-4">


                        {{ $warehouse->branch->name ?? 'Sin sucursal' }}


                    </td>







                    <td class="px-6 py-4">


                        {{ $warehouse->warehouseCategory->name ?? 'Sin categoría' }}


                    </td>







                    <td class="px-6 py-4 font-semibold text-slate-700">


                        {{ $warehouse->name }}


                    </td>







                    <td class="px-6 py-4">


                        {{ $warehouse->description ?? 'Sin descripción' }}


                    </td>







                    <td class="px-6 py-4 text-center">


                        @if($warehouse->is_active)


                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-semibold">

                                Activa

                            </span>


                        @else


                            <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full font-semibold">

                                Inactiva

                            </span>


                        @endif


                    </td>







                    <td class="px-6 py-4">


                        <div class="flex justify-center gap-3">





                            <a href="{{ route('warehouses.edit',$warehouse->id) }}"
                               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg shadow">


                                ✏️ Editar


                            </a>







                            <form action="{{ route('warehouses.destroy',$warehouse->id) }}"
                                  method="POST">


                                @csrf

                                @method('DELETE')



                                <button
                                    onclick="return confirm('¿Desea eliminar esta bodega?')"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow">


                                    🗑 Eliminar


                                </button>



                            </form>




                        </div>


                    </td>





                </tr>





            @empty



                <tr>


                    <td colspan="7"
                        class="py-10 text-center text-gray-500 text-lg">


                        No hay bodegas registradas.


                    </td>


                </tr>



            @endforelse





            </tbody>




        </table>



    </div>




</div>


@endsection