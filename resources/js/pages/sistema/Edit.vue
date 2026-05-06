<script setup lang="ts">
import InputError from '@/shared/components/InputError.vue';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';
import { usePermissions } from '@/shared/composables/usePermissions';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { regenerate_token, update } from '@/routes/settings';
import { Head, router, useForm } from '@inertiajs/vue3'; // Agregamos router
import {
    Building2, // Icono Copiar
    Check, // Icono Copiado

    // Icono Regenerar
    Copy,
    Megaphone,
    Monitor,
    Palette,
    Printer, // Icono Seguridad
    RefreshCw,
    Save,
    ShieldCheck,
    Upload,
} from 'lucide-vue-next';
import { ref } from 'vue';

const { can } = usePermissions();

interface Settings {
    id: number;
    name: string;
    address: string | null;
    email: string | null;
    phone: string | null;
    location: string | null;
    logo_path: string | null;
    footer_text: string | null;
    theme_color: string;
    display_notification: string | null;
    display_font_size: number;
    display_font_color: string;
    print_preview_enabled: boolean;
    voice_enabled: boolean;
    kiosk_token: string;
    kiosk_code: string;
    ticket_cooldown_minutes: number;
}

const props = defineProps<{ settings: Settings }>();

// ... (El resto de tu form setup se mantiene igual)
const form = useForm({
    _method: 'put',
    name: props.settings.name,
    address: props.settings.address || '',
    email: props.settings.email || '',
    phone: props.settings.phone || '',
    location: props.settings.location || '',
    logo: null as File | null,
    footer_text: props.settings.footer_text || '',
    theme_color: props.settings.theme_color,
    display_notification: props.settings.display_notification || '',
    display_font_size: props.settings.display_font_size,
    display_font_color: props.settings.display_font_color,
    print_preview_enabled: !!props.settings.print_preview_enabled,
    voice_enabled: !!props.settings.voice_enabled,
    kiosk_code: props.settings.kiosk_code || '',
    ticket_cooldown_minutes: props.settings.ticket_cooldown_minutes || 10,
});

const logoPreview = ref<string | null>(
    props.settings.logo_path ? `/storage/${props.settings.logo_path}` : null,
);

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo = target.files[0];
        logoPreview.value = URL.createObjectURL(target.files[0]);
    }
};

const submit = () => {
    form.post(update.url(), {
        preserveScroll: true,
    });
};

// --- LÓGICA DE SEGURIDAD ---
const regenerating = ref(false);
const copied = ref(false);

const regenerateToken = () => {
    regenerating.value = true;
    router.post(
        regenerate_token.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => (regenerating.value = false),
        },
    );
};

