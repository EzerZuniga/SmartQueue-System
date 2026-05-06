<script setup lang="ts">
import Pagination from '@/shared/components/Pagination.vue';
import { Button } from '@/shared/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';
import { usePermissions } from '@/shared/composables/usePermissions';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { create, index } from '@/routes/services';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusCircleIcon } from 'lucide-vue-next';
import { columns, type Service } from './columns';
import DataTable from './components/DataTable.vue'; // Asegúrate que apunta al DataTable nuevo

const { can } = usePermissions();

// Tipado según tu migración
interface PaginatedServices {
    current_page: number;
    data: Service[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

const props = defineProps<{
    services: PaginatedServices;
    filters: { search: string | null; perPage: number };
}>();

// Helper para actualizar params (reutilizable)
const updateParams = (newParams: Record<string, any>) => {
    router.get(
        index.url(),
        {
            search: props.filters.search,
            perPage: props.filters.perPage,
            ...newParams,
        },
        { preserveState: true, replace: true },
    );
};

const handleSearch = (query: string) =>
    updateParams({ search: query, page: 1 });
const handlePerPageChange = (val: string) =>
    val && updateParams({ perPage: val, page: 1 });
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Panel', href: '/dashboard' },
            { title: 'Servicios', href: '' },
        ]"
    >
        <Head title="Servicios" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-bold text-neutral-900 dark:text-neutral-100"
                    >
                        Gestión de Servicios
                    </h2>
                    <p class="text-sm text-neutral-500">
                        Configura los trámites y reglas del kiosco
                    </p>
                </div>
                <template v-if="can('services.crear')">
                    <Link :href="create.url()">
                        <Button>
                            <PlusCircleIcon class="mr-2 h-4 w-4" /> Nuevo
                            Servicio
                        </Button>
                    </Link>
                </template>
            </div>

            <DataTable
                :columns="columns"
                :data="services.data"
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
                            <SelectValue />
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
                        {{ services.from ?? 0 }} - {{ services.to ?? 0 }} de
                        {{ services.total }}
                    </p>
                    <Pagination :links="services.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
