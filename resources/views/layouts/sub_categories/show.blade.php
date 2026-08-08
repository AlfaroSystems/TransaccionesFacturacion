@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto px-4 py-6">

    <div class="bg-white shadow rounded-lg p-6">

        <h1 class="text-2xl font-bold mb-6">
            Detalle de Subcategoría
        </h1>

        <div class="space-y-4">

            <div>
                <strong>ID:</strong>
                {{ $subCategory->id }}
            </div>

            <div>
                <strong>Categoría:</strong>
                {{ $subCategory->category?->name ?? 'Sin categoría' }}
            </div>

            <div>
                <strong>Nombre:</strong>
                {{ $subCategory->name }}
            </div>

            <div>
                <strong>Descripción:</strong>
                {{ $subCategory->description ?? 'Sin descripción' }}
            </div>

            <div>
                <strong>Estado:</strong>

                @if($subCategory->is_active)
                    Activa
                @else
                    Inactiva
                @endif

            </div>

        </div>

        <div class="mt-6">

            <a href="{{ route('sub-categories.index') }}"
               class="px-4 py-2 bg-gray-200 rounded-lg">
                Volver
            </a>

        </div>

    </div>

</div>

@endsection