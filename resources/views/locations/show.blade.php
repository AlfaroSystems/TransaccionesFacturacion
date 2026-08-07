@extends('layouts.app')

@section('title', 'Detalle de Ubicación')

@section('content')

<div class="max-w-4xl mx-auto">

<div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">


    <!-- Encabezado -->

    <div class="bg-[#005e66] px-8 py-7 text-white flex justify-between items-center">


        <div>

            <span class="text-xs uppercase tracking-wider text-white/70 font-bold">
                Detalle de Ubicación
            </span>


            <h1 class="text-3xl font-extrabold mt-2">
                {{ $location->code }}
            </h1>

        </div>



        @if($location->is_active)

            <span class="bg-emerald-400 text-white px-4 py-2 rounded-full text-sm font-bold shadow">
                ● Activa
            </span>

        @else

            <span class="bg-rose-400 text-white px-4 py-2 rounded-full text-sm font-bold shadow">
                ● Inactiva
            </span>

        @endif


    </div>




    <div class="p-8">


        <!-- Datos generales -->

        <h2 class="text-xl font-extrabold text-slate-800 mb-5 border-b border-slate-100 pb-3">
            Datos Generales
        </h2>



        <div class="grid md:grid-cols-2 gap-6 mb-10">


            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">

                <label class="text-xs font-bold text-slate-400 uppercase">
                    Almacén / Bodega
                </label>


                <p class="text-xl font-bold text-slate-800 mt-2">
                    {{ $location->warehouse->name ?? 'Bodega '.$location->warehouse_id }}
                </p>

            </div>



            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">

                <label class="text-xs font-bold text-slate-400 uppercase">
                    Capacidad Máxima
                </label>


                <p class="text-xl font-bold text-slate-800 mt-2">
                    {{ number_format($location->capacity) }} items
                </p>

            </div>



        </div>





        <!-- Distribución física -->


        <h2 class="text-xl font-extrabold text-slate-800 mb-5 border-b border-slate-100 pb-3">
            Distribución Física
        </h2>



        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">


            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 text-center">

                <span class="text-xs font-bold text-slate-400 uppercase">
                    Pasillo
                </span>

                <p class="text-2xl font-extrabold text-[#005e66] mt-2">
                    {{ $location->pasillo ?? '-' }}
                </p>

            </div>




            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 text-center">

                <span class="text-xs font-bold text-slate-400 uppercase">
                    Estante
                </span>

                <p class="text-2xl font-extrabold text-[#005e66] mt-2">
                    {{ $location->rack ?? '-' }}
                </p>

            </div>





            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 text-center">

                <span class="text-xs font-bold text-slate-400 uppercase">
                    Nivel
                </span>

                <p class="text-2xl font-extrabold text-[#005e66] mt-2">
                    {{ $location->level ?? '-' }}
                </p>

            </div>





            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 text-center">

                <span class="text-xs font-bold text-slate-400 uppercase">
                    Posición
                </span>

                <p class="text-2xl font-extrabold text-[#005e66] mt-2">
                    {{ $location->position ?? '-' }}
                </p>

            </div>



        </div>





        @if($location->notes)

        <div class="mb-8">

            <h2 class="text-xl font-extrabold text-slate-800 mb-3">
                Notas
            </h2>


            <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 text-slate-700">
                {{ $location->notes }}
            </div>


        </div>

        @endif






        <!-- Auditoria -->


        <div class="border-t border-slate-100 pt-6 grid md:grid-cols-2 gap-4 text-sm">


            <p class="text-slate-400 font-semibold">

                Fecha de registro:

                <span class="text-slate-700">
                    {{ $location->created_at->format('d/m/Y H:i:s') }}
                </span>

            </p>



            <p class="text-slate-400 font-semibold">

                Última actualización:

                <span class="text-slate-700">
                    {{ $location->updated_at->format('d/m/Y H:i:s') }}
                </span>

            </p>



        </div>







        <!-- Botones -->


        <div class="flex justify-end gap-3 mt-8">


            <a href="{{ route('locations.edit',$location->id) }}"
               class="px-6 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold shadow-md transition">

                ✏️ Editar Ubicación

            </a>




            <a href="{{ route('locations.index') }}"
               class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition">

                Regresar

            </a>



        </div>



    </div>


</div>

</div>


@endsection
