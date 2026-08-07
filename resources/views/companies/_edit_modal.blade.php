<!-- MODAL DE EDICIÓN DE EMPRESA -->
<div id="edit-company-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-company-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Empresa</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica la información fiscal, giros, mapa de ubicaciones y logotipos corporativos.</p>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Fila 1: Datos Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit-name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Razón Social <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Corporación ABC S.A. de C.V." class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <label for="edit-commercial_name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Comercial</label>
                    <input type="text" name="commercial_name" id="edit-commercial_name" value="{{ old('modal_type') === 'edit' ? old('commercial_name') : '' }}" placeholder="Ej. Tiendas ABC" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 2: Documentos Fiscales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIT -->
                <div>
                    <label for="edit-nit" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NIT</label>
                    <input type="text" name="nit" id="edit-nit" value="{{ old('modal_type') === 'edit' ? old('nit') : '' }}" placeholder="Ej. 0614-123456-101-9" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- NRC -->
                <div>
                    <label for="edit-nrc" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NRC</label>
                    <input type="text" name="nrc" id="edit-nrc" value="{{ old('modal_type') === 'edit' ? old('nrc') : '' }}" placeholder="Ej. 123456-7" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 3: Giros Comerciales -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Actividades o Giros Comerciales</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="edit-commercial_line_1" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Principal</label>
                        <input type="text" name="commercial_line_1" id="edit-commercial_line_1" value="{{ old('modal_type') === 'edit' ? old('commercial_line_1') : '' }}" placeholder="Giro 1" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="edit-commercial_line_2" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Secundario</label>
                        <input type="text" name="commercial_line_2" id="edit-commercial_line_2" value="{{ old('modal_type') === 'edit' ? old('commercial_line_2') : '' }}" placeholder="Giro 2" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="edit-commercial_line_3" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Adicional</label>
                        <input type="text" name="commercial_line_3" id="edit-commercial_line_3" value="{{ old('modal_type') === 'edit' ? old('commercial_line_3') : '' }}" placeholder="Giro 3" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                </div>
            </div>

            <!-- Fila 4: Contacto y Sitio -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Teléfono -->
                <div>
                    <label for="edit-phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" id="edit-phone" value="{{ old('modal_type') === 'edit' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Correo -->
                <div>
                    <label for="edit-email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo electrónico</label>
                    <input type="email" name="email" id="edit-email" value="{{ old('modal_type') === 'edit' ? old('email') : '' }}" placeholder="Ej. contacto@empresa.com" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Sitio Web -->
                <div>
                    <label for="edit-web_site" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sitio Web</label>
                    <input type="url" name="web_site" id="edit-web_site" value="{{ old('modal_type') === 'edit' ? old('web_site') : '' }}" placeholder="Ej. https://www.empresa.com" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 5: Ubicación Geográfica (El Salvador) -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="edit_department_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Departamento</label>
                        <select name="department_id" id="edit_department_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit_municipality_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Municipio</label>
                        <select name="municipality_id" id="edit_municipality_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione municipio</option>
                            @foreach($municipalities as $muni)
                                <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}">{{ $muni->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit_district_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Distrito</label>
                        <select name="district_id" id="edit_district_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione distrito</option>
                            @foreach($districts as $dist)
                                <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}">{{ $dist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila 6: Dirección Detallada y Logo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dirección -->
                <div>
                    <label for="edit-address" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Dirección de la Empresa</label>
                    <textarea name="address" id="edit-address" rows="2" placeholder="Ej. Calle y Avenida, San Salvador" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold">{{ old('modal_type') === 'edit' ? old('address') : '' }}</textarea>
                </div>

                <!-- Logo -->
                <div>
                    <label for="edit-logo" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Actualizar Logo</label>
                    <input type="file" name="logo" id="edit-logo" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-500 font-semibold">
                    <p class="text-[10px] text-slate-400 mt-1">Dejar en blanco para conservar el actual. Máx 2MB.</p>
                </div>
            </div>

            <!-- Estado Activo -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                <label for="edit-is_active" class="text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer">Empresa Activa</label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-company-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
