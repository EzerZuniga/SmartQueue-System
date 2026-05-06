<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

// Íconos de Lucide
import { BreadcrumbItem } from '@/shared/types';
import {
    Activity,
    CheckCircle2,
    Clock,
    Megaphone,
    UserCheck,
    UserX,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
    { title: 'Estados de Atención', href: '/callStatuses' },
];

// 1. Interfaz del Modelo
interface CallStatus {
    id: number;
    name: string;
    slug: string;
    color: string;
    is_final?: boolean; // Si agregaste la migración sugerida
    description?: string; // Opcional si tienes descripción
}

defineProps<{
    callStatuses: CallStatus[];
}>();

// 2. Mapeo de Íconos según el slug
// Esto le da el toque profesional sin guardar el ícono en la BD
const getIcon = (slug: string) => {
    const map: Record<string, any> = {
        waiting: Clock,
        calling: Megaphone,
        in_progress: UserCheck,
        completed: CheckCircle2,
        no_show: UserX,
    };
    return map[slug] || Activity; // Activity por defecto
};

// Helper para saber si el texto debe ser oscuro o claro según el fondo
// (Simplificado: asumiendo que los colores de la BD son legibles)
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Estados de Atención" />

        <div class="mx-auto w-full max-w-7xl p-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 flex flex-col gap-2">
                <h2 class="text-2xl font-bold tracking-tight text-foreground">
                    Estados del Ciclo de Atención
                </h2>
                <p class="text-muted-foreground">
                    Estos son los estados por los que transita un ticket.
                </p>
            </div>

            <div
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="status in callStatuses"
                    :key="status.id"
                    class="relative overflow-hidden border-t-4 transition-all hover:shadow-md"
                    :style="{ borderTopColor: status.color }"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-lg font-bold text-neutral-800 dark:text-neutral-100"
                        >
                            {{ status.name }}
                        </CardTitle>

                        <div
                            class="rounded-full bg-neutral-100 p-2 dark:bg-neutral-800"
                            :style="{ color: status.color }"
                        >
                            <component
                                :is="getIcon(status.slug)"
                                class="h-5 w-5"
                            />
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div
                            class="mb-4 font-mono text-xs text-muted-foreground"
                        >
                            slug: {{ status.slug }}
                        </div>

                        <div
                            class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400"
                        >
                            <span
                                class="h-3 w-3 rounded-full shadow-sm"
                                :style="{ backgroundColor: status.color }"
                            ></span>
                            <span
                                class="text-xs tracking-wider uppercase opacity-70"
                            >
                                Identificador Visual
                            </span>
                        </div>
                    </CardContent>

                    <CardFooter class="pt-2">
                        <Badge
                            variant="outline"
                            class="w-full justify-center py-1 font-normal"
                            :class="
                                status.is_final
                                    ? 'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-800 dark:bg-neutral-900'
                                    : 'border-blue-100 bg-blue-50 text-blue-700 dark:border-blue-900/30 dark:bg-blue-900/10 dark:text-blue-400'
                            "
                        >
                            {{
                                status.is_final
                                    ? 'Estado Final (Cierra Ciclo)'
                                    : 'En Proceso'
                            }}
                        </Badge>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
