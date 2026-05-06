<script setup lang="ts">
import Pagination from '@/shared/components/Pagination.vue';
import { Button } from '@/shared/components/ui/button';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { create, index } from '@/routes/permissions';
import { type BreadcrumbItem } from '@/shared/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusCircleIcon } from 'lucide-vue-next';
import { columns, type Permission } from './columns';
import DataTable from './components/DataTable.vue';

// IMPORTAR COMPONENTES DE SELECT (Shadcn)
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/shared/components/ui/select';
import { usePermissions } from '@/shared/composables/usePermissions';

const { can } = usePermissions();

interface PaginatedPermissions {
    current_page: number;
    data: Permission[];
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
    permissions: PaginatedPermissions;
    filters: {
        search: string | null;
        perPage: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
    { title: 'Permisos', href: '/permissions' },
];

// --- LÓGICA DE FILTROS ---
const updateParams = (newParams: Record<string, any>) => {
    router.get(
        index.url(),
        {
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
    updateParams({ search: query, page: 1 });
};

const handlePerPageChange = (value: string | null) => {
    if (value) {
        updateParams({ perPage: value, page: 1 });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Permisos" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-bold text-neutral-900 dark:text-neutral-100"
                    >
                        Gestión de Permisos
                    </h2>
                    <p class="text-sm text-neutral-500">
                        Define los permisos del sistema
                    </p>
                </div>

                <template v-if="can('permisos.crear')">
                    <Link :href="create.url()">
                        <Button>
                            <PlusCircleIcon />
                            Nuevo Permiso
                        </Button>
                    </Link>
                </template>
            </div>

            <DataTable
                :columns="columns"
                :data="permissions.data"
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
                        {{ permissions.from ?? 0 }} -
                        {{ permissions.to ?? 0 }} de
                        {{ permissions.total }}
                    </p>
                    <Pagination :links="permissions.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
