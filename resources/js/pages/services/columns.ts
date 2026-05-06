import { Button } from '@/shared/components/ui/button';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown, Tv } from 'lucide-vue-next';
import { h } from 'vue';
import DataTableRowActions from './components/DataTableRowActions.vue';

// Interfaz exacta según tu migración
export interface Service {
    id: number;
    name: string;
    prefix: string | null;
    start_number: number;
    status: boolean;
    // Opcionales para la tabla, obligatorios para el form
    ask_document: boolean;
    active_assignments_count?: number;
    encrypted_id: string; // Agregado para el link seguro
}

export const columns: ColumnDef<Service>[] = [
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
        cell: ({ row }) =>
            h('div', { class: 'font-medium pl-4' }, row.getValue('name')),
    },
    {
        id: 'tv',
        header: 'TV',
        cell: ({ row }) => {
            const encryptedId = row.original.encrypted_id;
            return h(
                'a',
                {
                    href: `/tv?service=${encryptedId}`,
                    target: '_blank',
                    class: 'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9',
                    title: 'Abrir Monitor de TV',
                },
                h(Tv, { class: 'h-4 w-4' }),
            );
        },
    },
    {
        accessorKey: 'prefix',
        header: 'Prefijo',
        cell: ({ row }) => {
            const prefix = row.getValue('prefix') as string;
            return prefix
                ? h(
                      'span',
                      {
                          class: 'font-mono bg-neutral-100 dark:bg-neutral-800 px-2 py-1 rounded',
                      },
                      prefix,
                  )
                : h('span', { class: 'text-neutral-400' }, '-');
        },
    },
    {
        accessorKey: 'start_number',
        header: 'Inicio',
        cell: ({ row }) =>
            h('div', { class: 'text-start' }, row.getValue('start_number')),
    },
    {
        accessorKey: 'status',
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status');
            return h(
                'span',
                {
                    class: `inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                        status
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    }`,
                },
                status ? 'Activo' : 'Inactivo',
            );
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => h(DataTableRowActions, { row }),
        enableHiding: false,
    },
];
