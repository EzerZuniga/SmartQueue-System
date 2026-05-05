<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index } from '@/routes/users';
import { Head, Link, router } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';
import { columns, type User } from './columns';
import DataTable from './components/DataTable.vue';

const { can } = usePermissions();

interface PaginatedUsers {
    current_page: number;
    data: User[];
    from: number;
    to: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    // ... otros campos de paginación
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: { search: string | null; perPage: number };
}>();

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
            { title: 'Usuarios', href: '/users' },
        ]"
    >
        <Head title="Usuarios" />
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-bold text-neutral-900 dark:text-neutral-100"
                    >
                        Gestión de Usuarios
                    </h2>
                    <p class="text-sm text-neutral-500">
                        Administra el acceso y perfiles del personal
                    </p>
                </div>
                <template v-if="can('users.crear')">
                    <Link :href="create.url()">
                        <Button>
                            <UserPlus class="mr-2 h-4 w-4" /> Nuevo Usuario
                        </Button>
                    </Link>
                </template>
            </div>

            <DataTable
                :columns="columns"
                :data="users.data"
                :initial-search="filters.search"
                @search="handleSearch"
            />

            <div
                class="flex flex-col-reverse items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-center gap-2 text-sm text-neutral-500">
                    <span>Filas:</span>
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
                        {{ users.from ?? 0 }} - {{ users.to ?? 0 }} de
                        {{ users.total }}
                    </p>
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
