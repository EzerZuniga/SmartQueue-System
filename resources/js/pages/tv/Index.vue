<script setup lang="ts">
/**
 * ============================================================================
 * COMPONENTE: PANTALLA DE TURNOS (TV / DIGITAL SIGNAGE)
 * ============================================================================
 * Descripción: Muestra el turno actual, historial de llamadas y contenido multimedia.
 * Características:
 * - Actualización en tiempo real vía WebSockets (Reverb/Pusher).
 * - Sincronización reactiva con Inertia.
 * - Reproductor YouTube con API IFrame (Autoplay, Loop, Playlist).
 * - Diseño adaptable y tematizable dinámicamente.
 */

// 1. IMPORTS
import { Head, router } from '@inertiajs/vue3';
import { Maximize, Minimize, Monitor, Volume2 } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

// ==========================================
// 2. DEFINICIÓN DE TIPOS E INTERFACES
// ==========================================
interface Call {
    id: number;
    token_letter: string | null;
    token_number: number;
    call_status: { name: string; slug: string; color?: string };
    counter?: { name: string };
    ticket: {
        ticket_number: string;
        client_name: string | null;
    };
}

interface Settings {
    display_notification: string | null;
    display_font_size: number;
    display_font_color: string;
    voice_enabled: boolean;
}

// Interfaces para YouTube API
interface YTPlayer {
    destroy(): void;
    playVideo(): void;
    playVideoAt(index: number): void;
    nextVideo(): void;
    unMute(): void;
    setVolume(volume: number): void;
    getVolume(): number;
    getPlaylist(): string[];
    getPlaylistIndex(): number;
}

interface YTEvent {
    target: YTPlayer;
    data: number;
}

// Extensión global para Window
declare global {
    interface Window {
        YT: {
            Player: new (id: string, config: any) => YTPlayer;
        };
        onYouTubeIframeAPIReady: () => void;
    }
}

// ==========================================
// 3. PROPS & CONFIGURACIÓN
// ==========================================
const props = defineProps<{
    initialCurrentCall: Call | null;
    initialHistory: Call[];
    settings: Settings;
    layoutConfig: { themeColor: string }; // Usado en CSS via v-bind
    currentServiceId?: number | null;
}>();

// Diccionario para mapear estados a estilos visuales y etiquetas
const statusMap: Record<string, { label: string; class: string }> = {
    waiting: { label: 'En Espera', class: 'text-slate-400' },
    calling: {
        label: 'Llamando...',
        class: 'text-theme animate-pulse font-black',
    },
    in_progress: { label: 'En Atención', class: 'text-green-500 font-bold' },
    completed: { label: 'Atendido', class: 'text-teal-500 font-medium' },
    no_show: { label: 'No Estuvo', class: 'text-red-400 font-medium' },
    transferred: { label: 'Derivado', class: 'text-yellow-400 font-medium' },
    default: { label: 'Procesando', class: 'text-slate-300' },
};

// Helper para obtener info del estado de manera segura
const getStatusInfo = (slug: string) => statusMap[slug] || statusMap['default'];

// Configuración Constante
const PLAYLIST_ID = 'PLE4XHNWe051PAx_y1jJUOwgc5VY0Q2L2F';

// ==========================================
// 4. ESTADO REACTIVO (REFS)
// ==========================================
// Datos del sistema
const currentCall = ref<Call | null>(props.initialCurrentCall);
const history = ref<Call[]>(props.initialHistory);

// UI & Control
const showModal = ref(false);
const modalData = ref<Call | null>(null);
const isFullscreen = ref(false);

// Multimedia
const audioPlayer = new Audio('/sounds/ding.mp3');
let player: YTPlayer | null = null; // Instancia YouTube tipada
let keepAliveInterval: ReturnType<typeof setInterval> | null = null; // Timer anti-suspensión

// Variable para controlar el temporizador del modal y poder cancelarlo
//const modalTimer: ReturnType<typeof setTimeout> | null = null;

// COLA DE LLAMADAS (QUEUE)
const callQueue = ref<Call[]>([]);
const isProcessingQueue = ref(false);

