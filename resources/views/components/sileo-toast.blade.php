<div 
    x-data="sileoNotificationContainer()"
    x-init="init()"
    class="fixed top-6 right-6 z-[99999] flex flex-col items-end gap-3 max-w-sm w-full pointer-events-none px-4 sm:px-0"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-x-10 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-10 scale-95"
            class="pointer-events-auto relative overflow-hidden rounded-2xl p-4 shadow-2xl border border-slate-700/80 transition-all duration-300 min-w-[320px]"
            style="background-color: #0f172a !important; color: #ffffff !important;"
        >
            <div class="flex items-start gap-3.5">
                <!-- Icono de Estado -->
                <div class="flex-shrink-0 mt-0.5">
                    <!-- Éxito -->
                    <template x-if="toast.type === 'success'">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                            <svg class="w-4 h-4 stroke-current text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                    </template>
                    <!-- Error -->
                    <template x-if="toast.type === 'error'">
                        <div class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(244,63,94,0.3)]">
                            <svg class="w-4 h-4 stroke-current text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </template>
                    <!-- Info -->
                    <template x-if="toast.type === 'info'">
                        <div class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(14,165,233,0.3)]">
                            <svg class="w-4 h-4 stroke-current text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </div>
                    </template>
                    <!-- Advertencia -->
                    <template x-if="toast.type === 'warning'">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center shadow-[0_0_15px_rgba(245,158,11,0.3)]">
                            <svg class="w-4 h-4 stroke-current text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                    </template>
                </div>

                <!-- Contenido -->
                <div class="flex-1 pr-2">
                    <h4 x-text="toast.title" class="text-sm font-bold tracking-tight" style="color: #ffffff !important;"></h4>
                    <p x-text="toast.message" class="text-xs font-medium mt-0.5 leading-relaxed" style="color: #cbd5e1 !important;"></p>
                </div>

                <!-- Botón Cerrar -->
                <button 
                    @click="removeToast(toast.id)"
                    class="p-1 transition-colors rounded-lg hover:bg-slate-800"
                    style="color: #94a3b8 !important;"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Barra de Progreso Sileo -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-slate-800/60 overflow-hidden">
                <div 
                    class="h-full transition-all linear"
                    :class="{
                        'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]': toast.type === 'success',
                        'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]': toast.type === 'error',
                        'bg-sky-500 shadow-[0_0_8px_rgba(14,165,233,0.8)]': toast.type === 'info',
                        'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]': toast.type === 'warning'
                    }"
                    :style="`width: ${toast.progress}%; transition-duration: 50ms;`"
                ></div>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sileoNotificationContainer', () => ({
            toasts: [],
            lastMessages: new Map(),
            init() {
                window.sileo = {
                    show: (type, message, title = null, duration = 4000) => {
                        this.addToast(type, message, title, duration);
                    },
                    success: (message, title = '¡Éxito!') => {
                        this.addToast('success', message, title);
                    },
                    error: (message, title = 'Error') => {
                        this.addToast('error', message, title);
                    },
                    info: (message, title = 'Información') => {
                        this.addToast('info', message, title);
                    },
                    warning: (message, title = 'Atención') => {
                        this.addToast('warning', message, title);
                    }
                };

                // Cargar notificaciones flash enviadas desde Laravel Backend una sola vez por página
                if (!window.__sileoFlashDispatched) {
                    window.__sileoFlashDispatched = true;
                    @if(session('success'))
                        window.sileo.success(@json(session('success')), '¡Operación Exitosa!');
                    @endif
                    @if(session('error'))
                        window.sileo.error(@json(session('error')), '¡Ha ocurrido un problema!');
                    @endif
                    @if(session('info'))
                        window.sileo.info(@json(session('info')), 'Información');
                    @endif
                    @if(session('warning'))
                        window.sileo.warning(@json(session('warning')), 'Advertencia');
                    @endif
                    @if(session('status'))
                        window.sileo.info(@json(session('status')), 'Estado del Sistema');
                    @endif
                }
            },
            addToast(type, message, title, duration = 4000) {
                if (!message) return;

                // Prevenir mensajes duplicados idénticos en un lapso de 2 segundos
                const key = `${type}:${message}`;
                const now = Date.now();
                if (this.lastMessages.has(key) && (now - this.lastMessages.get(key) < 2000)) {
                    return;
                }
                this.lastMessages.set(key, now);

                const id = now + Math.random();
                const defaultTitles = {
                    success: '¡Operación Exitosa!',
                    error: 'Error',
                    info: 'Información',
                    warning: 'Advertencia'
                };
                
                const toastObj = {
                    id,
                    type,
                    title: title || defaultTitles[type] || 'Notificación',
                    message,
                    visible: true,
                    progress: 100,
                };

                this.toasts.push(toastObj);

                const startTime = Date.now();
                const interval = setInterval(() => {
                    const elapsed = Date.now() - startTime;
                    const remaining = Math.max(0, duration - elapsed);
                    toastObj.progress = (remaining / duration) * 100;

                    if (remaining <= 0) {
                        clearInterval(interval);
                        this.removeToast(id);
                    }
                }, 50);
            },
            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts[index].visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 250);
                }
            }
        }));
    });
</script>