<script setup lang="ts">
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}>();
</script>

<template>
    <div v-if="links.length > 3">
        <div class="-mb-1 flex flex-wrap items-center justify-center">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="
                        link.url === null &&
                        (link.label === '...' || link.label === '&hellip;')
                    "
                    class="mr-1 mb-1 px-2 font-medium tracking-widest text-neutral-500"
                >
                    ...
                </div>

                <div
                    v-else-if="link.url === null"
                    class="mr-1 mb-1 cursor-not-allowed rounded border px-3 py-2 text-sm text-neutral-400 opacity-50"
                    v-html="link.label"
                />

                <Link
                    v-else
                    :href="link.url"
                    :class="
                        cn(
                            buttonVariants({
                                variant: link.active ? 'default' : 'outline',
                                size: 'sm',
                            }),
                            'mr-1 mb-1',
                            link.active && 'pointer-events-none',
                        )
                    "
                >
                    <span v-html="link.label"></span>
                </Link>
            </template>
        </div>
    </div>
</template>