// ==========================================
// 5. CICLO DE VIDA (LIFECYCLE HOOKS)
// ==========================================
onMounted(() => {
    // A. Listener para cambios de pantalla completa
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });

    // B. Inicializar Reproductor de Video
    loadYoutubeAPI();

    // C. Inicializar WebSockets
    window.Echo.channel('kiosk')
        // 1. Evento: Operador llama a un ticket (Ruido + Modal)
        .listen('TicketCalled', (e: { call: Call }) => {
            handleNewCall(e.call);
        })
        // 2. Evento: Cambio de estado (Start/Finish/Abandon) -> Recarga silenciosa
        .listen('CallUpdated', () => {
            router.reload({
                only: ['initialCurrentCall', 'initialHistory'],
            });
        })
        // 3. Evento: Cambio de Settings () -> Recarga silenciosa
        .listen('SettingUpdated', () => {
            router.reload({
                only: ['settings', 'layoutConfig'],
            });
        });
});

onUnmounted(() => {
    // Limpieza de recursos al salir
    window.Echo.leave('kiosk');
    if (keepAliveInterval) clearInterval(keepAliveInterval);
    if (player && player.destroy) player.destroy();
    window.speechSynthesis.cancel(); // Detener voz al salir
});

// ==========================================
// 6. LÓGICA DE NEGOCIO (MANEJO DE LLAMADAS)
// ==========================================
const handleNewCall = (newCall: Call) => {
    // FILTRO: Si estamos en modo "Monitor por Servicio", ignoramos las llamadas de otros servicios
    if (
        props.currentServiceId &&
        // @ts-expect-error: El tipo Call por defecto no tiene 'ticket' definido
        newCall.ticket.service_id !== props.currentServiceId
    ) {
        return;
    }

    // Agregamos la llamada a la cola
    callQueue.value.push(newCall);
    // Intentamos procesar la cola
    processQueue();
};

const processQueue = async () => {
    // Si ya estamos procesando o no hay nada, salimos
    if (isProcessingQueue.value || callQueue.value.length === 0) return;

    isProcessingQueue.value = true;

    // Procesamos mientra haya items
    while (callQueue.value.length > 0) {
        // Sacamos el primero (FIFO)
        const nextCall = callQueue.value.shift();
        if (!nextCall) break;

        // 1. Ejecutar toda la lógica de presentación (Audio, Visual, TTS)
        await announceCall(nextCall);
    }

    isProcessingQueue.value = false;
};

const announceCall = async (newCall: Call) => {
    // A. Reproducir Audio "Ding"
    audioPlayer.currentTime = 0;
    audioPlayer.play().catch(() => {});

    // B. Actualizar Historial y Estado Visual
    if (currentCall.value && currentCall.value.id !== newCall.id) {
        history.value = history.value.filter(
            (h) => h.id !== currentCall.value?.id,
        );
        history.value.unshift(currentCall.value);
    }
    history.value = history.value.filter((h) => h.id !== newCall.id);
    if (history.value.length > 5) {
        history.value = history.value.slice(0, 5);
    }

    currentCall.value = newCall;
    modalData.value = newCall;

    // C. Mostrar Modal
    showModal.value = false;
    await nextTick(); // Reset animación
    showModal.value = true;

    // D. Voz (TTS)
    if (props.settings.voice_enabled) {
        speakTicket(newCall);
    }

    // E. Esperar el tiempo definido (6 segundos) antes de cerrar y pasar al siguiente
    await new Promise((resolve) => setTimeout(resolve, 6000));

    showModal.value = false;

    // Pequeño buffer visual entre llamadas (opcional, 500ms)
    await new Promise((resolve) => setTimeout(resolve, 500));
};

// Función auxiliar para buscar una voz específica
const seleccionarVoz = (preferencia: 'mujer' | 'hombre' = 'mujer') => {
    const voices = window.speechSynthesis.getVoices();

    const vocesMujer = [
        'Sabina',
        'Helena',
        'Monica',
        'Paulina',
        'Google español',
        'Laura',
    ];
    const vocesHombre = [
        'Pablo',
        'Jorge',
        'Microsoft Raul',
        'Google español de Estados Unidos',
    ];

    const buscar = preferencia === 'mujer' ? vocesMujer : vocesHombre;

    // 1. Intentar encontrar una voz que coincida con nuestros nombres preferidos y sea en español
    let vozElegida = voices.find(
        (voice) =>
            voice.lang.includes('es') &&
            buscar.some((nombre) => voice.name.includes(nombre)),
    );

    // 2. Si no encuentra ninguna de la lista, devolver la primera que sea español (fallback)
    if (!vozElegida) {
        vozElegida = voices.find((voice) => voice.lang.includes('es'));
    }

    return vozElegida;
};

