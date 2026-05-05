<script setup lang="ts">
import NumpadModal from '@/components/kiosk/NumpadModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/tickets';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Accessibility,
    CheckCircle2,
    ChevronRight,
    Maximize,
    Minimize,
    Printer,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

// --- Interfaces ---
interface TicketData {
    ticket_number: string;
    service_name: string;
    created_at: string;
    waiting_count: number;
    settings: {
        name: string;
        address: string | null;
        email: string | null;
        phone: string | null;
        print: boolean;
        footer: string | null;
    };
}

interface Service {
    id: number;
    name: string;
    prefix: string;
    start_number: number;
    ask_document: boolean;
}

const props = defineProps<{
    services: Service[];
    logoUrl?: string | null;
    kioskToken: string;
}>();

// --- Estado ---
const selectedService = ref<Service | null>(null);
const showNumpad = ref(false); // Modal Numérico
const showSuccessDialog = ref(false);
const generatedTicket = ref<TicketData | null>(null);
const isPreferential = ref(false);
const isFullscreen = ref(false);

const form = useForm({
    service_id: null as number | null,
    client_document: '',
    priority: 0,
});

//Detectar si tenemos la Ventanilla Preferencial Activa
const isPreferentialCounterOpen = computed(() => {
    return props.services.some((service) => service.prefix === 'P');
});
const showPreferentialToggle = computed(() => !isPreferentialCounterOpen.value);

const page = usePage<any>();

///=================METODOS====================
// Lógica al hacer click en un servicio
const handleServiceSelect = (service: Service) => {
    selectedService.value = service;
    form.service_id = service.id;

    form.priority = isPreferential.value ? 1 : 0;

    // Limpiamos errores previos y datos
    form.clearErrors();
    form.client_document = '';

    if (service.ask_document) {
        // Abrimos el teclado numérico si el servicio lo requiere
        showNumpad.value = true;
    } else {
        // Si no pide documento, asignamos el valor por defecto y enviamos directo
        form.client_document = '00000000';
        submitTicket();
    }
};

const handleNumpadConfirm = (document: string) => {
    form.client_document = document;
    submitTicket();
};

//Crear un nuevo Ticket
const submitTicket = () => {
    form.post(store.url({ token: props.kioskToken }), {
        preserveScroll: true,
        onSuccess: async (page) => {
            isPreferential.value = false;

            const flash = page.props.flash as any;
            if (flash?.ticket_created) {
                const data = flash.ticket_created as TicketData;
                generatedTicket.value = data;

                showNumpad.value = false;
                showSuccessDialog.value = true;

                form.reset();
                selectedService.value = null;

                // LÓGICA DE IMPRESIÓN AUTOMÁTICA
                if (data.settings.print) {
                    await nextTick();
                    setTimeout(() => {
                        window.print();
                    }, 500);
                }

                // Auto-cerrar modal
                setTimeout(() => {
                    closeSuccess();
                }, 3000);
            }
        },
        onError: () => {
            // Si hay error (ej: documento inválido), mantenemos el Numpad abierto para corregir
            if (form.errors.client_document) {
                showNumpad.value = true;
            }
        },
    });
};

// Función para alternar el modo preferencial
const togglePreferential = () => {
    isPreferential.value = !isPreferential.value;
};

// Cerrar modales
const closeSuccess = () => {
    showSuccessDialog.value = false;
    if (page.props.flash) {
        page.props.flash.ticket_created = null;
    }
};

// Función manual por si quieren reimprimir desde el modal
const printTicketManual = () => {
    window.print();
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
            isFullscreen.value = false;
        }
    }
};

// ==========================================
// LÓGICA WEBSOCKETS (REVERB)
// ==========================================
onMounted(() => {
    window.Echo.channel('kiosk').listen('ServicesUpdated', (e: any) => {
        console.log('Detectado cambio en servicios, actualizando...' + e);
        router.reload({
            only: ['services'],
        });
    });
});

onUnmounted(() => {
    window.Echo.leave('kiosk');
});
</script>

