<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import reportsRoute from '@/routes/reports';
import axios from 'axios';
import { Clock, FileSpreadsheet, RefreshCcw } from 'lucide-vue-next';
import type { DateRange } from 'radix-vue';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    range: DateRange;
}>();

interface PerformanceMetric {
    user_id: number;
    total_calls: number;
    avg_served_time: string; // "0.0000" from SQL DB raw
    user: {
        id: number;
        name: string;
    } | null;
}

const loading = ref(false);
const rows = ref<PerformanceMetric[]>([]);

const formatDuration = (seconds: string | number) => {
    const sec = Math.round(Number(seconds));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;

    if (h > 0) return `${h}h ${m}m ${s}s`;
    return `${m}m ${s}s`;
};

const fetchData = async () => {
    if (!props.range.start || !props.range.end) return;

    loading.value = true;
    try {
        const response = await axios.get(
            reportsRoute.performance.url({
                query: {
                    start_date: props.range.start.toString(),
                    end_date: props.range.end.toString(),
                },
            }),
        );
        // El endpoint devuelve el array directamente, no paginado
        rows.value = response.data;
    } catch (e) {
        console.error('Error fetching performance stats', e);
    } finally {
        loading.value = false;
    }
};

const downloadExcel = () => {
    if (!props.range.start || !props.range.end) return;
    const url = reportsRoute.performance.export.url({
        query: {
            start_date: props.range.start.toString(),
            end_date: props.range.end.toString(),
        },
    });
    window.open(url, '_blank');
};

watch(
    () => props.range,
    () => {
        fetchData();
    },
    { deep: true },
);

onMounted(() => {
    fetchData();
});
</script>

<template>
    <div class="rounded-md border bg-card shadow-sm">
        <div class="flex items-center justify-between border-b p-4">
            <h3 class="text-lg font-medium">Rendimiento por Operador</h3>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="fetchData()"
                    :disabled="loading"
                >
                    <RefreshCcw
                        class="mr-2 h-4 w-4"
                        :class="{ 'animate-spin': loading }"
                    />
                    Actualizar
                </Button>
                <Button variant="secondary" size="sm" @click="downloadExcel">
                    <FileSpreadsheet class="mr-2 h-4 w-4 text-green-600" />
                    Exportar Excel
                </Button>
            </div>
        </div>

        <div class="p-0">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Operador</TableHead>
                        <TableHead class="text-center"
                            >Total Atendidos</TableHead
                        >
                        <TableHead class="text-center"
                            >Tiempo Promedio (Atención)</TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 3" :key="i">
                            <TableCell>
                                <Skeleton class="h-4 w-[150px]" />
                            </TableCell>
                            <TableCell class="text-center">
                                <Skeleton class="mx-auto h-4 w-[60px]" />
                            </TableCell>
                            <TableCell class="text-center">
                                <Skeleton class="mx-auto h-4 w-[80px]" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else-if="rows.length > 0">
                        <TableRow v-for="row in rows" :key="row.user_id">
                            <TableCell class="font-medium">
                                {{ row.user?.name || 'Usuario Eliminado' }}
                            </TableCell>
                            <TableCell
                                class="text-center text-lg font-semibold"
                            >
                                {{ row.total_calls }}
                            </TableCell>
                            <TableCell class="text-center">
                                <div
                                    class="inline-flex items-center gap-1 rounded border border-blue-200 bg-blue-50 px-2 py-0.5 font-mono text-sm text-blue-700"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{ formatDuration(row.avg_served_time) }}
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else>
                        <TableRow>
                            <TableCell colspan="3" class="h-24 text-center">
                                No hay datos de rendimiento para el rango
                                seleccionado.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
