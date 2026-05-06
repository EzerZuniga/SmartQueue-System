<script setup lang="ts">
import InputError from '@/shared/components/InputError.vue';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { index, store } from '@/routes/permissions';
import { type BreadcrumbItem } from '@/shared/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: '/dashboard' },
    { title: 'Permisos', href: index.url() },
    { title: 'Nuevo Permiso', href: '' },
];

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nuevo Permiso" />

        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-lg font-bold text-neutral-900 dark:text-neutral-100"
                    >
                        Crear Nuevo Permiso
                    </h2>
                    <p class="text-sm text-neutral-500">
                        Define el nombre del permiso
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-lg border p-4 shadow-sm">
                    <div class="grid gap-4">
                        <div class="grid w-full items-center gap-1.5">
                            <Label for="name">Nombre del Permiso</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Ej: users.delete"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                            <p class="text-xs text-neutral-500">
                                Se recomienda usar el formato recurso.accion
                            </p>
                        </div>
                    </div>
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
                        Guardar Permiso
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
