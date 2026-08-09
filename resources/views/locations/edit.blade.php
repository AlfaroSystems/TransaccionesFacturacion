@extends('layouts.app')

@section('title', 'Editar Ubicación')

@section('content')

<div class="max-w-3xl mx-auto">

<div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">

    <!-- Header -->
    <div class="bg-[#005e66] px-8 py-6 text-white">

        <h1 class="text-3xl font-extrabold">
            Editar Ubicación
        </h1>

        <p class="text-white/70 mt-2">
            Modifica los datos de la ubicación física del almacén.
        </p>

    </div>


<form action="{{ route('locations.update',$location->id) }}" method="POST" class="p-8 space-y-5">

@csrf
@method('PUT')


<!-- Almacén -->

<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Almacén / Bodega *
</label>


<select name="warehouse_id"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700"
required>

@foreach($warehouses as $warehouse)

<option value="{{ $warehouse->id }}"
@if($location->warehouse_id == $warehouse->id)
selected
@endif>

{{ $warehouse->name }}

</option>

@endforeach

</select>

</div>



<!-- Código -->

<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Código de Ubicación *
</label>


<input 
type="text"
name="code"
value="{{ $location->code }}"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-semibold text-slate-700"
required>

</div>



<!-- Distribución -->

<div class="grid grid-cols-2 gap-4">


<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Pasillo
</label>

<input 
type="text"
name="pasillo"
value="{{ $location->pasillo }}"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">

</div>



<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Estante (Rack)
</label>

<input 
type="text"
name="rack"
value="{{ $location->rack }}"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">

</div>



<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Nivel
</label>

<input 
type="text"
name="level"
value="{{ $location->level }}"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">

</div>



<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Posición
</label>

<input 
type="text"
name="position"
value="{{ $location->position }}"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">

</div>


</div>



<!-- Capacidad -->

<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Capacidad Máxima *
</label>


<input 
type="number"
name="capacity"
value="{{ $location->capacity }}"
min="0"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3"
required>

</div>



<!-- Notas -->

<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Notas adicionales
</label>


<textarea
name="notes"
rows="3"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">{{ $location->notes }}</textarea>


</div>



<!-- Estado -->

<div>

<label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
Estado
</label>


<select name="is_active"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">


<option value="1" {{ $location->is_active ? 'selected':'' }}>
Activo
</option>


<option value="0" {{ !$location->is_active ? 'selected':'' }}>
Inactivo
</option>


</select>


</div>




<!-- Botones -->

<div class="flex justify-end gap-3 pt-5 border-t border-slate-100">


<a href="{{ route('locations.index') }}"
class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition">

Cancelar

</a>


<button type="submit"
class="px-6 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold shadow-md transition">

Guardar Cambios

</button>


</div>



</form>


</div>

</div>


@endsection