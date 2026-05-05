<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    index,
    markAsRead as marcarComoLeido,
    updatePreferences,
} from '@/routes/notifications';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell, Check } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Notification {
    id: string;
    type: string;
    data: any;
    created_at: string;
    read_at: string | null;
}

const notifications = ref<Notification[]>([]);
const unreadCount = computed(() => notifications.value.length);
const open = ref(false);

// Obtenemos el usuario actual de Inertia
const page = usePage();
const user = computed(() => page.props.auth.user);

const fetchNotifications = async () => {
    try {
        const { data } = await axios.get(index.url());
        notifications.value = data;
    } catch (e) {
        console.error('Error fetching notifications', e);
    }
};

const preferences = ref({
    sound: user.value?.preferences?.sound ?? true,
    browser: user.value?.preferences?.browser ?? true,
});

const savePreferences = async () => {
    try {
        await axios.post(updatePreferences.url(), preferences.value);
    } catch (e) {
        console.error('Error guardando preferencias', e);
    }
};

const toggleSound = (val: boolean) => {
    preferences.value.sound = val;
    savePreferences();
};

const toggleBrowser = (val: boolean) => {
    if (val) requestNotificationPermission();
    preferences.value.browser = val;
    savePreferences();
};

const markAsRead = async (id: string) => {
    try {
        notifications.value = notifications.value.filter((n) => n.id !== id);
        await axios.post(marcarComoLeido.url(id));
        // Refrescamos para traer nuevas si hay pendientes
        fetchNotifications();
    } catch (e) {
        console.error('Error al marcar como leída', e);
        fetchNotifications();
    }
};

const formatTime = (dateString: string) => {
    return new Date(dateString)
        .toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        })
        .toLowerCase();
};

const playNotificationSound = () => {
    // Asegúrate de que este archivo exista en tu carpeta /public/sounds/
    const audio = new Audio('/sounds/ding-2.mp3');

    // El .catch captura errores si el navegador bloquea el autoplay
    audio.play().catch((error) => {
        console.warn(
            'No se pudo reproducir el sonido (Autoplay policy):',
            error,
        );
    });
};

const requestNotificationPermission = () => {
    if (!('Notification' in window)) {
        console.warn('Este navegador no soporta notificaciones de escritorio.');
        return;
    }

    if (
        Notification.permission !== 'granted' &&
        Notification.permission !== 'denied'
    ) {
        Notification.requestPermission();
    }
};

const showNativeNotification = (title: string, body: string) => {
    if (Notification.permission === 'granted') {
        const notification = new Notification(title, {
            body: body,
            icon: '/apple-touch-icon.png',
        });

        notification.onclick = () => {
            window.focus();
            notification.close();
        };
    }
};

onMounted(() => {
    fetchNotifications();
    requestNotificationPermission();

    // Escuchamos las notificaciones de Broadcast
    window.Echo.private(`App.Models.User.${user.value.id}`).notification(
        (notification: any) => {
            notifications.value.unshift({
                id: notification.id,
                type: notification.type,
                data: notification,
                created_at: new Date().toISOString(),
                read_at: null,
            });

            // Mantenemos solo las últimas 5 en la lista visual
            if (notifications.value.length > 4) {
                notifications.value.pop();
            }

            // Reproducir sonido si está habilitado
            if (preferences.value.sound) {
                playNotificationSound();
            }

            // Mostrar notificación nativa si está habilitado
            if (preferences.value.browser) {
                showNativeNotification(
                    'Sistema de Colas',
                    notification.message || 'Tienes una nueva notificación',
                );
            }
        },
    );
});

onUnmounted(() => {
    // Limpiamos el listener de Broadcast al desmontar el componente
    if (window.Echo && user.value) {
        window.Echo.leave(`App.Models.User.${user.value.id}`);
    }
});
</script>

<template>
    <DropdownMenu v-model:open="open">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative text-muted-foreground hover:text-foreground"
            >
                <Bell class="h-5 w-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute top-1.5 right-1.5 flex h-2 min-h-[8px] w-2 min-w-[8px] items-center justify-center rounded-full bg-red-600 ring-2 ring-background"
                >
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-80" align="end">
            <DropdownMenuLabel
                class="flex items-center justify-between font-normal"
            >
                <span class="font-semibold">Notificaciones</span>
                <span
                    v-if="unreadCount > 0"
                    class="text-xs text-muted-foreground"
                    >{{ unreadCount }} nuevas</span
                >
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <!-- Configuración Rápida -->
            <div class="px-2 py-2">
                <div class="flex items-center justify-between py-1" @click.stop>
                    <span class="text-xs font-medium text-muted-foreground"
                        >Sonido</span
                    >
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            :checked="preferences.sound"
                            @change="
                                toggleSound(
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        <div
                            class="peer inline-flex h-[24px] w-[44px] shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent bg-zinc-200 transition-colors peer-checked:bg-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input dark:bg-zinc-700 dark:peer-checked:bg-primary"
                        >
                            <span
                                class="pointer-events-none block h-5 w-5 translate-x-0 rounded-full bg-background shadow-lg ring-0 transition-transform peer-checked:translate-x-5"
                            ></span>
                        </div>
                    </label>
                </div>
                <div class="flex items-center justify-between py-1" @click.stop>
                    <span class="text-xs font-medium text-muted-foreground"
                        >Alertas PC</span
                    >
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            :checked="preferences.browser"
                            @change="
                                toggleBrowser(
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        <div
                            class="peer inline-flex h-[24px] w-[44px] shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent bg-zinc-200 transition-colors peer-checked:bg-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input dark:bg-zinc-700 dark:peer-checked:bg-primary"
                        >
                            <span
                                class="pointer-events-none block h-5 w-5 translate-x-0 rounded-full bg-background shadow-lg ring-0 transition-transform peer-checked:translate-x-5"
                            ></span>
                        </div>
                    </label>
                </div>
            </div>

            <DropdownMenuSeparator />
            <div class="max-h-[300px] overflow-y-auto">
                <div
                    v-if="notifications.length === 0"
                    class="p-4 text-center text-sm text-muted-foreground"
                >
                    No tienes notificaciones.
                </div>

                <template v-else>
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="group relative border-b p-3 transition-colors last:border-0 hover:bg-muted/50"
                    >
                        <div class="pr-6">
                            <p class="mb-1 text-sm leading-none font-medium">
                                {{
                                    notification.data.message ||
                                    'Nueva notificación'
                                }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatTime(notification.created_at) }}
                            </p>
                        </div>
                        <button
                            @click.stop="markAsRead(notification.id)"
                            class="absolute top-3 right-3 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-primary"
                            title="Marcar como leída"
                        >
                            <Check class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </template>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
