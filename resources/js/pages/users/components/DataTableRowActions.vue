<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import type { Row } from '@tanstack/vue-table';
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/shared/components/ui/alert-dialog';
import { Button } from '@/shared/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu';
import { usePermissions } from '@/shared/composables/usePermissions';

const { can } = usePermissions();

// RUTAS DE USUARIOS
import { destroy, edit } from '@/routes/users'; // Asegúrate de definir esto en tu archivo de rutas JS
import type { User } from '../columns';

interface Props {
    row: Row<User>;
}
const props = defineProps<Props>();

const showDeleteDialog = ref(false);
const isDeleting = ref(false);

const handleDelete = () => {
    isDeleting.value = true;
    router.delete(destroy.url({ user: props.row.original.id }), {
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
            <template v-if="can('users.editar')">
                <DropdownMenuItem as-child>
                    <Link
                        :href="edit.url({ user: props.row.original.id })"
                        class="flex cursor-pointer items-center"
                    >
                        <Pencil class="mr-2 h-4 w-4 text-neutral-500" /> Editar
                    </Link>
                </DropdownMenuItem>
            </template>
            <template v-if="can('users.eliminar')">
                <DropdownMenuItem
                    @click="showDeleteDialog = true"
                    class="cursor-pointer text-red-600 focus:text-red-600"
                >
                    <Trash2 class="mr-2 h-4 w-4" /> Eliminar
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>

    <AlertDialog v-model:open="showDeleteDialog">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>¿Eliminar usuario?</AlertDialogTitle>
                <AlertDialogDescription>
                    Se eliminará a
                    <span class="font-bold"
                        >"{{ props.row.original.name }}"</span
                    >.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="isDeleting"
                    >Cancelar</AlertDialogCancel
                >
                <AlertDialogAction
                    @click.prevent="handleDelete"
                    class="bg-red-600 text-white hover:bg-red-700"
                    :disabled="isDeleting"
                >
                    {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