<template>
    <div
        class="no-print relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-neutral-50 p-6 dark:bg-neutral-950"
    >
        <div
            v-if="logoUrl"
            class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center opacity-30"
        >
            <img
                :src="logoUrl"
                class="h-full w-full object-cover"
                alt="Fondo"
            />
        </div>

        <div class="relative z-10 flex w-full flex-col items-center">
            <Head title="Nuevo Turno" />

            <!--pre class="fixed top-0 left-0 bg-black text-white p-4 z-50 opacity-80 text-xs">
        {{ $page.props.flash.ticket_created }}
    </pre-->
            <div
                class="mb-4 flex w-full max-w-6xl items-center justify-end gap-3 px-4"
            >
                <button
                    @click="toggleFullscreen"
                    class="rounded-full p-2 text-slate-400 transition-colors hover:bg-neutral-100 hover:text-primary dark:hover:bg-neutral-800"
                    title="Alternar pantalla completa"
                >
                    <Maximize v-if="!isFullscreen" class="h-6 w-6" />
                    <Minimize v-else class="h-6 w-6" />
                </button>
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="transform opacity-0 -translate-y-2"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <button
                        v-if="showPreferentialToggle"
                        @click="togglePreferential"
                        class="flex items-center gap-2 rounded-full px-6 py-3 font-bold shadow-sm transition-all duration-300"
                        :class="
                            isPreferential
                                ? 'scale-105 bg-amber-400 text-amber-900 ring-4 shadow-amber-200 ring-amber-200'
                                : 'border border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-100'
                        "
                    >
                        <Accessibility class="h-6 w-6" />
                        <span>{{
                            isPreferential
                                ? 'Modo Preferencial ACTIVADO'
                                : 'Atención Preferencial'
                        }}</span>

                        <span
                            v-if="isPreferential"
                            class="relative ml-2 flex h-3 w-3"
                        >
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-600 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-3 w-3 rounded-full bg-amber-600"
                            ></span>
                        </span>
                    </button>
                </transition>
            </div>

            <!---div class="text-center mb-10 max-w-2xl">
                <h1 class="text-4xl font-extrabold ...">
                    {{ isPreferential ? 'Seleccione trámite PREFERENCIAL' : 'Bienvenido' }}
                </h1>
            </div--->

            <div class="mb-10 max-w-2xl text-center">
                <h1
                    class="mb-2 text-4xl font-extrabold tracking-tight text-neutral-900 dark:text-white"
                >
                    {{
                        isPreferential
                            ? 'Seleccione trámite PREFERENCIAL'
                            : 'Bienvenido'
                    }}
                </h1>
                <p class="text-lg text-neutral-300">
                    Por favor, seleccione el trámite que desea realizar hoy.
                </p>
            </div>

            <div
                class="grid w-full max-w-6xl grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <button
                    v-for="service in services"
                    :key="service.id"
                    @click="handleServiceSelect(service)"
                    :disabled="form.processing"
                    class="group relative flex flex-col items-start rounded-2xl border-2 p-8 text-left transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                    :class="[
                        form.processing && form.service_id === service.id
                            ? 'cursor-wait border-primary bg-primary/5'
                            : isPreferential && service.prefix !== 'P'
                              ? 'border-amber-400 bg-white hover:border-amber-400 hover:shadow-xl hover:shadow-amber-200 dark:bg-neutral-800'
                              : 'border-neutral-200 bg-white hover:border-primary hover:shadow-xl dark:border-neutral-500 dark:bg-neutral-800',
                    ]"
                >
                    <!--div class="mb-6 p-4 rounded-xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                    <Ticket class="w-8 h-8" />
                </div--->

                    <h3
                        class="mb-2 text-2xl font-bold text-neutral-900 dark:text-white"
                    >
                        {{ service.name }}
                    </h3>
                    <p class="text-sm font-medium text-neutral-500">
                        Toque para obtener turno
                    </p>

                    <div
                        class="absolute right-6 bottom-6 translate-x-2 transform opacity-0 transition-opacity group-hover:translate-x-0 group-hover:opacity-100"
                    >
                        <ChevronRight class="h-6 w-6 text-primary" />
                    </div>

                    <div
                        v-if="isPreferential && service.prefix !== 'P'"
                        class="absolute top-4 right-4 text-amber-500"
                    >
                        <Accessibility class="h-6 w-6" />
                    </div>

                    <Spinner
                        v-if="form.processing && form.service_id === service.id"
                        class="absolute top-6 right-6 text-primary"
                    />
                </button>
            </div>
        </div>

        <!--div class="mt-16 text-neutral-400 text-sm">
            Sistema de Gestión de Colas © {{ new Date().getFullYear() }}
        </div---->

        <NumpadModal
            v-model:open="showNumpad"
            :loading="form.processing"
            :service-name="selectedService?.name"
            :error-message="form.errors.client_document"
            @confirm="handleNumpadConfirm"
        />

        <Dialog v-model:open="showSuccessDialog">
            <DialogContent class="text-center sm:max-w-sm">
                <DialogDescription class="sr-only">
                    El ticket ha sido generado exitosamente.
                </DialogDescription>
                <div
                    class="flex flex-col items-center justify-center gap-4 py-6"
                >
                    <div
                        class="mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600"
                    >
                        <CheckCircle2 class="h-8 w-8" />
                    </div>

                    <DialogTitle class="text-2xl font-bold"
                        >¡Turno Registrado!</DialogTitle
                    >

                    <div
                        class="my-2 w-full rounded-xl border-2 border-dashed border-neutral-200 bg-neutral-50 p-6"
                    >
                        <p
                            class="mb-1 text-sm font-semibold tracking-wider text-neutral-500 uppercase"
                        >
                            Su número es
                        </p>
                        <p
                            class="text-6xl font-black tracking-tighter text-neutral-900"
                        >
                            {{ generatedTicket?.ticket_number }}
                        </p>
                        <p class="mt-2 text-sm text-neutral-400">
                            El ticket ha sido generado exitosamente.
                        </p>
                    </div>
                    <p
                        class="text-sm text-neutral-500"
                        v-if="generatedTicket?.settings.print"
                    >
                        Imprimiendo ticket...
                    </p>

                    <!---p class="text-sm text-neutral-500">
                        Se ha enviado una orden de impresión.
                    </p--->
                </div>

                <DialogFooter class="sm:justify-center">
                    <Button
                        @click="printTicketManual()"
                        class="w-full"
                        size="lg"
                    >
                        <Printer class="mr-2 h-4 w-4" /> Imprimir Comprobante
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>

    <div id="printable-area" v-if="generatedTicket">
        <div class="ticket-container">
            <div class="header">
                <h2 class="company-name">
                    {{ generatedTicket.settings.name }}
                </h2>
                <p v-if="generatedTicket.settings.address">
                    {{ generatedTicket.settings.address }}
                </p>
                <p v-if="generatedTicket.settings.phone">
                    Tel: {{ generatedTicket.settings.phone }}
                </p>
                <p>{{ generatedTicket.created_at }}</p>
            </div>

            <hr class="divider" />

            <div class="body">
                <p class="service-name">{{ generatedTicket.service_name }}</p>
                <h1 class="ticket-number">
                    {{ generatedTicket.ticket_number }}
                </h1>

                <div class="waiting-info">
                    <span v-if="generatedTicket.waiting_count === 0"
                        >¡Es su turno!</span
                    >
                    <span v-else
                        >Personas en espera:
                        <strong>{{
                            generatedTicket.waiting_count
                        }}</strong></span
                    >
                </div>
            </div>

            <hr class="divider" />

            <div class="footer">
                <p v-if="generatedTicket.settings.footer">
                    {{ generatedTicket.settings.footer }}
                </p>
                <p v-else>Gracias por su preferencia</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
