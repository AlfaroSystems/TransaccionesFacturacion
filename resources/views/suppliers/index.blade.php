@extends('layouts.app')

@section('title', 'Gestión de Proveedores')

@section('content')
<div class="animate-fade-in duration-300">
    <!-- Mensajes Flash -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="font-semibold text-sm">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 font-bold">✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span class="font-semibold text-sm">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 font-bold">✕</button>
        </div>
    @endif

    <!-- Encabezado -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Gestión de Proveedores</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Administra los proveedores registrados y sus contactos comerciales asociados.</p>
        </div>

        @can('suppliers.crear')
        <button type="button" onclick="openModal('create-supplier-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
            <span>Nuevo Proveedor</span>
        </button>
        @endcan
    </header>

    <!-- Buscador -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre del proveedor, país o correo..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold rounded-xl text-sm transition-all">Buscar</button>
                @if(request('search'))
                    <a href="{{ route('suppliers.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-sm transition-all">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Listado de Proveedores -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Proveedor</th>
                    <th class="py-3 px-6">País</th>
                    <th class="py-3 px-6">Correo Electrónico</th>
                    <th class="py-3 px-6">Teléfono</th>
                    <th class="py-3 px-6 text-center">Contactos</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm font-extrabold text-slate-800">
                            {{ $supplier->name }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600 font-semibold">
                            {{ $supplier->country }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600 font-mono">
                            {{ $supplier->email }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">
                            {{ $supplier->phone ?? '-' }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-teal-50 text-[#005e66] border border-teal-100 text-xs font-bold">
                                {{ $supplier->contacts->count() }} contactos
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('suppliers.ver')
                                <a href="{{ route('suppliers.show', $supplier) }}" class="p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 font-semibold text-xs transition-all" title="Ver Detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                @endcan

                                @can('suppliers.editar')
                                <button type="button" onclick="openModal('edit-supplier-modal-{{ $supplier->id_supplier ?? $supplier->id }}')" class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 font-semibold text-xs transition-all" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                @endcan

                                @can('suppliers.eliminar')
                                <button type="button" onclick="confirmDelete('{{ route('suppliers.destroy', $supplier) }}', 'Proveedor {{ $supplier->name }}')" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>

                    <!-- Modal de Edición de Proveedor -->
                    <div id="edit-supplier-modal-{{ $supplier->id_supplier ?? $supplier->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                        <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between border-b pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-800">Editar Proveedor</h3>
                                <button onclick="closeModal('edit-supplier-modal-{{ $supplier->id_supplier ?? $supplier->id }}')" class="text-slate-400 hover:text-slate-600">✕</button>
                            </div>
                            <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre del Proveedor *</label>
                                        <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">País *</label>
                                        <input type="text" name="country" value="{{ old('country', $supplier->country) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Correo Electrónico *</label>
                                        <input type="email" name="email" value="{{ old('email', $supplier->email) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Teléfono</label>
                                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sitio Web</label>
                                        <input type="url" name="website" value="{{ old('website', $supplier->website) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onclick="closeModal('edit-supplier-modal-{{ $supplier->id_supplier ?? $supplier->id }}')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#005e66] text-white font-bold text-sm">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">No se encontraron proveedores registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <!-- Paginación -->
    @if($suppliers->hasPages())
        <div class="mt-6">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>

<!-- Modal de Creación de Proveedor con Contactos Dinámicos -->
<div id="create-supplier-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 max-w-3xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800">Registrar Nuevo Proveedor</h3>
                <p class="text-xs font-semibold text-slate-400">Completa los datos generales y agrega sus contactos iniciales.</p>
            </div>
            <button onclick="closeModal('create-supplier-modal')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <form action="{{ route('suppliers.store') }}" method="POST" class="space-y-6">
            @csrf
            <!-- Datos Generales -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Datos Generales</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre del Proveedor *</label>
                        <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Distribuidora Andina S.A.">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">País *</label>
                        <input type="text" name="country" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. El Salvador">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="contacto@proveedor.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Teléfono</label>
                        <input type="text" name="phone" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. 2222-2222">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sitio Web</label>
                        <input type="url" name="website" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="https://proveedor.com">
                    </div>
                </div>
            </div>

            <!-- Contactos Asociados -->
            <div class="space-y-4 pt-4 border-t">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Contactos del Proveedor</h4>
                    <button type="button" onclick="addModalContactRow()" class="px-3 py-1.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-lg text-xs font-bold transition-all">+ Agregar Contacto</button>
                </div>

                <div id="modal-contacts-container" class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 relative group">
                        <div>
                            <input type="text" name="contacts[0][full_name]" required placeholder="Nombre completo *" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                        </div>
                        <div>
                            <input type="text" name="contacts[0][phone]" required placeholder="Teléfono *" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                        </div>
                        <div>
                            <input type="email" name="contacts[0][email]" placeholder="Correo electrónico" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('create-supplier-modal')" class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] text-white font-bold text-sm">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<script>
    let modalContactIndex = 1;
    function addModalContactRow() {
        const container = document.getElementById('modal-contacts-container');
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 relative group';
        row.innerHTML = `
            <div>
                <input type="text" name="contacts[${modalContactIndex}][full_name]" required placeholder="Nombre completo *" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
            </div>
            <div>
                <input type="text" name="contacts[${modalContactIndex}][phone]" required placeholder="Teléfono *" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
            </div>
            <div class="flex gap-2">
                <input type="email" name="contacts[${modalContactIndex}][email]" placeholder="Correo electrónico" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                <button type="button" onclick="this.closest('.group').remove()" class="text-rose-500 hover:text-rose-700 px-2 font-bold text-sm">✕</button>
            </div>
        `;
        container.appendChild(row);
        modalContactIndex++;
    }
</script>
@endsection