const copyToClipboard = () => {
    navigator.clipboard.writeText(
        props.settings.kiosk_token ? props.settings.kiosk_token : '',
    );
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Panel', href: '/dashboard' },
            { title: 'Sistema', href: '' },
        ]"
    >
        <Head title="Configuración General" />

        <div class="mx-auto max-w-6xl px-4 py-8">
            <div
                class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <h2
                        class="text-3xl font-bold tracking-tight text-foreground"
                    >
                        Ajustes del Sistema
                    </h2>
                    <p class="text-muted-foreground">
                        Personaliza la información y apariencia de tu
                        plataforma.
                    </p>
                </div>
                <Button
                    v-if="can('settings.editar')"
                    @click="submit"
                    :disabled="form.processing"
                    size="lg"
                    class="shadow-lg shadow-primary/20"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    <Save class="mr-2 h-4 w-4" v-else />
                    Guardar Cambios
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="space-y-8 lg:col-span-2">
                    <div
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <div
                            class="mb-6 flex items-center gap-3 border-b border-border pb-4"
                        >
                            <div
                                class="rounded-lg bg-primary/10 p-2 text-primary"
                            >
                                <Building2 class="h-5 w-5" />
                            </div>
                            <h3 class="text-lg font-bold text-foreground">
                                Datos de la Organización
                            </h3>
                        </div>
                        <div class="grid gap-5">
                            <div class="grid gap-2">
                                <Label for="name">Nombre de la Empresa</Label>
                                <Input id="name" v-model="form.name" />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="email"
                                        >Correo de Contacto</Label
                                    >
                                    <Input
                                        id="email"
                                        type="email"
                                        v-model="form.email"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="phone">Teléfono</Label>
                                    <Input id="phone" v-model="form.phone" />
                                    <InputError :message="form.errors.phone" />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="address">Dirección Física</Label>
                                <Input id="address" v-model="form.address" />
                                <InputError :message="form.errors.address" />
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <div
                            class="mb-6 flex items-center gap-3 border-b border-border pb-4"
                        >
                            <div
                                class="rounded-lg bg-primary/10 p-2 text-primary"
                            >
                                <Monitor class="h-5 w-5" />
                            </div>
                            <h3 class="text-lg font-bold text-foreground">
                                Pantalla de Turnos (TV)
                            </h3>
                        </div>
                        <div class="grid gap-5">
                            <div class="grid gap-2">
                                <Label for="notification"
                                    >Mensaje de Cintillo</Label
                                >
                                <Input
                                    id="notification"
                                    v-model="form.display_notification"
                                    placeholder="Ej: Bienvenidos..."
                                />
                                <InputError
                                    :message="form.errors.display_notification"
                                />
                            </div>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label for="font_size"
                                        >Tamaño de Fuente (px)</Label
                                    >
                                    <Input
                                        id="font_size"
                                        type="number"
                                        v-model="form.display_font_size"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="font_color"
                                        >Color de Fuente</Label
                                    >
                                    <div class="flex gap-2">
                                        <Input
                                            id="font_color"
                                            type="color"
                                            v-model="form.display_font_color"
                                            class="h-10 w-12 cursor-pointer p-1"
                                        />
                                        <Input
                                            v-model="form.display_font_color"
                                            class="flex-1"
                                        />
                                    </div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="theme_color"
                                        >Color del Tema</Label
                                    >
                                    <div class="flex gap-2">
                                        <Input
                                            id="theme_color"
                                            type="color"
                                            v-model="form.theme_color"
                                            class="h-10 w-12 cursor-pointer p-1"
                                        />
                                        <Input
                                            v-model="form.theme_color"
                                            class="flex-1"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-amber-200 bg-amber-50/50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950/10"
                    >
                        <div
                            class="mb-6 flex items-center gap-3 border-b border-amber-200/50 pb-4 dark:border-amber-900/50"
                        >
                            <div
                                class="rounded-lg bg-amber-100 p-2 text-amber-600 dark:bg-amber-900 dark:text-amber-400"
                            >
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <h3 class="text-lg font-bold text-foreground">
                                Seguridad
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div class="grid gap-2">
                                <Label>Token privado de acceso al Kiosco</Label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <Input
                                            readonly
                                            :model-value="
                                                props.settings.kiosk_token
                                            "
                                            class="cursor-pointer bg-white pr-12 font-mono text-xs text-muted-foreground shadow-inner dark:bg-neutral-950"
                                        />
                                        <div
                                            class="pointer-events-none absolute inset-y-0 right-0 w-12 from-white to-transparent dark:from-neutral-950"
                                        ></div>
                                    </div>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="copyToClipboard"
                                        title="Copiar enlace"
                                    >
                                        <Check
                                            v-if="copied"
                                            class="h-4 w-4 text-green-600"
                                        />
                                        <Copy v-else class="h-4 w-4" />
                                    </Button>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Este es un token de
                                    <strong>200 caracteres</strong>. Usado para
                                    proteger la ruta de los Kioscos.
                                </p>
                            </div>

                            <div
                                class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-900/20"
                            >
                                <div
                                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                                >
                                    <div
                                        class="text-sm text-red-800 dark:text-red-300"
                                    >
                                        <strong>Zona de Peligro:</strong> Si
                                        regeneras el token, todos los kioscos
                                        conectados actualmente dejarán de
                                        funcionar hasta que actualices su URL.
                                    </div>
                                    <Button
                                        v-if="can('settings.editar')"
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        @click="regenerateToken"
                                        :disabled="regenerating"
                                    >
                                        <RefreshCw
                                            class="mr-2 h-4 w-4"
                                            :class="{
                                                'animate-spin': regenerating,
                                            }"
                                        />
                                        Regenerar Token
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <div
                            class="mb-6 flex items-center gap-3 border-b border-border pb-4"
                        >
                            <div
                                class="rounded-lg bg-primary/10 p-2 text-primary"
                            >
                                <Palette class="h-5 w-5" />
                            </div>
                            <h3 class="text-lg font-bold text-foreground">
                                Apariencia Kiosko
                            </h3>
                        </div>
                        <div class="grid gap-6">
                            <div class="grid gap-3">
                                <Label>Fondo del Kiosco</Label>
                                <div
                                    class="flex flex-col items-center gap-4 rounded-xl border-2 border-dashed border-input p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div
                                        class="flex h-24 w-auto max-w-[200px] items-center justify-center overflow-hidden rounded border bg-white p-2"
                                    >
                                        <img
                                            v-if="logoPreview"
                                            :src="logoPreview"
                                            class="h-full w-full object-contain"
                                        />
                                        <span
                                            v-else
                                            class="text-xs text-muted-foreground"
                                            >Sin logo</span
                                        >
                                    </div>
                                    <label
                                        for="logo"
                                        class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary hover:underline"
                                    >
                                        <Upload class="h-4 w-4" /> Subir nueva
                                        imagen
                                    </label>
                                    <input
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleLogoChange"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="footer"
                                    >Texto del Pie de Página - Ticket</Label
                                >
                                <Input id="footer" v-model="form.footer_text" />
                            </div>
                            <div class="grid gap-2 border-t border-border pt-4">
                                <Label for="kiosk_code"
                                    >Código Maestro (Kiosco)</Label
                                >
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="kiosk_code"
                                        v-model="form.kiosk_code"
                                        placeholder="Ej: 1234"
                                        maxlength="20"
                                        class="max-w-[200px]"
                                    />
                                    <p
                                        class="flex-1 text-xs text-muted-foreground"
                                    >
                                        Código para saltar validación de
                                        documento en Kiosco.
                                    </p>
                                </div>
                                <InputError :message="form.errors.kiosk_code" />
                            </div>

                            <!-- Control de Tiempo -->
                            <div class="grid gap-2 border-t border-border pt-4">
                                <Label for="cooldown"
                                    >Tiempo de Espera entre Turnos
                                    (minutos)</Label
                                >
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="cooldown"
                                        type="number"
                                        min="0"
                                        v-model="form.ticket_cooldown_minutes"
                                        class="max-w-[100px]"
                                    />
                                    <p
                                        class="flex-1 text-xs text-muted-foreground"
                                    >
                                        Tiempo mínimo que debe esperar un
                                        usuario (con el mismo documento) para
                                        sacar otro turno.
                                    </p>
                                </div>
                                <InputError
                                    :message="
                                        form.errors.ticket_cooldown_minutes
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-border bg-card p-6 shadow-sm"
                    >
                        <div
                            class="mb-6 flex items-center gap-3 border-b border-border pb-4"
                        >
                            <h3 class="text-lg font-bold text-foreground">
                                Opciones
                            </h3>
                        </div>
                        <div class="space-y-4">
                            <div
                                class="flex items-center justify-between rounded-lg border border-border bg-card p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <Printer
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <Label for="print" class="cursor-pointer"
                                        >Impresión de Tickets</Label
                                    >
                                </div>
                                <input
                                    type="checkbox"
                                    id="print"
                                    v-model="form.print_preview_enabled"
                                    class="h-4 w-4 rounded border-primary text-primary focus:ring-primary"
                                />
                            </div>
                            <div
                                class="flex items-center justify-between rounded-lg border border-border bg-card p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <Megaphone
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <Label for="voice" class="cursor-pointer"
                                        >Llamado por Voz</Label
                                    >
                                </div>
                                <input
                                    type="checkbox"
                                    id="voice"
                                    v-model="form.voice_enabled"
                                    class="h-4 w-4 rounded border-primary text-primary focus:ring-primary"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
