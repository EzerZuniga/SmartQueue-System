<script setup lang="ts">
import { Button } from '@/shared/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/shared/components/ui/popover';
import { RangeCalendar } from '@/shared/components/ui/range-calendar';
import { cn } from '@/shared/lib/utils';
import { DateFormatter, getLocalTimeZone } from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import type { DateRange } from 'radix-vue';
import { ref, watch, type Ref } from 'vue';

const df = new DateFormatter('es-ES', {
    dateStyle: 'medium',
});

const props = defineProps<{
    modelValue?: DateRange;
}>();

const emit = defineEmits(['update:modelValue']);

const value = ref({
    start: undefined,
    end: undefined,
}) as Ref<DateRange>;

// Sincronizar con prop externa si cambia
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal) {
            value.value = newVal;
        }
    },
    { immediate: true },
);

// Emitir cambios
watch(
    value,
    (newVal) => {
        emit('update:modelValue', newVal);
    },
    { deep: true },
);
</script>

<template>
    <div :class="cn('grid gap-2', $attrs.class ?? '')">
        <Popover>
            <PopoverTrigger as-child>
                <Button
                    id="date"
                    variant="outline"
                    :class="
                        cn(
                            'w-[300px] justify-start text-left font-normal',
                            !value.start && 'text-muted-foreground',
                        )
                    "
                >
                    <CalendarIcon class="mr-2 h-4 w-4" />
                    <template v-if="value.start">
                        <template v-if="value.end">
                            {{
                                df.format(
                                    value.start.toDate(getLocalTimeZone()),
                                )
                            }}
                            -
                            {{
                                df.format(value.end.toDate(getLocalTimeZone()))
                            }}
                        </template>

                        <template v-else>
                            {{
                                df.format(
                                    value.start.toDate(getLocalTimeZone()),
                                )
                            }}
                        </template>
                    </template>
                    <template v-else> Seleccionar fechas </template>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <RangeCalendar
                    v-model="value"
                    initial-focus
                    :number-of-months="2"
                    @update:start-value="(v: any) => (value.start = v)"
                />
            </PopoverContent>
        </Popover>
    </div>
</template>
