@extends('layouts.app')

@section('title', 'Registrar Bodega')

@section('content')

<div class="max-w-2xl mx-auto">


    <div class="bg-white rounded-2xl shadow-lg p-8">


        <!-- Encabezado -->

        <div class="mb-8">

            <h1 class="text-3xl font-extrabold text-navy-800">

                Registrar Bodega

            </h1>


            <p class="text-gray-500 mt-2">

                Complete la información de la nueva bodega.

            </p>


        </div>





        <!-- Errores -->

        @if ($errors->any())

            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">


                <strong>
                    Se encontraron errores:
                </strong>


                <ul class="list-disc ml-5 mt-2">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>


            </div>

        @endif






        <form action="{{ route('warehouses.store') }}"
              method="POST">


            @csrf






            <!-- Sucursal -->

            <div class="mb-5">


                <label class="block text-sm font-bold text-gray-700 mb-2">

                    Sucursal

                </label>




                <select
                    name="branch_id"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>


                    <option value="">
                        Seleccione una sucursal
                    </option>



                    @foreach($branches as $branch)


                        <option value="{{ $branch->id }}">

                            {{ $branch->name }}

                        </option>


                    @endforeach



                </select>



            </div>








            <!-- Categoría -->

            <div class="mb-5">


                <label class="block text-sm font-bold text-gray-700 mb-2">

                    Categoría de Bodega

                </label>





                <select
                    name="warehouse_category_id"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>



                    <option value="">

                        Seleccione categoría

                    </option>




                    @foreach($categories as $category)



                        <option value="{{ $category->id }}">


                            {{ $category->name }}


                        </option>



                    @endforeach




                </select>



            </div>








            <!-- Nombre -->


            <div class="mb-5">


                <label class="block text-sm font-bold text-gray-700 mb-2">

                    Nombre de Bodega

                </label>




                <input
                    type="text"
                    name="name"
                    placeholder="Ejemplo: Bodega Principal"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>



            </div>








            <!-- Descripción -->


            <div class="mb-5">


                <label class="block text-sm font-bold text-gray-700 mb-2">

                    Descripción

                </label>




                <textarea
                    name="description"
                    rows="4"
                    placeholder="Descripción de la bodega"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>



            </div>








            <!-- Estado -->


            <div class="mb-8">


                <label class="flex items-center gap-3">


                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        checked
                        class="w-5 h-5 text-blue-600 rounded">



                    <span class="font-bold text-gray-700">

                        Bodega Activa

                    </span>



                </label>



            </div>








            <!-- Botones -->


            <div class="flex gap-4">


                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl shadow-md transition">


                    Guardar Bodega


                </button>





                <a href="{{ route('warehouses.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-6 py-3 rounded-xl transition">


                    Cancelar


                </a>




            </div>






        </form>



    </div>


</div>


@endsection