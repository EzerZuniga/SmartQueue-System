<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/counterAssignments';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Monitor, Users } from 'lucide-vue-next';

// Importamos componentes para los Avatares
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

defineProps<{
    counters: Array<{
        id: number;
        name: string;
        current_operators: Array<{
            name: string;
            initials: string;
            image?: string;
        }>;
    }>;
    services: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    counter_id: null as number | null,
    services: [] as number[],
});

const toggleService = (id: number) => {
    if (form.services.includes(id)) {
        form.services = form.services.filter((s) => s !== id);
    } else {
        form.services.push(id);
    }
};

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Asignación', href: index.url() },
            { title: 'Crear', href: '' },
        ]"
    >
        <Head title="Crear Asignacion" />

        <div class="mx-auto max-w-5xl px-4 py-8">
            <h2
                class="mb-2 text-center text-3xl font-bold tracking-tight text-foreground"
            >
                Crea tu Asignación
            </h2>
            <p class="mb-10 text-center text-muted-foreground">
                Selecciona tu ubicación y los servicios para iniciar la
                atención.
            </p>

            <form @submit.prevent="submit" class="grid gap-10">
                <div class="space-y-5">
                    <h3
                        class="flex items-center gap-3 text-lg font-semibold text-foreground"
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground shadow-sm"
                            >1</span
                        >
                        Selecciona tu Ventanilla
                    </h3>

                    <div
                        class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div
                            v-for="counter in counters"
                            :key="counter.id"
                            @click="form.counter_id = counter.id"
                            class="group relative flex cursor-pointer flex-col justify-between overflow-hidden rounded-xl border-2 p-5 transition-all duration-300"
                            :class="
                                form.counter_id === counter.id
                                    ? 'scale-[1.02] border-primary bg-primary text-primary-foreground shadow-xl'
                                    : 'border-border bg-card text-card-foreground hover:border-primary/50 hover:shadow-lg'
                            "
                        >
                            <div
                                v-if="form.counter_id === counter.id"
                                class="pointer-events-none absolute -top-6 -right-6 h-32 w-32 rounded-full bg-white/10 blur-3xl"
                            ></div>

                            <div
                                class="relative z-10 mb-3 flex items-start justify-between"
                            >
                                <div
                                    class="rounded-lg p-2.5 transition-colors"
                                    :class="
                                        form.counter_id === counter.id
                                            ? 'bg-white/20 text-white'
                                            : 'bg-muted text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary'
                                    "
                                >
                                    <Monitor class="h-6 w-6" />
                                </div>

                                <div
                                    v-if="counter.current_operators.length > 0"
                                    class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase backdrop-blur-sm transition-colors"
                                    :class="
                                        form.counter_id === counter.id
                                            ? 'border border-amber-500/30 bg-amber-500/20 text-amber-50'
                                            : 'border border-amber-200 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                    "
                                >
                                    <Users class="h-3 w-3" />
                                    {{ counter.current_operators.length }}
                                    Activo(s)
                                </div>
                                <div
                                    v-else
                                    class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase backdrop-blur-sm transition-colors"
                                    :class="
                                        form.counter_id === counter.id
                                            ? 'border border-emerald-400/30 bg-emerald-400/20 text-emerald-50'
                                            : 'border border-emerald-200 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    "
                                >
                                    Disponible
                                </div>
                            </div>

                            <div
                                class="relative z-10 mb-2 text-lg font-bold"
                                :class="
                                    form.counter_id === counter.id
                                        ? 'text-white'
                                        : 'text-foreground'
                                "
                            >
                                {{ counter.name }}
                            </div>

                            <div class="relative z-10 mt-auto">
                                <div
                                    v-if="counter.current_operators.length > 0"
                                    class="flex items-center gap-3"
                                >
                                    <div
                                        class="flex -space-x-2.5 overflow-hidden py-1"
                                    >
                                        <Avatar
                                            v-for="(
                                                op, idx
                                            ) in counter.current_operators.slice(
                                                0,
                                                3,
                                            )"
                                            :key="idx"
                                            class="inline-block h-8 w-8 border-2 transition-colors"
                                            :class="
                                                form.counter_id === counter.id
                                                    ? 'border-primary'
                                                    : 'border-card'
                                            "
                                        >
                                            <AvatarFallback
                                                class="text-[10px] font-bold"
                                                :class="
                                                    form.counter_id ===
                                                    counter.id
                                                        ? 'bg-primary-foreground text-primary'
                                                        : 'bg-muted text-muted-foreground'
                                                "
                                            >
                                                {{ op.initials }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <div
                                            v-if="
                                                counter.current_operators
                                                    .length > 3
                                            "
                                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-[10px] font-bold"
                                            :class="
                                                form.counter_id === counter.id
                                                    ? 'border-primary bg-primary-foreground text-primary'
                                                    : 'border-card bg-muted text-muted-foreground'
                                            "
                                        >
                                            +{{
                                                counter.current_operators
                                                    .length - 3
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="text-xs font-medium"
                                    :class="
                                        form.counter_id === counter.id
                                            ? 'text-blue-100'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    Libre
                                </div>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.counter_id"
                        class="mt-2 text-sm font-medium text-destructive"
                    >
                        {{ form.errors.counter_id }}
                    </p>
                </div>

                <div
                    v-if="form.counter_id"
                    class="animate-in space-y-5 border-t border-border pt-4 duration-500 fade-in slide-in-from-top-4"
                >
                    <h3
                        class="flex items-center gap-3 text-lg font-semibold text-foreground"
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground shadow-sm"
                            >2</span
                        >
                        ¿Qué trámites atenderás?
                    </h3>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div
                            v-for="service in services"
                            :key="service.id"
                            @click="toggleService(service.id)"
                            class="flex cursor-pointer items-center justify-between rounded-xl border-2 p-4 transition-all duration-200"
                            :class="
                                form.services.includes(service.id)
                                    ? 'scale-[1.01] border-foreground bg-foreground text-background shadow-lg'
                                    : 'border-border bg-card text-card-foreground hover:border-muted-foreground/30 hover:bg-muted/50'
                            "
                        >
                            <span class="text-sm font-bold tracking-wide">{{
                                service.name
                            }}</span>

                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-full border transition-all"
                                :class="
                                    form.services.includes(service.id)
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-muted-foreground/30 bg-transparent'
                                "
                            >
                                <CheckCircle2
                                    v-if="form.services.includes(service.id)"
                                    class="h-4 w-4"
                                />
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.services"
                        class="text-sm font-medium text-destructive"
                    >
                        {{ form.errors.services }}
                    </p>
                </div>

                <div class="flex justify-end pt-6" v-if="form.counter_id">
                    <Button
                        size="lg"
                        class="px-8 text-base shadow-lg shadow-primary/20"
                        :disabled="
                            form.processing || form.services.length === 0
                        "
                    >
                        <Spinner v-if="form.processing" class="mr-2" />
                        Iniciar Turno
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
