<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/shared/components/ui/alert-dialog';
import { Button } from '@/shared/components/ui/button';
import { usePermissions } from '@/shared/composables/usePermissions';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { destroy } from '@/routes/counterAssignments';
import { Head, router } from '@inertiajs/vue3';
import {
    Briefcase,
    CheckCircle2,
    Clock,
    LogOut,
    Monitor,
} from 'lucide-vue-next';

const { can } = usePermissions();

const props = defineProps<{
    assignment: {
        id: number;
        counter: string;
        opened_at: string;
        services: Array<{ id: number; name: string }>;
    };
}>();

const closeSession = () => {
    router.delete(destroy.url({ counterAssignment: props.assignment.id }));
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Panel', href: '/dashboard' },
            { title: 'Asignación', href: '' },
        ]"
    >
        <Head title="Asignacion" />

        <div class="mx-auto max-w-4xl px-4 py-8">
            <div class="mb-10 text-center">
                <h2
                    class="mb-2 text-3xl font-bold tracking-tight text-foreground"
                >
                    Tu Asignación Activa
                </h2>
                <p class="text-muted-foreground">
                    Gestiona tu asignación o finaliza tu jornada laboral.
                </p>
            </div>

            <div class="grid gap-6">
                <div
                    class="relative overflow-hidden rounded-xl bg-linear-to-br from-blue-700 to-blue-600 p-8 text-white shadow-xl"
                >
                    <div
                        class="pointer-events-none absolute -top-10 -right-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"
                    ></div>

                    <div
                        class="relative z-10 flex flex-col items-center justify-between gap-6 md:flex-row"
                    >
                        <div class="flex items-center gap-6">
                            <div
                                class="rounded-2xl bg-white/20 p-4 backdrop-blur-sm"
                            >
                                <Monitor class="h-10 w-10 text-white" />
                            </div>
                            <div>
                                <p
                                    class="mb-1 flex items-center gap-2 font-medium text-blue-100"
                                >
                                    <Clock class="h-4 w-4" /> Iniciado a las
                                    {{ assignment.opened_at }}
                                </p>
                                <h3 class="text-4xl font-bold tracking-tight">
                                    {{ assignment.counter }}
                                </h3>
                                <div class="mt-2 flex items-center gap-2">
                                    <span
                                        class="h-2.5 w-2.5 animate-pulse rounded-full bg-green-400"
                                    ></span>
                                    <span
                                        class="text-sm font-medium text-blue-50"
                                        >En línea y disponible</span
                                    >
                                </div>
                            </div>
                        </div>

                        <AlertDialog>
                            <AlertDialogTrigger as-child>
                                <Button
                                    v-if="can('assignments.eliminar')"
                                    variant="destructive"
                                    size="lg"
                                    class="border-2 border-red-400/30 shadow-lg hover:bg-red-600"
                                >
                                    <LogOut class="mr-2 h-5 w-5" />
                                    Finalizar
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle
                                        >¿Finalizar turno?</AlertDialogTitle
                                    >
                                    <AlertDialogDescription>
                                        Esto liberará la ventanilla
                                        <strong>{{
                                            assignment.counter
                                        }}</strong>
                                        para que otros compañeros puedan usarla.
                                        Asegúrate de no tener tickets
                                        pendientes.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel
                                        >Cancelar</AlertDialogCancel
                                    >
                                    <AlertDialogAction
                                        @click="closeSession"
                                        class="bg-red-600 text-white hover:bg-red-700"
                                    >
                                        Sí, finalizar
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-border bg-card p-6 shadow-sm"
                >
                    <h4
                        class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground"
                    >
                        <Briefcase class="h-5 w-5 text-primary" />
                        Servicios Habilitados
                    </h4>

                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div
                            v-for="service in assignment.services"
                            :key="service.id"
                            class="flex items-center gap-3 rounded-lg border border-border bg-muted/30 p-3"
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary"
                            >
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            <span class="text-sm font-medium text-foreground">
                                {{ service.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
