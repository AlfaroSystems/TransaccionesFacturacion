@extends('layouts.app')

@section('title', 'Editar Categoría de Bodega')

@section('content')

<div class="max-w-2xl mx-auto">


    <div class="bg-white rounded-2xl shadow-lg p-8">



        <!-- Encabezado -->

        <div class="mb-8">


            <h1 class="text-3xl font-extrabold text-slate-800">

                Editar Categoría de Bodega

            </h1>


            <p class="text-gray-500 mt-2">

                Modifique la información de la categoría.

            </p>


        </div>







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







        <form action="{{ route('warehouse_categories.update',$warehouseCategory->id) }}"
              method="POST">


            @csrf

            @method('PUT')







            <!-- Nombre -->


            <div class="mb-5">


                <label class="block text-sm font-bold text-gray-700 mb-2">


                    Nombre de Categoría


                </label>




                <input
                    type="text"
                    name="name"
                    value="{{ old('name',$warehouseCategory->name) }}"
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
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">{{ old('description',$warehouseCategory->description) }}</textarea>



            </div>







            <!-- Estado -->


            <div class="mb-8">


                <label class="block text-sm font-bold text-gray-700 mb-2">

                    Estado

                </label>



                <label class="flex items-center gap-3">


                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="w-5 h-5 text-blue-600 rounded"

                        {{ $warehouseCategory->is_active ? 'checked' : '' }}>



                    <span>

                        Categoría Activa

                    </span>


                </label>



            </div>








            <!-- Botones -->


            <div class="flex gap-4">



                <button
                    type="submit"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-6 py-3 rounded-xl shadow-md transition">


                    ✏️ Actualizar Categoría


                </button>






                <a href="{{ route('warehouse_categories.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-6 py-3 rounded-xl transition">


                    Cancelar


                </a>




            </div>




        </form>



    </div>


</div>


@endsection