<script setup lang="ts">
import DemandChart from '@/components/dashboard/DemandChart.vue';
import DistributionChart from '@/components/dashboard/DistributionChart.vue';
import OperatorGrid from '@/components/dashboard/OperatorGrid.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { stats as StatsRoute } from '@/routes/dashboard';
import { type BreadcrumbItem } from '@/types';
import { DashboardStats } from '@/types/dashboard';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    initialStats: DashboardStats;
}>();

const stats = ref(props.initialStats);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel',
        href: dashboard().url,
    },
];

const reloadStats = async () => {
    try {
        const response = await axios.get(StatsRoute.url());
        stats.value = response.data;
    } catch (error) {
        console.error('Error refreshing stats:', error);
    }
};

let echoInstance: any = null;

onMounted(() => {
    if (window.Echo) {
        echoInstance = window.Echo.channel('dashboard')
            .listen('TicketCreated', () => {
                reloadStats();
            })
            .listen('CallUpdated', () => {
                reloadStats();
            })
            .listen('DashboardStatUpdated', () => {
                reloadStats();
            });
    }
});

onUnmounted(() => {
    if (echoInstance) {
        echoInstance.stopListening('TicketCreated');
        echoInstance.stopListening('CallUpdated');
        echoInstance.stopListening('DashboardStatUpdated');
    }
});
</script>

<template>
    <Head title="Panel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <!-- KPIs -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="En Espera"
                    :value="stats.kpis.waiting_count"
                    description="Personas esperando ahora"
                    :color="
                        stats.kpis.waiting_count > 30
                            ? 'text-red-500'
                            : 'text-foreground'
                    "
                />
                <StatCard
                    title="Tiempo Promedio"
                    :value="stats.kpis.avg_wait_time"
                    description="Promedio de espera hoy"
                />
                <StatCard
                    title="Ventanillas Activas"
                    :value="stats.kpis.active_counters"
                    description="Cajeros conectados"
                />
                <StatCard
                    title="Tasa de Abandono"
                    :value="stats.kpis.abandonment_rate"
                    description="Deserción hoy (No Show)"
                    :color="
                        parseInt(stats.kpis.abandonment_rate) > 15
                            ? 'text-red-500'
                            : 'text-green-500'
                    "
                />
            </div>

            <!-- Main Content + Sidebar Layout -->
            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- Main Content -->
                <div class="min-w-0 flex-1 space-y-4">
                    <!-- Charts -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-7">
                        <div class="col-span-1 lg:col-span-4">
                            <DemandChart :data="stats.charts.demand" />
                        </div>

                        <div class="col-span-1 lg:col-span-3">
                            <DistributionChart
                                :data="stats.charts.distribution"
                            />
                        </div>
                    </div>

                    <!-- Operator Grid -->
                    <div>
                        <OperatorGrid :operators="stats.operators" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
