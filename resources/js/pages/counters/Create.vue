<script setup lang="ts">
import HeadingSmall from '@/shared/components/HeadingSmall.vue';
import InputError from '@/shared/components/InputError.vue';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { index, store } from '@/routes/counters';
import { type BreadcrumbItem } from '@/shared/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ventanillas', href: index.url() },
    { title: 'Crear', href: '' },
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
        <Head title="Crear Ventanilla" />

        <div class="mx-auto w-full p-4 py-10 sm:px-6 lg:px-8">
            <HeadingSmall
                title="Nueva Ventanilla"
                description="Registra un nuevo punto de atención para el sistema"
                class="mb-8"
            />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="name">Nombre:</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        autofocus
                        placeholder="Ej: Ventanilla 9"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <Button :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Guardar Ventanilla
                    </Button>

                    <Button variant="link" as-child>
                        <Link :href="index.url()"> Cancelar </Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
