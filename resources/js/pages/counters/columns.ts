import { Button } from '@/components/ui/button';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';

import DataTableRowActions from '@/pages/counters/components/DataTableRowActions.vue';

export interface Counter {
    id: number;
    name: string;
    status: boolean;
}

export const columns: ColumnDef<Counter>[] = [
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
            return h('div', { class: 'text-left' }, row.getValue('name'));
        },
    },
    {
        accessorKey: 'status',
        header: () => h('div', { class: 'text-left' }, 'Estado'),
        cell: ({ row }) => {
            const status = row.getValue('status');
            const statusClass = status
                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';

            return h(
                'span',
                {
                    class: `inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${statusClass}`,
                },
                status ? 'Abierta' : 'Cerrada',
            );
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => h(DataTableRowActions, { row }),
        enableHiding: false,
    },
];
