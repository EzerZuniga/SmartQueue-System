<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import type { Row } from '@tanstack/vue-table';
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

// Componentes UI
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

// Rutas e Interfaces
import { destroy, edit } from '@/routes/counters';
import type { Counter } from '../columns';

interface DataTableRowActionsProps {
    row: Row<Counter>;
}

const props = defineProps<DataTableRowActionsProps>();

// Estado para controlar el diálogo
const showDeleteDialog = ref(false);
const isDeleting = ref(false);

const handleDelete = () => {
    isDeleting.value = true;
    router.delete(destroy.url({ counter: props.row.original.id }), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteDialog.value = false;
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0">
                <span class="sr-only">Abrir menú</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <template v-if="can('counters.editar')">
                <DropdownMenuItem as-child>
                    <Link
                        :href="edit.url({ counter: props.row.original.id })"
                        class="flex cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4 text-neutral-500" />
                        Editar
                    </Link>
                </DropdownMenuItem>
            </template>

            <template v-if="can('counters.eliminar')">
                <DropdownMenuItem
                    @click="showDeleteDialog = true"
                    class="cursor-pointer text-red-600 focus:text-red-600"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Eliminar
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>

    <AlertDialog v-model:open="showDeleteDialog">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle
                    >¿Estás completamente seguro?</AlertDialogTitle
                >
                <AlertDialogDescription>
                    Esta acción no se puede deshacer. Se eliminará
                    permanentemente la ventanilla
                    <span class="font-bold text-neutral-600"
                        >"{{ props.row.original.name }}"</span
                    >
                    y todos sus datos asociados.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="isDeleting"
                    >Cancelar</AlertDialogCancel
                >
                <AlertDialogAction
                    @click.prevent="handleDelete"
                    class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-600"
                    :disabled="isDeleting"
                >
                    {{ isDeleting ? 'Eliminando...' : 'Sí, eliminar' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
