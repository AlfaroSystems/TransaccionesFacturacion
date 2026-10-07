{{-- Permisos agrupados por módulo (App\Support\PermissionGroups); $prefix: create | edit --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($permissionGroups as $group)
        <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
            <h4 class="text-[10px] font-extrabold text-navy-800 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full {{ $group['color'] }}"></span>
                {{ $group['title'] }}
            </h4>
            <div class="space-y-3 max-h-48 overflow-y-auto">
                @foreach($group['permissions'] as $permission)
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id_permission }}" id="{{ $prefix }}-permission-{{ str_replace('.', '-', $permission->id_permission) }}" class="{{ $prefix }}-permission-checkbox mt-0.5 rounded-sm text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                        <div>
                            <span class="text-xs font-bold text-slate-700 block group-hover:text-navy-sidebar transition-colors">{{ $permission->name }}</span>
                            <span class="text-[10px] text-slate-400 font-semibold block leading-tight mt-0.5">{{ $permission->description }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
