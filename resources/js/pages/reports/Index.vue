<script setup lang="ts">
import DateRangePicker from '@/components/DateRangePicker.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { getLocalTimeZone, today } from '@internationalized/date';
import type { DateRange } from 'radix-vue';
import { ref, type Ref } from 'vue';
import CallsTable from './Partials/CallsTable.vue';
import PerformanceTable from './Partials/PerformanceTable.vue';
import TicketsTable from './Partials/TicketsTable.vue';

// Estado del filtro de fechas (Por defecto: Hoy)
const dateRange = ref({
    start: today(getLocalTimeZone()),
    end: today(getLocalTimeZone()),
}) as Ref<DateRange>;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
    { title: 'Reportes', href: '/reports' },
];
</script>

<template>
    <Head title="Reportes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2
                class="text-xl leading-tight font-semibold text-gray-800 dark:text-gray-200"
            >
                Reportes y Estadísticas
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-5">
                <div class="flex flex-col gap-6">
                    <!-- Filtros Globales -->
                    <div
                        class="flex items-center justify-between rounded-lg border bg-card p-4 shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <span
                                class="text-sm font-medium text-muted-foreground"
                                >Rango de Fechas:</span
                            >
                            <DateRangePicker v-model="dateRange" />
                        </div>
                    </div>

                    <!-- Tabs Reportes -->
                    <Tabs default-value="tickets" class="w-full">
                        <TabsList class="grid w-full grid-cols-3">
                            <TabsTrigger value="tickets">
                                Tickets Generados
                            </TabsTrigger>
                            <TabsTrigger value="calls">
                                Atención (Llamadas)
                            </TabsTrigger>
                            <TabsTrigger value="performance">
                                Rendimiento
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="tickets" class="mt-4 space-y-4">
                            <TicketsTable :range="dateRange" />
                        </TabsContent>

                        <TabsContent value="calls" class="mt-4 space-y-4">
                            <CallsTable :range="dateRange" />
                        </TabsContent>

                        <TabsContent value="performance" class="mt-4 space-y-4">
                            <PerformanceTable :range="dateRange" />
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
