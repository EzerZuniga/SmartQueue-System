import { Button } from '@/components/ui/button';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';

import DataTableRowActions from '@/pages/roles/components/DataTableRowActions.vue';

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions_count?: number;
}

export const columns: ColumnDef<Role>[] = [
    {
        accessorKey: 'name',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Nombre', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'text-left font-medium' },
                row.getValue('name'),
            );
        },
    },
    {
        accessorKey: 'permissions_count',
        header: () => h('div', { class: 'text-center' }, 'Permisos'),
        cell: ({ row }) => {
            const count = row.original.permissions_count ?? 0;
            return h(
                'div',
                { class: 'text-center' },
                h(
                    'span',
                    {
                        class: 'bg-neutral-100 dark:bg-neutral-800 px-2 py-1 rounded text-xs',
                    },
                    `${count} permisos`,
                ),
            );
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => h(DataTableRowActions, { row }),
        enableHiding: false,
    },
];
