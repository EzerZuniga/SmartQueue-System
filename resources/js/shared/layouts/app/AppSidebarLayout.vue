<script setup lang="ts">
import AppContent from '@/shared/components/AppContent.vue';
import AppShell from '@/shared/components/AppShell.vue';
import AppSidebar from '@/shared/components/AppSidebar.vue';
import AppSidebarHeader from '@/shared/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/shared/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
