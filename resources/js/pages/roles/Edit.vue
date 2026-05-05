<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

interface Role {
    id: number;
    name: string;
}

const props = defineProps<{
    role: Role;
    permissions: Record<string, Array<{ id: number; name: string }>>;
    currentPermissions: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
    { title: 'Roles', href: index.url() },
    { title: 'Editar Rol', href: '' },
];

const form = useForm({
    name: props.role.name,
    permissions: props.currentPermissions || [],
});

const submit = () => {
    form.put(update.url({ role: props.role.id }));
};

// Helpers para manejar permisos

const isGroupComplete = (groupPermissions: Array<{ name: string }>) => {
    return groupPermissions.every((p) => form.permissions.includes(p.name));
};

const toggleGroup = (groupPermissions: Array<{ name: string }>) => {
    const groupNames = groupPermissions.map((p) => p.name);
    const allSelected = isGroupComplete(groupPermissions);

    if (allSelected) {
        // Deseleccionar todos
        form.permissions = form.permissions.filter(
            (p) => !groupNames.includes(p),
        );
    } else {
        // Seleccionar todos (los que falten)
        const missing = groupNames.filter(
            (name) => !form.permissions.includes(name),
        );
        form.permissions = [...form.permissions, ...missing];
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Editar ${role.name}`" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-bold text-neutral-900 dark:text-neutral-100"
                    >
                        Editar Rol: {{ role.name }}
                    </h2>
                    <p class="text-sm text-neutral-500">
                        Modifica el nombre y asignación de permisos
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-lg border p-4 shadow-sm">
                    <div class="grid gap-4">
                        <div class="grid w-full items-center gap-1.5">
                            <Label for="name">Nombre del Rol</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Ej: Supervisor de Caja"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border p-4 shadow-sm">
                    <h3 class="text-md mb-4 font-bold font-semibold">
                        Permisos del Sistema
                    </h3>

                    <div
                        v-if="Object.keys(permissions).length === 0"
                        class="text-sm text-neutral-500"
                    >
                        No hay permisos disponibles.
                    </div>

                    <div
                        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="(groupPermissions, groupName) in permissions"
                            :key="groupName"
                            class="rounded-md border p-4"
                        >
                            <div
                                class="mb-3 flex items-center justify-between border-b pb-2"
                            >
                                <h4 class="font-bold capitalize">
                                    {{ groupName }}
                                </h4>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-6 text-xs"
                                    :class="
                                        isGroupComplete(groupPermissions)
                                            ? 'text-red-500 hover:text-red-600'
                                            : 'text-primary hover:text-primary/80'
                                    "
                                    @click="toggleGroup(groupPermissions)"
                                >
                                    {{
                                        isGroupComplete(groupPermissions)
                                            ? 'Deseleccionar'
                                            : 'Seleccionar Todo'
                                    }}
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="permission in groupPermissions"
                                    :key="permission.id"
                                    class="flex items-start space-x-2"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`perm-${permission.id}`"
                                        :value="permission.name"
                                        v-model="form.permissions"
                                        class="h-4 w-4 rounded-sm border-neutral-300 text-primary focus:ring-primary"
                                    />
                                    <label
                                        :for="`perm-${permission.id}`"
                                        class="cursor-pointer text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        {{ permission.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <InputError
                        :message="form.errors.permissions"
                        class="mt-2"
                    />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <Link :href="index.url()">
                        <Button
                            variant="outline"
                            type="button"
                            :disabled="form.processing"
                        >
                            Cancelar
                        </Button>
                    </Link>
                    <Button :disabled="form.processing" type="submit">
                        <Save class="mr-2 h-4 w-4" v-if="!form.processing" />
                        <Spinner v-if="form.processing" />
                        Actualizar Rol
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