#printable-area {
    display: none;
}
</style>

<style>
@media print {
    /* Ocultar TODO el contenido del body por defecto */
    body {
        visibility: hidden;
        background: white;
        height: auto;
    }

    /* Ocultar explícitamente los Modales de Shadcn/Radix */
    /* Estos viven en el body, por eso necesitamos estilos NO scoped */
    [role='dialog'],
    [data-state='open'],
    .group.fixed.z-50 {
        display: none !important;
    }

    /* Asegurar que el área de impresión sea visible */
    #printable-area {
        visibility: visible !important;
        display: block !important;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        background: white;
        z-index: 2147483647;
        /* El número más alto posible en CSS */
    }

    /* Asegurar que los hijos del área de impresión también sean visibles */
    #printable-area * {
        visibility: visible !important;
    }

    /* Diseño del Ticket */
    /* Diseño del Ticket */
    .ticket-container {
        /* Ancho estándar para papel térmico de 80mm */
        width: 72mm;
        margin: 0 auto;
        padding: 5px 0;
        text-align: center;
        font-family: 'Courier New', Courier, monospace;
        color: black;
        page-break-inside: avoid;
    }

    /* Header */
    .header .company-name {
        font-size: 18px;
        font-weight: bold;
        margin: 0 0 5px 0;
        text-transform: uppercase;
        line-height: 1.2;
    }

    /* Fallback por si no usa la clase company-name */
    .header h2 {
        font-size: 18px;
        font-weight: bold;
        margin: 0 0 5px 0;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .header p {
        font-size: 11px;
        margin: 2px 0;
    }

    /* Separadores */
    .divider {
        border-top: 2px dashed black;
        margin: 8px 0;
        display: block;
        width: 100%;
    }

    .ticket-number {
        font-size: 56px;
        font-weight: 900;
        margin: 5px 0;
        line-height: 1;
        letter-spacing: -2px;
    }

    .service-name {
        font-size: 16px;
        font-weight: bold;
        margin: 5px 0;
        text-transform: uppercase;
    }

    .footer {
        font-size: 10px;
        margin-top: 15px;
        font-style: italic;
    }

    .waiting-info {
        font-size: 12px;
        font-weight: bold;
        margin-top: 5px;
    }

    @page {
        margin: 0;
        size: auto;
    }
}
</style>
