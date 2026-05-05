<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post('/user/confirm-password');
};
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-neutral-50 p-6 dark:bg-neutral-950"
    >
        <Head title="Confirmar contraseña" />

        <div class="w-full max-w-md rounded-xl border bg-card p-6 shadow-sm">
            <h1 class="mb-2 text-xl font-semibold">Confirmar contraseña</h1>
            <p class="mb-6 text-sm text-muted-foreground">
                Por seguridad, confirma tu contraseña para continuar.
            </p>

            <form class="space-y-4" @submit.prevent="submit">
                <Input
                    v-model="form.password"
                    type="password"
                    placeholder="Contraseña actual"
                    autocomplete="current-password"
                    required
                />

                <Button type="submit" class="w-full" :disabled="form.processing">
                    Confirmar
                </Button>
            </form>
        </div>
    </div>
</template>

