<script setup lang="ts">
import HeadingSmall from '@/shared/components/HeadingSmall.vue';
import InputError from '@/shared/components/InputError.vue';
import { Button } from '@/shared/components/ui/button';
import { Input } from '@/shared/components/ui/input';
import { Label } from '@/shared/components/ui/label';
import { Separator } from '@/shared/components/ui/separator';
import { Spinner } from '@/shared/components/ui/spinner';
import AppLayout from '@/shared/layouts/AppLayout.vue';
import { index, store } from '@/routes/services'; // Asegúrate de tener estas rutas
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    prefix: '',
    start_number: 1,
    status: true,
    // Reglas Kiosco
    ask_document: true,
});

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Servicios', href: index.url() },
            { title: 'Crear', href: '' },
        ]"
    >
        <Head title="Crear Servicio" />

        <div class="mx-auto w-full p-4 py-10 sm:px-6 lg:px-8">
            <HeadingSmall
                title="Nuevo Servicio"
                description="Configura un nuevo tipo de trámite y sus reglas"
                class="mb-8"
            />

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Información General</h3>

                    <div class="grid gap-2">
                        <Label for="name">Nombre del Servicio</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Ej: Atención General"
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="prefix">Prefijo Ticket</Label>
                            <Input
                                id="prefix"
                                v-model="form.prefix"
                                placeholder="Ej: A"
                                maxlength="5"
                            />
                            <InputError :message="form.errors.prefix" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="start_number">Número Inicial</Label>
                            <Input
                                id="start_number"
                                type="number"
                                v-model="form.start_number"
                                min="0"
                            />
                            <InputError :message="form.errors.start_number" />
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Reglas del Kiosco</h3>
                    <p class="text-sm text-neutral-500">
                        Configura el comportamiento del kiosco para este
                        servicio.
                    </p>

                    <div
                        class="grid grid-cols-1 gap-4 rounded-lg border bg-neutral-50 p-4 sm:grid-cols-2 dark:bg-neutral-900/50"
                    >
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="ask_document"
                                    v-model="form.ask_document"
                                    class="h-4 w-4 rounded border-neutral-300 text-neutral-900"
                                />
                                <Label for="ask_document" class="cursor-pointer"
                                    >Solicitar Documento (DNI/RUC)</Label
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <Button :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-2" />
                        Guardar
                    </Button>
                    <Button variant="link" as-child>
                        <Link :href="index.url()">Cancelar</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
