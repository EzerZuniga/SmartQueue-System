<script setup lang="ts">
import HeadingSmall from '@/shared/components/HeadingSmall.vue';
import InputError from '@/shared/components/InputError.vue';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Spinner } from '@/shared/components/ui/spinner';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { index, update } from '@/routes/counters';
import { type BreadcrumbItem } from '@/shared/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

// 1. Definición de Interfaz
interface Counter {
    id: number;
    name: string;
    status: boolean | number;
}

const props = defineProps<{
    counter: Counter;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ventanillas', href: index.url() },
    { title: 'Editar', href: '' },
];

const form = useForm({
    name: props.counter.name,
    status: props.counter.status,
});

const submit = () => {
    form.put(update.url({ counter: props.counter.id }));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Ventanilla" />

        <div class="mx-auto w-full p-4 py-10 sm:px-6 lg:px-8">
            <HeadingSmall
                title="Editar Ventanilla"
                description="Modifica la información de la ventanilla existente"
                class="mb-8"
            />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="name">Nombre:</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        placeholder="Ej: Ventanilla 9"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        id="status"
                        v-model="form.status"
                        class="h-4 w-4 rounded border-neutral-300 text-neutral-900 shadow-sm focus:ring-neutral-900"
                    />

                    <Label
                        for="status"
                        class="cursor-pointer font-normal text-neutral-600"
                    >
                        Ventanilla Activa (Abierta)
                    </Label>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <Button :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Actualizar Ventanilla
                    </Button>

                    <Button variant="link" as-child>
                        <Link :href="index.url()"> Cancelar </Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
