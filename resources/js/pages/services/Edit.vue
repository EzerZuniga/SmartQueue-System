<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/services';
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { Service } from './columns';

const props = defineProps<{ service: Service }>();

const form = useForm({
    name: props.service.name,
    prefix: props.service.prefix || '',
    start_number: props.service.start_number,
    status: !!props.service.status,
    ask_document: !!props.service.ask_document,
});

const submit = () => {
    form.put(update.url({ service: props.service.id }));
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Servicios', href: index.url() },
            { title: 'Editar', href: '' },
        ]"
    >
        <Head title="Editar Servicio" />

        <div class="mx-auto w-full p-4 py-10 sm:px-6 lg:px-8">
            <HeadingSmall
                title="Editar Servicio"
                description="Modifica la configuración del trámite"
                class="mb-8"
            />

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Información General</h3>

                    <div class="grid gap-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="prefix">Prefijo</Label>
                            <Input
                                id="prefix"
                                v-model="form.prefix"
                                maxlength="5"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="start_number">Número Inicial</Label>
                            <Input
                                id="start_number"
                                type="number"
                                v-model="form.start_number"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            type="checkbox"
                            id="status"
                            v-model="form.status"
                            class="h-4 w-4 rounded border-neutral-300 text-neutral-900"
                        />
                        <Label for="status" class="cursor-pointer font-normal"
                            >Servicio Activo</Label
                        >
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Reglas del Kiosco</h3>
                    <div
                        class="grid grid-cols-1 gap-4 rounded-lg border bg-neutral-50 p-4 sm:grid-cols-2 dark:bg-neutral-900/50"
                    >
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="ask_document"
                                    v-model="form.ask_document"
                                    class="h-4 w-4 rounded border-neutral-300"
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
                        Actualizar
                    </Button>
                    <Button variant="link" as-child>
                        <Link :href="index.url()">Cancelar</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