const speakTicket = (call: Call) => {
    if (!('speechSynthesis' in window)) return;

    // IMPORTANTE: En Chrome, a veces las voces no están listas inmediatamente al cargar la página.
    // Asegúrate de que window.speechSynthesis.getVoices() no devuelva un array vacío antes de llamar a esto.

    // Texto: Añadimos pausas con comas para que suene más natural
    let text = '';
    if (call.ticket.client_name) {
        text = `${call.ticket.client_name}, Pase a ${call.counter?.name || 'Ventanilla'}`;
    } else {
        text = `Ticket ${call.token_letter || ''}, Pase a ${call.counter?.name || 'Ventanilla'}`;
    }

    const utterance = new SpeechSynthesisUtterance(text);

    // Configuración básica
    utterance.rate = 0.7;
    utterance.pitch = 1;

    // --- AQUÍ ASIGNAMOS LA VOZ ---
    const voice = seleccionarVoz('mujer');
    if (voice) {
        utterance.voice = voice;
        utterance.lang = voice.lang;
    }

    window.speechSynthesis.speak(utterance);
};

// ==========================================
// 7. INTEGRACIÓN YOUTUBE API
// ==========================================
const loadYoutubeAPI = () => {
    if (window.YT) {
        initPlayer();
    } else {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.body.appendChild(tag);
        window.onYouTubeIframeAPIReady = initPlayer;
    }
};

const initPlayer = () => {
    player = new window.YT.Player('youtube-player', {
        height: '100%',
        width: '100%',
        playerVars: {
            autoplay: 1,
            controls: 0,
            loop: 1,
            mute: 0, // Importante: Browsers policy
            listType: 'playlist',
            list: PLAYLIST_ID,
            origin: window.location.origin,
        },
        events: {
            onReady: onPlayerReady,
            onStateChange: onPlayerStateChange,
        },
    });
};

const onPlayerReady = (e: YTEvent) => {
    // Intentar activar sonido y reproducir
    e.target.unMute();
    e.target.setVolume(20);
    e.target.playVideo();

    // Intervalo "Keep-Alive" para evitar suspensión del navegador
    keepAliveInterval = setInterval(() => {
        if (player?.getVolume) player.setVolume(player.getVolume());
    }, 600000);
};

const onPlayerStateChange = (e: YTEvent) => {
    // Lógica de Loop Infinito Manual para Playlists
    if (e.data === 0) {
        // 0 = Ended
        if (!player) return;
        const isLastVideo =
            player.getPlaylistIndex() === player.getPlaylist().length - 1;
        if (isLastVideo) {
            player.playVideoAt(0);
        } else {
            player.nextVideo();
        }
    }
};

// ==========================================
// 8. UTILIDADES UI & WATCHERS
// ==========================================
const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
};

const formatTicket = (c: Call | null) => c?.ticket.ticket_number || '---';

// WATCHERS: Sincronización Reactiva con Inertia
// Cuando router.reload() trae nuevos datos, actualizamos los refs locales.
watch(
    () => props.initialCurrentCall,
    (newVal) => {
        currentCall.value = newVal;
    },
);

watch(
    () => props.initialHistory,
    (newVal) => {
        history.value = newVal;
    },
);
</script>

