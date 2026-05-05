<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index } from '@/routes/counters';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusCircleIcon } from 'lucide-vue-next';
import { columns, type Counter } from './columns';
import DataTable from './components/DataTable.vue';

// IMPORTAR COMPOENTES DE SELECT (Shadcn)
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

interface PaginatedCounters {
    current_page: number;
    data: Counter[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

const props = defineProps<{
    counters: PaginatedCounters;
    filters: {
        search: string | null;
        perPage: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
    { title: 'Ventanillas', href: '/counters' },
];

// --- LÓGICA DE FILTROS ---

// Helper para hacer la petición manteniendo los filtros actuales
const updateParams = (newParams: Record<string, any>) => {
    router.get(
        index.url(),
        {
            // Mantenemos lo que ya existe y sobrescribimos con lo nuevo
            search: props.filters.search,
            perPage: props.filters.perPage,
            ...newParams,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleSearch = (query: string) => {
    updateParams({ search: query, page: 1 }); // Al buscar, volvemos a página 1
};

const handlePerPageChange = (value: string | null) => {
    if (value) {
        // Verificamos que no sea null
        updateParams({ perPage: value, page: 1 });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Ventanillas" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-bold text-neutral-900 dark:text-neutral-100"
                    >
                        Gestión de Ventanillas
                    </h2>
                    <p class="text-sm text-neutral-500">
                        Administra las cajas de atención
                    </p>
                </div>

                <template v-if="can('counters.crear')">
                    <Link :href="create.url()">
                        <Button>
                            <PlusCircleIcon />
                            Nueva Ventanilla
                        </Button>
                    </Link>
                </template>
            </div>

            <DataTable
                :columns="columns"
                :data="counters.data"
                :initial-search="filters.search"
                @search="handleSearch"
            />

            <div
                class="flex flex-col-reverse items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-center gap-2 text-sm text-neutral-500">
                    <span>Filas por página:</span>
                    <Select
                        :model-value="String(filters.perPage)"
                        @update:model-value="
                            (val) => handlePerPageChange(val as string)
                        "
                    >
                        <SelectTrigger class="h-8 w-[70px]">
                            <SelectValue
                                :placeholder="String(filters.perPage)"
                            />
                        </SelectTrigger>
                        <SelectContent side="top">
                            <SelectItem value="5">5</SelectItem>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="20">20</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-col items-center gap-4 sm:flex-row">
                    <p class="text-sm text-neutral-500">
                        {{ counters.from ?? 0 }} - {{ counters.to ?? 0 }} de
                        {{ counters.total }}
                    </p>
                    <Pagination :links="counters.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
