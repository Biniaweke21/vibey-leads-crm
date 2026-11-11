<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { ChartPoint } from '@/features/analytics/types'
import { categoricalPalette } from '@/features/analytics/data/chartColors'

const props = defineProps<{ data: ChartPoint[] }>()

const options = computed<ApexOptions>(() => ({
  chart: { type: 'donut', fontFamily: 'inherit' },
  labels: props.data.map((d) => d.label),
  colors: categoricalPalette,
  legend: { position: 'bottom', labels: { colors: '#52514e' } },
  dataLabels: { enabled: true, formatter: (val: number) => `${val.toFixed(0)}%` },
  stroke: { width: 2, colors: ['var(--card)'] },
  tooltip: { y: { formatter: (v: number) => `${v} lead${v === 1 ? '' : 's'}` } },
}))

const series = computed(() => props.data.map((d) => d.value))
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Service Distribution</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="donut" height="320" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
