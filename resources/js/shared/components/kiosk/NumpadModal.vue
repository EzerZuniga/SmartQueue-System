<script setup lang="ts">
import { Button } from '@/shared/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/shared/components/ui/dialog';
import { Input } from '@/shared/components/ui/input';
import { Spinner } from '@/shared/components/ui/spinner';
import { Delete, Eraser } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    loading?: boolean;
    serviceName?: string;
    errorMessage?: string; // Nuevo prop para mostrar errores de validación del backend
}>();

const emit = defineEmits(['update:open', 'confirm']);

const documentNumber = ref('');

// Watch para resetear cuando se abre
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            documentNumber.value = '';
        }
    },
);

const handleInput = (num: string) => {
    if (documentNumber.value.length < 11) {
        documentNumber.value += num;
    }
};

const handleBackspace = () => {
    documentNumber.value = documentNumber.value.slice(0, -1);
};

const handleClear = () => {
    documentNumber.value = '';
};

// Validación local básica
const isValid = computed(() => {
    const len = documentNumber.value.length;
    return (len === 8 || len === 11) && !props.loading;
});

const submit = () => {
    if (isValid.value) {
        emit('confirm', documentNumber.value);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="text-center text-2xl font-bold">
                    Ingrese su Documento
                </DialogTitle>
                <DialogDescription class="text-center">
                    Para el servicio
                    <span class="font-bold text-primary">{{
                        serviceName
                    }}</span>
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-6 py-4">
                <!-- Pantalla del número -->
                <div class="w-full max-w-[280px]">
                    <div class="relative">
                        <Input
                            readonly
                            v-model="documentNumber"
                            class="h-16 text-center text-3xl font-bold tracking-widest text-neutral-900 shadow-sm focus-visible:ring-0 dark:text-white"
                            placeholder="________"
                        />
                        <p
                            v-if="errorMessage"
                            class="absolute right-0 -bottom-6 left-0 animate-pulse text-center text-xs font-bold text-red-500"
                        >
                            {{ errorMessage }}
                        </p>
                    </div>
                </div>

                <!-- Teclado Numérico -->
                <div class="grid w-full max-w-[280px] grid-cols-3 gap-3">
                    <Button
                        v-for="n in 9"
                        :key="n"
                        type="button"
                        variant="outline"
                        class="h-16 text-2xl font-semibold shadow-sm transition-all hover:bg-neutral-100 active:scale-95 dark:hover:bg-neutral-800"
                        @click="handleInput(n.toString())"
                    >
                        {{ n }}
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="h-16 bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 dark:bg-red-950/20"
                        @click="handleClear"
                    >
                        <Eraser class="h-6 w-6" />
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="h-16 text-2xl font-semibold shadow-sm transition-all hover:bg-neutral-100 active:scale-95 dark:hover:bg-neutral-800"
                        @click="handleInput('0')"
                    >
                        0
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="h-16 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-700"
                        @click="handleBackspace"
                    >
                        <Delete class="h-6 w-6" />
                    </Button>
                </div>
            </div>

            <DialogFooter class="sm:justify-center">
                <Button
                    type="button"
                    class="h-12 w-full text-lg font-bold"
                    size="lg"
                    :disabled="!isValid"
                    @click="submit"
                >
                    <Spinner v-if="loading" class="mr-2" />
                    CONFIRMAR
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
