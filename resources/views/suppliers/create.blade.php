@extends('layouts.app')

@section('title', 'Registrar Proveedor')

@section('content')
<div class="animate-fade-in duration-300">

    {{-- Mensajes --}}
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <span class="font-semibold text-sm">{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">✕</button>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">
            <p class="font-bold text-sm mb-2">Revisa los siguientes campos:</p>
            <ul class="list-disc list-inside text-sm font-semibold space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Encabezado --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">
                Registrar Nuevo Proveedor
            </h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">
                Ingresa los datos del proveedor y sus contactos asociados.
            </p>
        </div>

        <a href="{{ route('suppliers.index') }}"
            class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold text-sm transition-all">
            ← Regresar
        </a>
    </header>

    {{-- Formulario --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        <form action="{{ route('suppliers.store') }}" method="POST" class="space-y-8" id="supplier-form">
            @csrf

            {{-- DATOS GENERALES --}}
            <section>
                <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider mb-4">
                    Datos Generales
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                            Nombre del Proveedor *
                        </label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Ej. Distribuidora Andina S.A."
                            class="w-full bg-slate-50 border @error('name') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                        @error('name')
                            <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                            País *
                        </label>
                        <input
                            type="text"
                            name="country"
                            value="{{ old('country') }}"
                            placeholder="Ej. El Salvador"
                            class="w-full bg-slate-50 border @error('country') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                        @error('country')
                            <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- CONTACTO DEL PROVEEDOR --}}
            <section>
                <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider mb-4">
                    Contacto del Proveedor
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                            Correo electrónico *
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="contacto@proveedor.com"
                            class="w-full bg-slate-50 border @error('email') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                        @error('email')
                            <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                            Teléfono
                        </label>
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="Ej. 2222-2222"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                            Sitio Web
                        </label>
                        <input
                            type="url"
                            name="website"
                            value="{{ old('website') }}"
                            placeholder="https://proveedor.com"
                            class="w-full bg-slate-50 border @error('website') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                        @error('website')
                            <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- CONTACTOS ASOCIADOS (FORM REPEATER) --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider">
                        Contactos Asociados
                    </h3>

                    <button
                        type="button"
                        id="add-contact-btn"
                        class="px-4 py-2 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-xs transition-all">
                        + Agregar Contacto
                    </button>
                </div>

                @error('contacts')
                    <p class="text-rose-500 text-xs mb-3 font-semibold">{{ $message }}</p>
                @enderror

                <div id="contacts-container" class="space-y-4">
                    @php
                        $oldContacts = old('contacts', [['full_name' => '', 'phone' => '', 'email' => '']]);
                    @endphp

                    @foreach($oldContacts as $index => $contact)
                        <div class="contact-row bg-slate-50 border border-slate-200 rounded-xl p-4" data-index="{{ $index }}">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                                        Nombre Completo *
                                    </label>
                                    <input
                                        type="text"
                                        name="contacts[{{ $index }}][full_name]"
                                        value="{{ $contact['full_name'] ?? '' }}"
                                        placeholder="Ej. Juan Pérez"
                                        class="w-full bg-white border @error('contacts.'.$index.'.full_name') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                                    @error('contacts.'.$index.'.full_name')
                                        <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                                        Teléfono *
                                    </label>
                                    <input
                                        type="text"
                                        name="contacts[{{ $index }}][phone]"
                                        value="{{ $contact['phone'] ?? '' }}"
                                        placeholder="Ej. 7777-7777"
                                        class="w-full bg-white border @error('contacts.'.$index.'.phone') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                                    @error('contacts.'.$index.'.phone')
                                        <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                                            Correo
                                        </label>
                                        <input
                                            type="email"
                                            name="contacts[{{ $index }}][email]"
                                            value="{{ $contact['email'] ?? '' }}"
                                            placeholder="contacto@correo.com"
                                            class="w-full bg-white border @error('contacts.'.$index.'.email') border-rose-400 @else border-slate-200 @enderror rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                                        @error('contacts.'.$index.'.email')
                                            <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button
                                        type="button"
                                        class="remove-contact-btn self-start mt-6 px-3 py-3 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold transition-all">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Botones --}}
            <div class="flex justify-end gap-3 pt-5 border-t border-slate-100">
                <a href="{{ route('suppliers.index') }}"
                    class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold text-sm transition-all">
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="px-6 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md transition-all">
                    Guardar Proveedor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Template oculto para nuevas filas de contacto --}}
<template id="contact-row-template">
    <div class="contact-row bg-slate-50 border border-slate-200 rounded-xl p-4" data-index="__INDEX__">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    Nombre Completo *
                </label>
                <input
                    type="text"
                    name="contacts[__INDEX__][full_name]"
                    placeholder="Ej. Juan Pérez"
                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                    Teléfono *
                </label>
                <input
                    type="text"
                    name="contacts[__INDEX__][phone]"
                    placeholder="Ej. 7777-7777"
                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
            </div>

            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                        Correo
                    </label>
                    <input
                        type="email"
                        name="contacts[__INDEX__][email]"
                        placeholder="contacto@correo.com"
                        class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:border-[#005e66]">
                </div>

                <button
                    type="button"
                    class="remove-contact-btn self-start mt-6 px-3 py-3 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold transition-all">
                    ✕
                </button>
            </div>
        </div>
    </div>
</template>

{{-- Script del repeater de contactos (JS Vanilla) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('contacts-container');
    const addBtn = document.getElementById('add-contact-btn');
    const template = document.getElementById('contact-row-template');

    let nextIndex = container.querySelectorAll('.contact-row').length;

    function refreshRemoveButtons() {
        const rows = container.querySelectorAll('.contact-row');
        const removeButtons = container.querySelectorAll('.remove-contact-btn');

        removeButtons.forEach(function (btn) {
            btn.disabled = rows.length <= 1;
            btn.classList.toggle('opacity-40', rows.length <= 1);
            btn.classList.toggle('cursor-not-allowed', rows.length <= 1);
        });
    }

    addBtn.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', nextIndex);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();

        container.appendChild(wrapper.firstElementChild);
        nextIndex++;

        refreshRemoveButtons();
    });

    container.addEventListener('click', function (event) {
        const btn = event.target.closest('.remove-contact-btn');
        if (!btn) return;

        const rows = container.querySelectorAll('.contact-row');
        if (rows.length <= 1) return;

        btn.closest('.contact-row').remove();
        refreshRemoveButtons();
    });

    refreshRemoveButtons();
});
</script>
@endsection
