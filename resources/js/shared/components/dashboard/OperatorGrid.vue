<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/shared/components/ui/avatar';
import { OperatorData } from '@/shared/types/dashboard';
import { Clock } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    operators: OperatorData[];
}>();

const elapsedTimes = ref<Record<number, string>>({});
let intervalId: number | null = null;

const formatDuration = (startDateStr: string) => {
    const start = new Date(startDateStr).getTime();
    const now = new Date().getTime();
    const diff = Math.max(0, Math.floor((now - start) / 1000));

    const minutes = Math.floor(diff / 60);
    const seconds = diff % 60;

    // Optional: Add hours if needed, but for ticket durations MM:SS is usually enough
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hrs > 0) {
        return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    return `${mins.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

const updateTimers = () => {
    props.operators.forEach((op) => {
        if (op.status === 'serving' && op.call_started_at) {
            elapsedTimes.value[op.id] = formatDuration(op.call_started_at);
        } else {
            // Clean up if no longer serving
            delete elapsedTimes.value[op.id];
        }
    });
};

onMounted(() => {
    updateTimers(); // Immediate update
    intervalId = window.setInterval(updateTimers, 1000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border bg-card text-card-foreground shadow"
    >
        <div class="border-b p-4">
            <h3 class="font-semibold">Estado de Operadores</h3>
        </div>
        <div class="divide-y">
            <div
                v-for="op in operators"
                :key="op.id"
                class="flex items-center p-4"
            >
                <Avatar class="h-9 w-9">
                    <AvatarImage
                        :src="`https://ui-avatars.com/api/?name=${op.operator_name}&background=random`"
                        :alt="op.operator_name"
                    />
                    <AvatarFallback>{{
                        op.operator_name.charAt(0)
                    }}</AvatarFallback>
                </Avatar>
                <div class="ml-4 space-y-1">
                    <p class="text-sm leading-none font-medium">
                        {{ op.operator_name }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ op.counter_name }}
                    </p>
                </div>
                <div class="ml-auto flex items-center gap-4">
                    <div class="flex flex-col items-end gap-1">
                        <span
                            v-if="op.status === 'serving'"
                            class="inline-flex items-center rounded-full border border-transparent bg-green-700 px-2.5 py-0.5 text-xs font-semibold text-white shadow transition-colors hover:bg-green-700/80 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                        >
                            Atendiendo {{ op.current_ticket }}
                        </span>
                        <span
                            v-else-if="op.status === 'waiting'"
                            class="inline-flex items-center rounded-full border border-transparent bg-yellow-700 px-2.5 py-0.5 text-xs font-semibold text-white shadow transition-colors hover:bg-yellow-700/80 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                        >
                            En Espera
                        </span>

                        <!-- Timer -->
                        <div
                            v-if="
                                op.status === 'serving' && elapsedTimes[op.id]
                            "
                            class="text-md flex items-center gap-1.5 rounded-md bg-muted/80 px-2 py-0.5 font-medium text-muted-foreground"
                        >
                            <Clock class="h-3 w-3" />
                            <span
                                class="mt-[1px] font-mono leading-none tabular-nums"
                                >{{ elapsedTimes[op.id] }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="operators.length === 0"
                class="p-4 text-center text-sm text-muted-foreground"
            >
                No hay operadores activos.
            </div>
        </div>
    </div>
</template>
