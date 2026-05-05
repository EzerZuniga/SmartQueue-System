<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/users';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    roles: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: '',
    image: null as File | null, // Campo para el archivo
    status: true,
});

const imagePreview = ref<string | null>(null);

// Manejo de preview de imagen
const handleImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.image = target.files[0];
        imagePreview.value = URL.createObjectURL(target.files[0]);
    }
};

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Usuarios', href: index.url() },
            { title: 'Crear', href: '' },
        ]"
    >
        <Head title="Crear Usuario" />
        <div class="mx-auto w-full p-4 py-10 sm:px-6 lg:px-8">
            <HeadingSmall
                title="Nuevo Empleado"
                description="Registra un nuevo usuario en el sistema"
                class="mb-8"
            />

            <form @submit.prevent="submit" class="space-y-8">
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Datos de Perfil</h3>

                    <div class="flex items-center gap-6">
                        <div
                            class="h-20 w-20 overflow-hidden rounded-full border bg-neutral-100"
                        >
                            <img
                                v-if="imagePreview"
                                :src="imagePreview"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-neutral-400"
                            >
                                <span class="text-xs">Sin foto</span>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="image">Foto de perfil</Label>
                            <Input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                            <InputError :message="form.errors.image" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Nombre Completo</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Ej: Juan Pérez"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Correo Electrónico</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            placeholder="juan@empresa.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid w-full gap-2 sm:w-1/2">
                        <Label for="role">Rol</Label>
                        <Select v-model="form.role">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona un rol" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.name"
                                >
                                    {{ role.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role" />
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Seguridad</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="password">Contraseña</Label>
                            <Input
                                id="password"
                                type="password"
                                v-model="form.password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="password_confirmation"
                                >Confirmar Contraseña</Label
                            >
                            <Input
                                id="password_confirmation"
                                type="password"
                                v-model="form.password_confirmation"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <Button :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-2" /> Guardar
                    </Button>
                    <Button variant="link" as-child>
                        <Link :href="index.url()">Cancelar</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
