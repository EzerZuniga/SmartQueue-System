<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge';
import { Button } from '@/shared/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/shared/components/ui/dialog';
import Spinner from '@/shared/components/ui/spinner/Spinner.vue'; // Asegúrate de importar tu Spinner
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { abandon, callNext, finish, recall, start } from '@/routes/calls';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowLeftRight, // Added this
    Briefcase,
    CheckCircle2,
    CheckSquare,
    Clock,
    Hash,
    Hourglass,
    Megaphone,
    Monitor,
    Play,
    Timer,
    User,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// --- Interfaces (Igual que antes) ---
interface Ticket {
    id: number;
    ticket_number: string;
    client_name?: string;
    priority: number;
    created_at: string;
    created_at_formatted: string;
    service: { id: number; name: string; prefix: string };
}

interface Call {
    id: number;
    call_status: { slug: string; name: string };
    ticket: {
        ticket_number: string;
        client_name?: string;
        created_at_formatted: string;
        created_at: string;
    };
    service: { id: number; name: string; prefix: string };
    waiting_duration: number;
    started_at: string;
    called_at: string;
}

interface CounterAssignment {
    counter: { name: string };
    opened_at: string;
}

interface Service {
    id: number;
    name: string;
    prefix: string;
}

const props = defineProps<{
    assignment: CounterAssignment;
    currentCall?: Call | null;
    queue: Ticket[];
    queueCount: number;
    services: Service[];
}>();

// --- Estados ---
// Ahora guardamos QUÉ acción específica está cargando para mostrar el spinner en el botón correcto
const processingAction = ref<string | null>(null);

const isLoading = (actionName: string) => processingAction.value === actionName;
const isAnyLoading = computed(() => processingAction.value !== null);

// --- Computed ---
const statusSlug = computed(() => props.currentCall?.call_status?.slug);
const isCalled = computed(() => statusSlug.value === 'calling');
const isServing = computed(() => statusSlug.value === 'in_progress');

const isTransferDialogOpen = ref(false);

const handleTransfer = (serviceId: number) => {
    if (isAnyLoading.value) return; // Guard contra doble clic

    isTransferDialogOpen.value = false;
    // Iniciamos la acción de transferencia, mostrando loading si queremos
    processingAction.value = 'transfer';

    router.post(
        '/calls/transfer',
        { new_service_id: serviceId },
        {
            preserveScroll: true,
            onFinish: () => (processingAction.value = null),
        },
    );
};

// --- Acciones ---
// Modificamos para recibir el nombre de la acción y controlar el spinner específico
const action = (routeDef: { url: () => string }, actionName: string) => {
    processingAction.value = actionName;

    router.post(
        routeDef.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => (processingAction.value = null),
        },
    );
};

// --- LÓGICA DE CRONÓMETRO ---
const elapsedTime = ref('00:00:00');
let timerInterval: number | null = null;

const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval);

    timerInterval = window.setInterval(() => {
        if (!props.currentCall) {
            elapsedTime.value = '00:00:00';
            return;
        }

        // Determinamos la fecha base para el cronómetro
        let startTime: Date;

        if (isServing.value && props.currentCall.started_at) {
            // Si está atendiendo: Cuenta desde que inició la atención (Eficiencia Operador)
            startTime = new Date(props.currentCall.started_at);
        } else {
            // Si está llamando: Cuenta desde que se creó el ticket (Tiempo de Espera Total)
            // Opcional: Podrías contar desde que se llamó (called_at) si prefieres ver cuánto tarda en llegar el cliente
            startTime = new Date(
                props.currentCall.called_at ||
                    props.currentCall.ticket.created_at,
            );
        }

        const now = new Date();
        const diff = Math.floor((now.getTime() - startTime.getTime()) / 1000); // Diferencia en segundos

        if (diff < 0) {
            elapsedTime.value = '00:00:00';
            return;
        }

        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;

        elapsedTime.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
};

onMounted(() => {
    startTimer();

    ////WebSockets
    window.Echo.channel('kiosk').listen('TicketCreated', () => {
        // Si nosotros mismos estamos procesando una acción (como transferir),
        // no necesitamos recargar manualmente porque el router.post ya lo hará.
        if (isAnyLoading.value) return;

        console.log('¡Ticket nuevo recibido!');
        router.reload({
            only: ['queue', 'queueCount'],
        });
    });
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);

    window.Echo.leave('kiosk');
});

