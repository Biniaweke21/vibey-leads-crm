<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { ChartPoint } from '@/features/analytics/types'
import { chartChrome } from '@/features/analytics/data/chartColors'

const props = defineProps<{ data: ChartPoint[] }>()

/** Matches the hues already used for project status badges elsewhere in the app. */
const statusHexByLabel: Record<string, string> = {
  Planning: '#0ea5e9',
  'In Progress': '#f59e0b',
  'On Hold': '#f97316',
  Completed: '#10b981',
  Maintenance: '#a855f7',
}

const options = computed<ApexOptions>(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: props.data.map((d) => statusHexByLabel[d.label] ?? '#898781'),
  plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', distributed: true } },
  legend: { show: false },
  dataLabels: { enabled: false },
  grid: { borderColor: chartChrome.gridline, strokeDashArray: 3 },
  xaxis: {
    categories: props.data.map((d) => d.label),
    labels: { style: { colors: chartChrome.mutedText } },
    axisBorder: { color: chartChrome.axis },
    axisTicks: { color: chartChrome.axis },
  },
  yaxis: { labels: { style: { colors: chartChrome.mutedText } }, forceNiceScale: true },
  tooltip: { y: { formatter: (v: number) => `${v} project${v === 1 ? '' : 's'}` } },
}))

const series = computed(() => [{ name: 'Projects', data: props.data.map((d) => d.value) }])
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Project Status</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="bar" height="280" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
