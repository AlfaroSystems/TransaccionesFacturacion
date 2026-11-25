{{-- Campos del cliente. Requiere: $customer (modelo o null), $prefix, $departments, $municipalities, $districts --}}
@php
    $c = $customer;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre / Razón social *</label>
        <input type="text" name="name" value="{{ old('name', $c->name ?? '') }}" required
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Tipo de cliente *</label>
        <select name="customer_type" id="{{ $prefix }}customer_type"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
            <option value="final" {{ old('customer_type', $c->customer_type ?? 'final') === 'final' ? 'selected' : '' }}>Consumidor final</option>
            <option value="taxpayer" {{ old('customer_type', $c->customer_type ?? '') === 'taxpayer' ? 'selected' : '' }}>Contribuyente</option>
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Tipo de documento</label>
        <select name="document_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
            <option value="">—</option>
            <option value="dui" {{ old('document_type', $c->document_type ?? '') === 'dui' ? 'selected' : '' }}>DUI</option>
            <option value="nit" {{ old('document_type', $c->document_type ?? '') === 'nit' ? 'selected' : '' }}>NIT</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Número de documento</label>
        <input type="text" name="document_number" value="{{ old('document_number', $c->document_number ?? '') }}" maxlength="50"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
    </div>
    <div id="{{ $prefix }}nrc_wrap" class="hidden">
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">NRC *</label>
        <input type="text" name="nrc" value="{{ old('nrc', $c->nrc ?? '') }}" maxlength="50"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Correo electrónico</label>
        <input type="email" name="email" value="{{ old('email', $c->email ?? '') }}" maxlength="255"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"
            placeholder="Para el envío del comprobante">
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Teléfono</label>
        <input type="text" name="phone" value="{{ old('phone', $c->phone ?? '') }}" maxlength="30"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Giro</label>
        <input type="text" name="commercial_line" value="{{ old('commercial_line', $c->commercial_line ?? '') }}" maxlength="255"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
    </div>
</div>

{{-- Ubicación geográfica (El Salvador) --}}
<div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
    <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Departamento</label>
            <select name="id_department" id="{{ $prefix }}id_department" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs text-slate-700 font-semibold">
                <option value="">Seleccione departamento</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id_department }}" {{ old('id_department', $c->id_department ?? '') == $dept->id_department ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Municipio</label>
            <select name="id_municipality" id="{{ $prefix }}id_municipality" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs text-slate-700 font-semibold">
                <option value="">Seleccione municipio</option>
                @foreach($municipalities as $muni)
                    <option value="{{ $muni->id_municipality }}" data-parent="{{ $muni->id_department }}" {{ old('id_municipality', $c->id_municipality ?? '') == $muni->id_municipality ? 'selected' : '' }}>{{ $muni->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Distrito</label>
            <select name="id_district" id="{{ $prefix }}id_district" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs text-slate-700 font-semibold">
                <option value="">Seleccione distrito</option>
                @foreach($districts as $dist)
                    <option value="{{ $dist->id_district }}" data-parent="{{ $dist->id_municipality }}" {{ old('id_district', $c->id_district ?? '') == $dist->id_district ? 'selected' : '' }}>{{ $dist->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Dirección</label>
        <input type="text" name="address" value="{{ old('address', $c->address ?? '') }}" maxlength="255"
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Estado</label>
        <select name="is_active" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
            <option value="1" {{ old('is_active', $c->is_active ?? true) ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ !old('is_active', $c->is_active ?? true) ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
</div>
