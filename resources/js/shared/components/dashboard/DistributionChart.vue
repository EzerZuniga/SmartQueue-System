<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/shared/components/ui/card';
import { ChartContainer, ChartTooltip } from '@/shared/components/ui/chart';
import { DistributionData } from '@/shared/types/dashboard';
import { Donut } from '@unovis/ts';
import { VisDonut, VisSingleContainer } from '@unovis/vue';
import { computed } from 'vue';

const props = defineProps<{
    data: DistributionData[];
}>();

// 1. Reactividad
const reactiveData = computed(() => props.data);

// 2. Cálculo del Total para el centro del Donut
const totalTickets = computed(() => {
    return props.data.reduce((acc, curr) => acc + curr.count, 0);
});

// 3. Accesores
const value = (d: DistributionData) => d.count;

// 4. Colores (Mantenemos tu lógica de colores cíclicos)
const colors = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];
const color = (_: unknown, i: number) => colors[i % colors.length];

// 5. Configuración básica para satisfacer el tipo, aunque usamos colores manuales
const chartConfig = {
    tickets: { label: 'Tickets' },
};

// Función para generar el HTML del tooltip al estilo Shadcn
const getTooltip = (d: any) => {
    const item = d?.data || d;

    if (!item) return '';

    const name = item.name ?? 'Sin nombre';
    const count = item.count ?? 0;

    return `
    <div class="rounded-lg border bg-popover px-3 py-1.5 text-sm shadow-md">
      <div class="font-semibold text-popover-foreground mb-1">${name}</div>
      <div class="flex items-center gap-2">
        <span class="text-muted-foreground">
          ${count} tickets
        </span>
      </div>
    </div>
  `;
};
</script>

<template>
    <Card class="flex flex-col">
        <CardHeader class="items-center pb-0">
            <CardTitle>Distribución por Servicio</CardTitle>
            <CardDescription>Tickets emitidos hoy</CardDescription>
        </CardHeader>
        <hr />
        <CardContent class="flex-1 pb-0">
            <br />
            <ChartContainer
                :config="chartConfig"
                class="mx-auto mt-4 aspect-square max-h-[250px]"
                :style="{
                    '--vis-donut-central-label-font-size': 'var(--text-3xl)',
                    '--vis-donut-central-label-font-weight':
                        'var(--font-weight-bold)',
                    '--vis-donut-central-label-text-color': 'var(--foreground)',
                    '--vis-donut-central-sub-label-text-color':
                        'var(--muted-foreground)',
                }"
            >
                <VisSingleContainer
                    :data="reactiveData"
                    :margin="{ top: 20, bottom: 20 }"
                >
                    <VisDonut
                        :value="value"
                        :color="color"
                        :arc-width="30"
                        :central-label-offset-y="10"
                        :central-label="totalTickets.toString()"
                        central-sub-label="Total"
                    />

                    <ChartTooltip
                        :triggers="{
                            [Donut.selectors.segment]: getTooltip,
                        }"
                    />
                </VisSingleContainer>
            </ChartContainer>
        </CardContent>
    </Card>
</template>
