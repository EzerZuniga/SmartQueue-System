<script setup lang="ts">
import { Button } from '@/shared/components/ui/button';
import { Skeleton } from '@/shared/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/shared/components/ui/table';
import axios from 'axios';
import { FileSpreadsheet, RefreshCcw } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
//import { DateFormatter, getLocalTimeZone } from '@internationalized/date';
import reportsRoute from '@/routes/reports';
import type { DateRange } from 'radix-vue';

const props = defineProps<{
    range: DateRange;
}>();

interface CallStatus {
    id: number;
    name: string;
    slug: string;
    color: string;
}

interface Counter {
    name: string;
}

interface Ticket {
    id: number;
    ticket_number: string;
    service: {
        name: string;
    };
    call_statuse: CallStatus;
    status: string; // fallback
    created_at: string;
    client_document: string | null;
    client_name: string | null;
    client_phone: string | null;
    latest_call?: {
        counter?: Counter;
    };
}

const loading = ref(false);
const tickets = ref<Ticket[]>([]);
const pagination = ref<any>({});

//const df = new DateFormatter('es-ES', { dateStyle: 'short', timeStyle: 'short' });

const fetchData = async (page = 1) => {
    if (!props.range.start || !props.range.end) return;

    loading.value = true;
    try {
        const response = await axios.get(
            reportsRoute.tickets.url({
                query: {
                    page,
                    start_date: props.range.start.toString(),
                    end_date: props.range.end.toString(),
                },
            }),
        );
        tickets.value = response.data.data;
        pagination.value = response.data; // links, meta, etc.
    } catch (e) {
        console.error('Error fetching tickets', e);
    } finally {
        loading.value = false;
    }
};

const downloadExcel = () => {
    if (!props.range.start || !props.range.end) return;

    // Construir URL manual o usar axios con responseType blob
    const url = reportsRoute.tickets.export.url({
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
            <h3 class="text-lg font-medium">Listado de Tickets</h3>
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
                        <TableHead>Ticket #</TableHead>
                        <TableHead>Servicio</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Fecha Creación</TableHead>
                        <TableHead>Documento</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Ventanilla</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 5" :key="i">
                            <TableCell>
                                <Skeleton class="h-4 w-[80px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[120px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[100px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[150px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[100px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[120px]" />
                            </TableCell>
                            <TableCell>
                                <Skeleton class="h-4 w-[80px]" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else-if="tickets.length > 0">
                        <TableRow v-for="ticket in tickets" :key="ticket.id">
                            <TableCell class="font-bold">{{
                                ticket.ticket_number
                            }}</TableCell>
                            <TableCell>{{ ticket.service?.name }}</TableCell>
                            <TableCell>
                                <span
                                    class="rounded px-2 py-1 text-xs font-semibold text-white capitalize shadow-sm"
                                    :style="{
                                        backgroundColor:
                                            ticket.call_statuse?.color,
                                    }"
                                    >{{ ticket.call_statuse?.name }}</span
                                >
                            </TableCell>
                            <TableCell>{{
                                new Date(ticket.created_at).toLocaleString(
                                    'es-PE',
                                    {
                                        year: 'numeric',
                                        month: '2-digit',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    },
                                )
                            }}</TableCell>
                            <TableCell>{{
                                ticket.client_document || '-'
                            }}</TableCell>
                            <TableCell>
                                <div class="text-sm">
                                    {{ ticket.client_name || 'Anónimo' }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground"
                                    v-if="ticket.client_phone"
                                >
                                    {{ ticket.client_phone }}
                                </div>
                            </TableCell>
                            <TableCell>{{
                                ticket.latest_call?.counter?.name || '-'
                            }}</TableCell>
                        </TableRow>
                    </template>

                    <template v-else>
                        <TableRow>
                            <TableCell colspan="7" class="h-24 text-center">
                                No se encontraron tickets en el rango
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