// Observamos cambios en currentCall para reiniciar el timer si cambia el estado
watch(
    () => props.currentCall,
    () => {
        startTimer();
    },
    { deep: true },
);
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Panel', href: '/dashboard' },
            { title: 'Atención', href: '' },
        ]"
    >
        <Head title="Módulo de Atención" />

        <div
            class="flex h-[calc(100vh-4rem)] flex-col gap-4 overflow-hidden bg-muted/20 p-4"
        >
            <div
                class="relative shrink-0 overflow-hidden rounded-xl bg-linear-to-br from-blue-700 to-blue-600 p-6 text-white shadow-xl"
            >
                <div
                    class="pointer-events-none absolute -top-10 -right-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"
                ></div>

                <div
                    class="relative z-10 flex flex-col items-center justify-between gap-4 md:flex-row"
                >
                    <div class="flex w-full items-center gap-4 md:w-auto">
                        <div
                            class="shrink-0 rounded-2xl bg-white/20 p-3 backdrop-blur-sm"
                        >
                            <Monitor class="h-8 w-8 text-white" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="mb-1 flex items-center gap-2 text-xs font-medium tracking-wider text-blue-100 uppercase"
                            >
                                <Clock class="h-3 w-3" /> Panel de Operador
                            </p>
                            <h3
                                class="truncate text-2xl font-bold tracking-tight md:text-3xl"
                            >
                                {{ assignment.counter.name }}
                            </h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="h-2 w-2 animate-pulse rounded-full bg-green-400"
                                ></span>
                                <span class="text-xs font-medium text-blue-50"
                                    >En línea</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="hidden text-right md:block">
                        <div class="text-sm font-medium text-blue-100">
                            En espera
                        </div>
                        <div class="text-3xl font-bold">{{ queueCount }}</div>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 flex-col gap-4 overflow-hidden lg:flex-row">
                <div
                    class="relative flex flex-1 flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm"
                >
                    <div
                        v-if="currentCall"
                        class="relative z-10 flex flex-1 flex-col items-center justify-center p-4 text-center"
                    >
                        <div
                            class="mb-4 flex w-full justify-center sm:absolute sm:top-4 sm:right-4 sm:mb-0 sm:justify-end"
                        >
                            <Badge
                                v-if="isCalled"
                                class="animate-pulse border-blue-200 bg-blue-100 px-3 py-1 text-xs text-blue-700 shadow-sm sm:text-sm"
                            >
                                <Megaphone class="mr-2 h-3 w-3" /> Llamando...
                            </Badge>
                            <Badge
                                v-if="isServing"
                                class="border-green-200 bg-green-100 px-3 py-1 text-xs text-green-700 shadow-sm sm:text-sm"
                            >
                                <Clock class="mr-2 h-3 w-3" /> En Atención
                            </Badge>
                        </div>

                        <div class="mt-2 mb-6 w-full space-y-3 sm:mt-0">
                            <div>
                                <p
                                    class="mb-1 text-[10px] font-semibold tracking-widest text-muted-foreground uppercase sm:text-xs"
                                >
                                    Turno Actual
                                </p>
                                <h1
                                    class="text-5xl leading-none font-black tracking-tighter text-foreground tabular-nums sm:text-7xl"
                                >
                                    {{ currentCall.ticket.ticket_number }}
                                </h1>
                            </div>

                            <div
                                class="inline-flex max-w-full items-center gap-2 rounded-full bg-muted/50 px-3 py-1 text-xs font-medium text-foreground sm:text-sm"
                            >
                                <Briefcase
                                    class="h-3 w-3 shrink-0 text-primary sm:h-4 sm:w-4"
                                />
                                <span class="truncate">{{
                                    currentCall.service.name
                                }}</span>
                            </div>
                        </div>

                        <div
                            class="mb-6 flex w-full max-w-lg flex-col items-center justify-center gap-2 rounded-lg border border-border/50 bg-muted/20 p-3 text-xs text-muted-foreground sm:flex-row sm:gap-6 sm:text-sm"
                        >
                            <div
                                v-if="currentCall.ticket.client_name"
                                class="flex items-center gap-2"
                            >
                                <User
                                    class="h-3 w-3 text-primary sm:h-4 sm:w-4"
                                />
                                <span
                                    class="max-w-[150px] truncate font-medium text-foreground sm:max-w-[200px]"
                                >
                                    {{ currentCall.ticket.client_name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Clock
                                    class="h-3 w-3 text-primary sm:h-4 sm:w-4"
                                />
                                <span
                                    >Creado:
                                    {{
                                        currentCall.ticket.created_at_formatted
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex items-center gap-2 rounded border border-border bg-background px-2 py-0.5 shadow-sm"
                            >
                                <component
                                    :is="isServing ? Timer : Hourglass"
                                    class="h-3 w-3 sm:h-4 sm:w-4"
                                    :class="
                                        isServing
                                            ? 'text-green-500'
                                            : 'text-blue-500'
                                    "
                                />
                                <span
                                    class="font-mono font-bold tracking-wider text-foreground tabular-nums"
                                >
                                    {{ elapsedTime }}
                                </span>
                            </div>
                        </div>

                        <!----Opciones de la llamada-->
                        <div
                            class="grid w-full max-w-xl grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4"
                        >
                            <template v-if="isCalled">
                                <Button
                                    @click="action(start, 'start')"
                                    :disabled="isAnyLoading"
                                    size="default"
                                    class="col-span-1 h-12 bg-green-600 text-base text-white shadow-md shadow-green-900/10 hover:bg-green-700 sm:col-span-2"
                                >
                                    <Spinner
                                        v-if="isLoading('start')"
                                        class="mr-2 h-4 w-4"
                                    />
                                    <Play v-else class="mr-2 h-4 w-4" />
                                    Empezar
                                </Button>

                                <Button
                                    @click="action(recall, 'recall')"
                                    :disabled="isAnyLoading"
                                    variant="outline"
                                    size="default"
                                    class="h-12 hover:bg-muted/50"
                                >
                                    <Spinner
                                        v-if="isLoading('recall')"
                                        class="mr-2 h-4 w-4"
                                    />
                                    <Megaphone v-else class="mr-2 h-4 w-4" />
                                    Rellamar
                                </Button>

                                <Button
                                    @click="action(abandon, 'abandon')"
                                    :disabled="isAnyLoading"
                                    variant="destructive"
                                    size="default"
                                    class="h-12 shadow-md shadow-red-900/10"
                                >
                                    <Spinner
                                        v-if="isLoading('abandon')"
                                        class="mr-2 h-4 w-4"
                                    />
                                    <XCircle v-else class="mr-2 h-4 w-4" />
                                    No Estuvo
                                </Button>
                            </template>

                            <template v-if="isServing">
                                <!-- Botón Derivar -->
                                <Dialog v-model:open="isTransferDialogOpen">
                                    <DialogTrigger as-child>
                                        <Button
                                            :disabled="isAnyLoading"
                                            variant="secondary"
                                            size="default"
                                            class="col-span-1 h-12 border border-amber-200 bg-amber-100 text-amber-900 shadow-sm hover:bg-amber-200 sm:col-span-1 lg:col-span-2"
                                        >
                                            <ArrowLeftRight
                                                class="mr-2 h-5 w-5"
                                            />
                                            Derivar
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-md">
                                        <DialogHeader>
                                            <DialogTitle
                                                >Derivar Ticket</DialogTitle
                                            >
                                            <DialogDescription>
                                                Selecciona el servicio destino.
                                                El ticket conservará su hora de
                                                llegada original.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div
                                            class="grid max-h-[60vh] gap-2 overflow-y-auto py-4"
                                        >
                                            <Button
                                                v-for="service in services"
                                                :key="service.id"
                                                variant="outline"
                                                class="h-auto justify-between px-4 py-4 hover:border-primary hover:bg-primary/5"
                                                :disabled="
                                                    isAnyLoading ||
                                                    service.id ===
                                                        currentCall.service.id
                                                "
                                                @click="
                                                    handleTransfer(service.id)
                                                "
                                            >
                                                <span class="font-semibold">{{
                                                    service.name
                                                }}</span>
                                                <Badge
                                                    variant="secondary"
                                                    class="font-mono text-xs"
                                                >
                                                    {{ service.prefix }}
                                                </Badge>
                                            </Button>
                                            <p
                                                v-if="services.length <= 1"
                                                class="text-center text-sm text-muted-foreground"
                                            >
                                                No hay otros servicios
                                                disponibles para derivar.
                                            </p>
                                        </div>
                                    </DialogContent>
                                </Dialog>

                                <!-- Botón Finalizar -->
                                <Button
                                    @click="action(finish, 'finish')"
                                    :disabled="isAnyLoading"
                                    size="default"
                                    class="col-span-1 h-12 bg-primary text-lg shadow-md shadow-primary/20 hover:bg-primary/90 sm:col-span-1 lg:col-span-2"
                                >
                                    <Spinner
                                        v-if="isLoading('finish')"
                                        class="mr-2 h-5 w-5"
                                    />
                                    <CheckSquare v-else class="mr-2 h-5 w-5" />
                                    Finalizar Atención
                                </Button>
                            </template>
                        </div>
                    </div>

                    <div
                        v-else
                        class="relative z-10 flex flex-1 flex-col items-center justify-center bg-muted/5 p-6 text-center"
                    >
                        <div
                            class="mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-blue-50 sm:h-32 sm:w-32 dark:bg-blue-900/20"
                        >
                            <Hash
                                class="h-10 w-10 text-blue-200 sm:h-14 sm:w-14 dark:text-blue-800"
                            />
                        </div>

                        <h3
                            class="mb-1 text-xl font-bold text-foreground sm:text-2xl"
                        >
                            Ventanilla Libre
                        </h3>
                        <p
                            class="mx-auto mb-6 max-w-sm px-2 text-sm text-muted-foreground sm:text-base"
                        >
                            La ventanilla está disponible. Llama al siguiente
                            cliente.
                        </p>

                        <Button
                            @click="action(callNext, 'callNext')"
                            :disabled="queue.length === 0 || isAnyLoading"
                            size="lg"
                            class="h-14 w-full min-w-[200px] rounded-full bg-blue-600 px-8 text-lg shadow-lg shadow-blue-600/20 transition-all hover:scale-105 hover:bg-blue-700 sm:h-16 sm:w-auto"
                        >
                            <Spinner
                                v-if="isLoading('callNext')"
                                class="mr-2 h-5 w-5"
                            />
                            <Megaphone v-else class="mr-2 h-5 w-5" />
                            {{
                                queue.length > 0
                                    ? 'Llamar Siguiente'
                                    : 'Cola Vacía'
                            }}
                        </Button>
                    </div>
                </div>

                <!------Barra lateral que muestra los pendientes-->
                <div
                    class="flex h-64 w-full shrink-0 flex-col gap-4 lg:h-auto lg:w-96"
                >
                    <div
                        class="flex h-full flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-border bg-muted/30 p-4 backdrop-blur-sm"
                        >
                            <h3
                                class="flex items-center gap-2 text-sm font-bold tracking-wide text-foreground uppercase"
                            >
                                <User class="h-4 w-4 text-primary" />
                                En Espera
                            </h3>
                        </div>

                        <div
                            class="flex-1 space-y-3 overflow-y-auto bg-muted/10 p-3"
                        >
                            <div
                                v-if="queue.length === 0"
                                class="flex h-full flex-col items-center justify-center py-10 text-muted-foreground opacity-60"
                            >
                                <div
                                    class="mb-3 rounded-full bg-muted p-4 shadow-inner"
                                >
                                    <CheckCircle2 class="h-8 w-8 opacity-40" />
                                </div>
                                <p class="text-sm font-medium">
                                    No hay tickets pendientes
                                </p>
                            </div>

                            <div
                                v-for="(ticket, index) in queue"
                                :key="ticket.id"
                                class="group relative flex items-stretch overflow-hidden rounded-lg border border-border bg-card shadow-sm transition-all hover:border-primary/50 hover:shadow-md"
                                :class="{
                                    'bg-amber-50/30 ring-1 ring-amber-400 dark:bg-amber-950/10':
                                        ticket.priority === 1,
                                }"
                            >
                                <div
                                    class="w-1.5 shrink-0"
                                    :class="
                                        ticket.priority === 1
                                            ? 'bg-amber-400'
                                            : 'bg-primary/20 group-hover:bg-primary'
                                    "
                                ></div>

                                <div
                                    class="flex flex-1 flex-col justify-center gap-1 p-3"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-lg font-bold tracking-tight text-foreground tabular-nums"
                                        >
                                            {{ ticket.ticket_number }}
                                        </span>
                                        <span
                                            class="rounded bg-muted px-1.5 font-mono text-[10px] text-muted-foreground"
                                        >
                                            #{{ index + 1 }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1 flex items-end justify-between"
                                    >
                                        <div
                                            class="flex max-w-[140px] items-center gap-1.5 truncate text-xs text-muted-foreground"
                                        >
                                            <Briefcase
                                                class="h-3 w-3 opacity-70"
                                            />
                                            <span class="truncate">{{
                                                ticket.service.name
                                            }}</span>
                                        </div>
                                        <div
                                            class="text-[10px] font-medium text-muted-foreground/70"
                                        >
                                            {{ ticket.created_at_formatted }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="ticket.priority === 1"
                                    class="absolute top-0 right-0 p-1"
                                >
                                    <User
                                        class="h-3 w-3 fill-amber-500/20 text-amber-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
