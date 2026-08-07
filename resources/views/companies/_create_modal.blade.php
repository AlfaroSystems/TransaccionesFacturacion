<!-- MODAL DE REGISTRO DE EMPRESA -->
<div id="create-company-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-company-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Nueva Empresa</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Ingresa todos los datos requeridos por el sistema fiscal y de catastro.</p>
            </div>
        </div>

        <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">

            <!-- Fila 1: Datos Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nombre Razón Social -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Razón Social <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Corporación ABC S.A. de C.V." class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Nombre Comercial -->
                <div>
                    <label for="commercial_name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Comercial</label>
                    <input type="text" name="commercial_name" id="commercial_name" value="{{ old('modal_type') === 'create' ? old('commercial_name') : '' }}" placeholder="Ej. Tiendas ABC" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 2: Documentos Fiscales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIT -->
                <div>
                    <label for="nit" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NIT</label>
                    <input type="text" name="nit" id="nit" value="{{ old('modal_type') === 'create' ? old('nit') : '' }}" placeholder="Ej. 0614-123456-101-9" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- NRC -->
                <div>
                    <label for="nrc" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NRC</label>
                    <input type="text" name="nrc" id="nrc" value="{{ old('modal_type') === 'create' ? old('nrc') : '' }}" placeholder="Ej. 123456-7" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 3: Giros Comerciales -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Actividades o Giros Comerciales</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="commercial_line_1" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Principal</label>
                        <input type="text" name="commercial_line_1" id="commercial_line_1" value="{{ old('modal_type') === 'create' ? old('commercial_line_1') : '' }}" placeholder="Giro 1" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="commercial_line_2" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Secundario</label>
                        <input type="text" name="commercial_line_2" id="commercial_line_2" value="{{ old('modal_type') === 'create' ? old('commercial_line_2') : '' }}" placeholder="Giro 2" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="commercial_line_3" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Adicional</label>
                        <input type="text" name="commercial_line_3" id="commercial_line_3" value="{{ old('modal_type') === 'create' ? old('commercial_line_3') : '' }}" placeholder="Giro 3" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                </div>
            </div>

            <!-- Fila 4: Contacto y Sitio -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Teléfono -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" id="phone" value="{{ old('modal_type') === 'create' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Correo -->
                <!-- Botones -->
<div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">

    <button
        type="button"
        onclick="closeModal('create-company-modal')"
        class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-semibold hover:bg-slate-100 transition-all duration-200"
    >
        Cancelar
    </button>

    <button
        type="submit"
        id="btn-save-company"
        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-[#005e66] text-white font-bold shadow-md hover:bg-[#0b7d88] hover:shadow-lg active:scale-95 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
    >

        <!-- Icono -->
        <svg
            id="btn-save-company-icon"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
            />
        </svg>

        <!-- Spinner -->
        <svg
            id="btn-save-company-spinner"
            class="hidden w-5 h-5 animate-spin"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-20"
                cx="15"
                cy="15"
                r="13"
                stroke="currentColor"
                stroke-width="4">
            </circle>

            <path
                class="opacity-90"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
            </path>
        </svg>

        <span id="btn-save-company-text">
            Guardar Empresa
        </span>

    </button>

</div>
            </div>
        </form>
    </div>
</div>



