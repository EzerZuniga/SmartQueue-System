<script setup lang="ts" generic="TData, TValue">
import type {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ref, watch } from 'vue';

import { Button } from '@/shared/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/shared/components/ui/empty'; // Componente de Shadcn
import { Input } from '@/shared/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/shared/components/ui/table';
import { valueUpdater } from '@/shared/lib/utils';
import { ChevronDown, Users } from 'lucide-vue-next'; // Usamos ícono de Users

const props = defineProps<{
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    initialSearch: string | null;
}>();

const emit = defineEmits(['search']);

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const searchValue = ref(props.initialSearch || '');

watch(
    () => props.initialSearch,
    (val) => {
        searchValue.value = val || '';
    },
);

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: (updaterOrValue) =>
        valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: (updaterOrValue) =>
        valueUpdater(updaterOrValue, columnVisibility),
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
    },
});

const handleSearch = () => {
    emit('search', searchValue.value);
};
</script>

<template>
    <div class="w-full">
        <div class="flex items-center py-4">
            <Input
                class="max-w-sm"
                placeholder="Buscar por nombre o email..."
                v-model="searchValue"
                @keydown.enter="handleSearch()"
            />
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="ml-auto">
                        Columnas
                        <ChevronDown class="ml-2 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuCheckboxItem
                        v-for="column in table
                            .getAllColumns()
                            .filter((column) => column.getCanHide())"
                        :key="column.id"
                        class="capitalize"
                        :model-value="column.getIsVisible()"
                        @update:model-value="
                            (value) => column.toggleVisibility(!!value)
                        "
                    >
                        {{
                            column.id === 'name'
                                ? 'Nombre'
                                : column.id === 'email'
                                  ? 'Correo'
                                  : column.id === 'image_path'
                                    ? 'Foto'
                                    : column.id === 'status'
                                      ? 'Estado'
                                      : column.id
                        }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else>
                        <TableRow>
                            <TableCell
                                :colspan="columns.length"
                                class="h-96 p-0 text-center"
                            >
                                <div
                                    class="flex h-full w-full flex-col items-center justify-center py-10"
                                >
                                    <Empty class="min-w-[300px] border-dashed">
                                        <EmptyHeader>
                                            <EmptyMedia
                                                variant="icon"
                                                class="mx-auto flex items-center justify-center"
                                            >
                                                <Users
                                                    class="h-10 w-10 text-neutral-400"
                                                />
                                            </EmptyMedia>
                                            <EmptyTitle
                                                >Sin empleados</EmptyTitle
                                            >
                                            <EmptyDescription
                                                >No se encontraron usuarios con
                                                ese criterio.</EmptyDescription
                                            >
                                        </EmptyHeader>
                                        <EmptyContent>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="$emit('search', '')"
                                            >
                                                Limpiar búsqueda
                                            </Button>
                                        </EmptyContent>
                                    </Empty>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
