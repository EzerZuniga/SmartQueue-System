import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar';
import { Button } from '@/shared/components/ui/button';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';
import DataTableRowActions from './components/DataTableRowActions.vue';

export interface User {
    id: number;
    name: string;
    email: string;
    image_path: string | null;
    status: boolean;
    roles?: { name: string }[];
    // email_verified_at, password, etc. no suelen mostrarse en la tabla
}

export const columns: ColumnDef<User>[] = [
    {
        accessorKey: 'image_path',
        header: '',
        cell: ({ row }) => {
            const name = row.getValue('name') as string;
            const image = row.getValue('image_path') as string | null;

            // Generar iniciales para el fallback (Ej: "Juan Perez" -> "JP")
            const initials = name
                .split(' ')
                .map((n) => n[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();

            // Corrección 1: Aseguramos que sea string vacío si es null
            const imageUrl = image ? `/storage/${image}` : '';

            // Corrección 2: Usamos funciones para los slots
            return h(
                Avatar,
                { class: 'h-8 w-8' },
                {
                    default: () => [
                        h(AvatarImage, { src: imageUrl, alt: name }),
                        h(AvatarFallback, null, {
                            default: () => initials,
                        }),
                    ],
                },
            );
        },
    },
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
            h('div', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'email',
        header: 'Correo',
        cell: ({ row }) =>
            h(
                'div',
                { class: 'lowercase text-neutral-500' },
                row.getValue('email'),
            ),
    },
    {
        id: 'role',
        header: 'Rol',
        cell: ({ row }) => {
            const roles = row.original.roles;
            const roleName =
                roles && roles.length > 0 ? roles[0].name : 'Sin Rol';
            return h(
                'span',
                {
                    class: 'inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400',
                },
                roleName,
            );
        },
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
        cell: ({ row }) => h(DataTableRowActions, { row }), // Necesitarás crear este archivo igual que en Services
        enableHiding: false,
    },
];
