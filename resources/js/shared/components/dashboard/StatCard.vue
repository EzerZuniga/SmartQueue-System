<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    title: string;
    value: string | number;
    description?: string;
    trend?: 'up' | 'down' | 'neutral';
    color?: string; // class for text color logic
}>();

const valueClass = computed(() => {
    if (props.color) return props.color;
    return 'text-foreground';
});
</script>

<template>
    <div class="rounded-xl border bg-card text-card-foreground shadow">
        <div
            class="flex flex-row items-center justify-between space-y-0 p-6 pb-2"
        >
            <h3 class="text-sm font-medium tracking-tight">{{ title }}</h3>
            <!-- Optional Icon could go here -->
        </div>
        <div class="p-6 pt-0">
            <div class="text-4xl font-bold" :class="valueClass">
                {{ value }}
            </div>
            <p v-if="description" class="text-xs text-muted-foreground">
                {{ description }}
            </p>
        </div>
    </div>
</template>