<template>
    <Head title="Monitor" />

    <div
        class="tv-layout flex h-screen w-screen flex-col overflow-hidden bg-slate-100 font-sans text-slate-800"
    >
        <div
            class="z-10 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-8 shadow-sm"
        >
            <div class="flex items-center gap-3">
                <div class="bg-theme rounded-lg p-2 text-white">
                    <Monitor class="h-6 w-6" />
                </div>
                <h1
                    class="text-xl font-bold tracking-tight text-slate-700 uppercase"
                >
                    Sistema de Atención
                </h1>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-sm font-medium text-slate-400">
                    {{ new Date().toLocaleDateString() }}
                </div>
                <button
                    @click="toggleFullscreen"
                    class="hover:text-theme rounded-full p-2 text-slate-400 transition-colors"
                >
                    <Maximize v-if="!isFullscreen" class="h-5 w-5" />
                    <Minimize v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div
            class="grid min-h-0 flex-1 grid-cols-12 gap-4 overflow-hidden p-4 lg:gap-6 lg:p-6"
        >
            <div
                class="relative col-span-4 flex h-full flex-col overflow-hidden rounded-3xl border-4 border-white bg-black shadow-2xl"
            >
                <div id="youtube-player" class="h-full w-full"></div>
            </div>

            <div class="col-span-4 flex h-full flex-col">
                <div
                    class="relative flex flex-1 flex-col items-center overflow-hidden rounded-3xl border border-slate-100 bg-white p-4 text-center shadow-xl"
                >
                    <div
                        class="bg-theme absolute top-0 left-0 h-3 w-full"
                    ></div>
                    <div
                        class="bg-theme absolute -right-10 -bottom-10 h-40 w-40 rounded-full opacity-5 blur-3xl"
                    ></div>

                    <div
                        v-if="currentCall"
                        class="relative z-10 flex h-full w-full flex-col justify-between py-4"
                    >
                        <div>
                            <span
                                class="inline-block rounded-full bg-slate-100 px-6 py-2 text-lg font-bold tracking-widest text-slate-600 uppercase"
                            >
                                Turno Actual
                            </span>
                        </div>

                        <div class="flex flex-1 items-center justify-center">
                            <h1
                                class="text-theme text-[7rem] leading-none font-black tracking-tighter drop-shadow-sm lg:text-[9rem]"
                            >
                                {{ formatTicket(currentCall) }}
                            </h1>
                        </div>

                        <div class="mb-6 flex h-12 items-center justify-center">
                            <span
                                class="text-4xl tracking-tight transition-colors duration-300 lg:text-5xl"
                                :class="
                                    getStatusInfo(currentCall.call_status.slug)
                                        .class
                                "
                                :style="{
                                    color: currentCall.call_status.color,
                                }"
                            >
                                {{
                                    getStatusInfo(currentCall.call_status.slug)
                                        .label
                                }}
                            </span>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <p
                                class="mb-1 text-sm font-bold tracking-wider text-slate-400 uppercase"
                            >
                                Pase a
                            </p>
                            <h2
                                class="text-4xl font-black text-slate-800 lg:text-5xl"
                            >
                                {{ currentCall.counter?.name || 'Ventanilla' }}
                            </h2>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex h-full flex-col items-center justify-center text-slate-300"
                    >
                        <div class="mb-6 rounded-full bg-slate-50 p-8">
                            <Monitor class="h-16 w-16 opacity-20" />
                        </div>
                        <p class="text-2xl font-medium">
                            Esperando llamadas...
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="col-span-4 flex h-full flex-col overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-lg"
            >
                <div
                    class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-slate-50 p-4"
                >
                    <h3 class="text-xl font-bold text-slate-600">
                        Últimos Llamados
                    </h3>
                    <span
                        class="rounded bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-600"
                        >Historial</span
                    >
                </div>

                <div class="flex h-full flex-1 flex-col overflow-hidden p-3">
                    <div
                        v-if="history.length > 0"
                        class="flex flex-1 flex-col gap-2"
                    >
                        <div
                            v-for="(call, index) in history"
                            :key="call.id"
                            class="relative flex min-h-0 flex-1 items-center justify-between overflow-hidden rounded-xl border border-slate-100 bg-white p-3 shadow-sm"
                        >
                            <div
                                class="absolute top-0 bottom-0 left-0 w-1.5 bg-slate-100"
                            ></div>

                            <div class="flex w-full items-center gap-3 pl-2">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-slate-50 text-base font-bold text-slate-400"
                                >
                                    {{ index + 1 }}
                                </div>
                                <div
                                    class="flex min-w-0 flex-1 flex-col items-start justify-center"
                                >
                                    <h4
                                        class="mb-0.5 text-2xl leading-none font-black text-slate-700 lg:text-3xl"
                                    >
                                        {{ formatTicket(call) }}
                                    </h4>
                                    <p
                                        class="w-full truncate text-xs font-bold tracking-wide text-slate-400 uppercase lg:text-sm"
                                    >
                                        {{ call.counter?.name }}
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <span
                                        class="block min-w-20 rounded-lg px-3 py-1.5 text-center text-[10px] font-bold tracking-wide text-white uppercase shadow-sm lg:text-xs"
                                        :style="{
                                            backgroundColor:
                                                call.call_status.color,
                                        }"
                                    >
                                        {{
                                            getStatusInfo(call.call_status.slug)
                                                .label
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center text-slate-300 opacity-50"
                    >
                        <p class="text-lg">Sin historial reciente</p>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-theme relative z-20 flex h-16 shrink-0 items-center overflow-hidden shadow-[0_-4px_20px_rgba(0,0,0,0.1)]"
        >
            <div
                class="pointer-events-none absolute inset-0 z-10 bg-linear-to-r from-blue-600 via-transparent to-blue-600"
            ></div>
            <div class="marquee-container w-full">
                <div
                    class="marquee-content"
                    :style="{
                        fontSize: settings.display_font_size + 'px',
                        color: settings.display_font_color,
                    }"
                >
                    <span class="mx-8 font-bold tracking-wide uppercase">
                        {{ settings.display_notification }}
                    </span>
                    <span
                        class="mx-8 font-bold tracking-wide uppercase opacity-50"
                        >•</span
                    >
                    <span class="mx-8 font-bold tracking-wide uppercase">
                        {{ settings.display_notification }}
                    </span>
                </div>
            </div>
        </div>

        <Transition name="zoom">
            <div
                v-if="showModal && modalData"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
            >
                <div
                    class="animate-bounce-in relative w-full max-w-2xl overflow-hidden rounded-3xl border-4 border-white bg-white shadow-2xl"
                >
                    <div
                        class="bg-theme absolute top-0 bottom-0 left-0 flex w-12 flex-col items-center justify-center gap-3"
                    >
                        <div class="h-1.5 w-1.5 rounded-full bg-white/40"></div>
                        <div class="h-1.5 w-1.5 rounded-full bg-white/40"></div>
                        <div class="h-1.5 w-1.5 rounded-full bg-white/40"></div>
                    </div>
                    <div class="p-8 pl-14 text-center">
                        <div
                            class="mb-4 inline-flex animate-pulse items-center gap-2 rounded-full bg-green-100 px-4 py-1.5 text-lg font-bold tracking-widest text-green-700 uppercase"
                        >
                            <Volume2 class="h-5 w-5" /> ¡Atención!
                        </div>
                        <h1
                            class="scale-up mb-2 text-[7rem] leading-none font-black tracking-tighter text-slate-800 drop-shadow-md"
                        >
                            {{ formatTicket(modalData) }}
                        </h1>
                        <p class="mb-6 text-xl font-bold text-slate-400">
                            Por favor acérquese a:
                        </p>
                        <div
                            class="bg-theme inline-block rotate-1 transform rounded-2xl border-2 border-white px-8 py-4 text-4xl font-black text-white shadow-lg ring-4 ring-blue-600/20"
                        >
                            {{ modalData.counter?.name || 'Ventanilla' }}
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* =========================================
   1. VARIABLES Y CLASES DINÁMICAS
   ========================================= */
/* Vinculación de variable CSS con la prop de Vue */
.tv-layout {
    --tv-color: v-bind('props.layoutConfig.themeColor');
}

/* Utilidades de tema */
.text-theme {
    color: var(--tv-color);
}

.bg-theme {
    background-color: var(--tv-color);
}

.border-theme {
    border-color: var(--tv-color);
}

.hover\:text-theme:hover {
    color: var(--tv-color);
}

/* =========================================
   2. ANIMACIONES CSS
   ========================================= */
/* Marquee (Texto Deslizante) */
.marquee-container {
    overflow: hidden;
    white-space: nowrap;
    position: relative;
}

.marquee-content {
    display: inline-block;
    padding-left: 100%;
    animation: marquee 25s linear infinite;
}

@keyframes marquee {
    0% {
        transform: translate(0, 0);
    }

    100% {
        transform: translate(-100%, 0);
    }
}

/* Transiciones Vue (Zoom/Fade) */
.zoom-enter-active,
.zoom-leave-active {
    transition: opacity 0.5s ease;
}

.zoom-enter-from,
.zoom-leave-to {
    opacity: 0;
}

/* Efecto de entrada "Bounce/Scale" para el modal */
.scale-up {
    animation: scaleUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes scaleUp {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }

    80% {
        transform: scale(1.1);
        opacity: 1;
    }

    100% {
        transform: scale(1);
    }
}
</style>
