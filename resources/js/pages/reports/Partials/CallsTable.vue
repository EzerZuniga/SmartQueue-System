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
import axios from 'axios';
import { Clock, FileSpreadsheet, RefreshCcw } from 'lucide-vue-next';
import type { DateRange } from 'radix-vue';
import { onMounted, ref, watch } from 'vue';

import reportsRoute from '@/routes/reports';

const props = defineProps<{
    range: DateRange;
}>();

interface CallStatus {
    id: number;
    name: string;
    slug: string;
    color: string;
}

interface Call {
    id: number;
    ticket: {
        ticket_number: string;
    };
    user: {
        name: string;
    };
    service: {
        name: string;
    };
    counter: {
        name: string;
    };
    call_status?: CallStatus;
    called_at: string | null;
    started_at: string | null;
    ended_at: string | null;
    waiting_duration: number;
}

const loading = ref(false);
const calls = ref<Call[]>([]);
const pagination = ref<any>({});

const fetchData = async (page = 1) => {
    if (!props.range.start || !props.range.end) return;

    loading.value = true;
    try {
        const response = await axios.get(
            reportsRoute.calls.url({
                query: {
                    page,
                    start_date: props.range.start.toString(),
                    end_date: props.range.end.toString(),
                },
            }),
        );
        calls.value = response.data.data;
        pagination.value = response.data;
    } catch (e) {
        console.error('Error fetching calls', e);
    } finally {
        loading.value = false;
    }
};

const downloadExcel = () => {
    if (!props.range.start || !props.range.end) return;
    const url = reportsRoute.calls.export.url({
        query: {
            start_date: props.range.start.toString(),
            end_date: props.range.end.toString(),
        },
    });
    window.open(url, '_blank');
};

const calculateDuration = (start: string | null, end: string | null) => {
    if (!end) return 'En curso';
    if (!start) return '0m 0s';
    const s = new Date(start).getTime();
    const e = new Date(end).getTime();
    const diffMs = e - s;
    const diffMins = Math.floor(diffMs / 60000);
    const diffSecs = Math.floor((diffMs % 60000) / 1000);
    return `${diffMins}m ${diffSecs}s`;
};

const formatSeconds = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
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
            <h3 class="text-lg font-medium">Reporte de Atención</h3>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="fetchData(pagination.current_page)"
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
                        <TableHead>Ventanilla</TableHead>
                        <TableHead>Ticket</TableHead>
                        <TableHead>Llamada</TableHead>
                        <TableHead>Inicio</TableHead>
                        <TableHead>Fin</TableHead>
                        <TableHead>Espera</TableHead>
                        <TableHead>Atención</TableHead>
                        <TableHead>Estado Final</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 5" :key="i">
                            <TableCell>
                                <Skeleton class="h-4 w-[100px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[80px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[60px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[120px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[120px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[60px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[60px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[100px]" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else-if="calls.length > 0">
                        <TableRow v-for="call in calls" :key="call.id">
                            <TableCell>
                                <div class="font-medium">
                                    {{ call.user?.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ call.service?.name }}
                                </div>
                            </TableCell>
                            <TableCell>{{ call.counter?.name }}</TableCell>
                            <TableCell class="font-bold">{{
                                call.ticket?.ticket_number
                            }}</TableCell>
                            <TableCell>{{
                                call.called_at
                                    ? new Date(
                                          call.called_at,
                                      ).toLocaleTimeString('es-PE', {
                                          hour: '2-digit',
                                          minute: '2-digit',
                                      })
                                    : '-'
                            }}</TableCell>
                            <TableCell>{{
                                call.started_at
                                    ? new Date(
                                          call.started_at,
                                      ).toLocaleTimeString('es-PE', {
                                          hour: '2-digit',
                                          minute: '2-digit',
                                      })
                                    : '-'
                            }}</TableCell>
                            <TableCell
                                >{{
                                    call.ended_at
                                        ? new Date(
                                              call.ended_at,
                                          ).toLocaleTimeString('es-PE', {
                                              hour: '2-digit',
                                              minute: '2-digit',
                                          })
                                        : '-'
                                }}
                            </TableCell>
                            <TableCell>
                                <div
                                    class="flex w-fit items-center gap-1 rounded bg-gray-500 px-1.5 py-0.5 font-mono text-xs text-white"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{ formatSeconds(call.waiting_duration) }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <div
                                    class="flex w-fit items-center gap-1 rounded bg-gray-500 px-1.5 py-0.5 font-mono text-xs text-white"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{
                                        calculateDuration(
                                            call.started_at,
                                            call.ended_at,
                                        )
                                    }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <span
                                    class="rounded px-2 py-1 text-xs font-semibold text-white capitalize shadow-sm"
                                    :style="{
                                        backgroundColor:
                                            call.call_status?.color,
                                    }"
                                    >{{ call.call_status?.name }}</span
                                >
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else>
                        <TableRow>
                            <TableCell colspan="9" class="h-24 text-center">
                                No se encontraron llamadas en el rango
                                seleccionado.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Paginación Simple -->
        <div
            class="flex items-center justify-end space-x-2 border-t px-4 py-4"
            v-if="pagination.last_page > 1"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page === 1"
                @click="fetchData(pagination.current_page - 1)"
            >
                Anterior
            </Button>
            <div class="text-sm text-muted-foreground">
                Página {{ pagination.current_page }} de
                {{ pagination.last_page }}
            </div>
            <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page === pagination.last_page"
                @click="fetchData(pagination.current_page + 1)"
            >
                Siguiente
            </Button>
        </div>
    </div>
</template>
