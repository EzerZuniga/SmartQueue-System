<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import { DemandData } from '@/types/dashboard';
import { VisArea, VisAxis, VisLine, VisXYContainer } from '@unovis/vue';
import { computed } from 'vue';

const props = defineProps<{
    data: DemandData[];
}>();

const reactiveData = computed(() => props.data);

type Data = (typeof reactiveData.value)[number];

const chartConfig = {
    tickets: {
        label: 'Tickets',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

const svgDefs = `
  <linearGradient id="fillTickets" x1="0" y1="0" x2="0" y2="1">
    <stop
      offset="5%"
      stop-color="var(--color-tickets)"
      stop-opacity="0.8"
    />
    <stop
      offset="95%"
      stop-color="var(--color-tickets)"
      stop-opacity="0.1"
    />
  </linearGradient>
`;
</script>

<template>
    <Card class="pt-0">
        <CardHeader class="border-b py-5">
            <div class="grid flex-1 gap-1">
                <CardTitle>Curva de Demanda</CardTitle>
                <CardDescription>
                    Tickets emitidos hoy (06:00 - 22:00)
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent class="px-2 pt-4 pb-4 sm:px-6 sm:pt-6">
            <ChartContainer
                :config="chartConfig"
                class="aspect-auto h-[250px] w-full"
                :cursor="false"
            >
                <VisXYContainer
                    :data="reactiveData"
                    :svg-defs="svgDefs"
                    :margin="{ left: 0, right: 0 }"
                >
                    <VisArea
                        :x="(d: Data) => new Date(d.date).getTime()"
                        :y="(d: Data) => d.tickets"
                        color="url(#fillTickets)"
                        :opacity="0.6"
                    />
                    <VisLine
                        :x="(d: Data) => new Date(d.date).getTime()"
                        :y="(d: Data) => d.tickets"
                        :color="chartConfig.tickets.color"
                        :line-width="2"
                    />
                    <VisAxis
                        type="x"
                        :x="(d: Data) => new Date(d.date).getTime()"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="false"
                        :num-ticks="16"
                        :tick-format="
                            (d: number) => {
                                const date = new Date(d);
                                return date.toLocaleTimeString('es-ES', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                });
                            }
                        "
                    />
                    <VisAxis
                        type="y"
                        :num-ticks="3"
                        :tick-line="false"
                        :domain-line="false"
                    />
                    <ChartTooltip />
                    <ChartCrosshair
                        :template="
                            componentToString(
                                chartConfig,
                                ChartTooltipContent,
                                {
                                    labelFormatter: (d) => {
                                        const date = new Date(d);
                                        const startHour = date.getHours();
                                        const endHour = startHour + 1;
                                        return `${startHour}:00 - ${endHour}:00`;
                                    },
                                },
                            )
                        "
                        :color="chartConfig.tickets.color"
                    />
                </VisXYContainer>
            </ChartContainer>
        </CardContent>
    </Card>
</template>
