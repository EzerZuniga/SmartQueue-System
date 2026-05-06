<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/shared/components/AppearanceTabs.vue';
import HeadingSmall from '@/shared/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/shared/types';

import AppLayout from '@/shared/layouts/AppLayout.vue';
import SettingsLayout from '@/shared/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurar Tema',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurar Tema" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Configurar Tema"
                    description="Actualiza el tema según tu preferencia"
                />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
