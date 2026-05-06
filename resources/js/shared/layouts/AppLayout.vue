<script setup lang="ts">
import { Toaster } from '@/shared/components/ui/sonner';
import AppLayout from '@/shared/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/shared/types';
import { usePage } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// --- LÓGICA FLASH GLOBAL ---
const page = usePage();

watch(
    () => page.props.flash,
    async (flash: any) => {
        await nextTick();

        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.info) {
            toast.info(flash.info);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster position="top-right" />
        <slot />
    </AppLayout>
</template>
