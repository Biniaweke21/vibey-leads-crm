<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { TrendPoint } from '@/features/analytics/types'
import { sequentialHue, chartChrome } from '@/features/analytics/data/chartColors'
import { formatEtb } from '@/utils/currency'

const props = defineProps<{ data: TrendPoint[] }>()

const options = computed<ApexOptions>(() => ({
  chart: { type: 'line', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: [sequentialHue],
  stroke: { width: 2, curve: 'smooth' },
  markers: { size: 0, hover: { size: 6 } },
  dataLabels: { enabled: false },
  grid: { borderColor: chartChrome.gridline, strokeDashArray: 3 },
  xaxis: {
    categories: props.data.map((d) => d.label),
    labels: { style: { colors: chartChrome.mutedText } },
    axisBorder: { color: chartChrome.axis },
    axisTicks: { color: chartChrome.axis },
    tickAmount: Math.min(8, props.data.length - 1),
  },
  yaxis: {
    labels: { formatter: (v: number) => formatEtb(v), style: { colors: chartChrome.mutedText } },
  },
  tooltip: { y: { formatter: (v: number) => formatEtb(v) } },
}))

const series = computed(() => [{ name: 'Revenue', data: props.data.map((d) => d.value) }])
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Revenue Trend</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="line" height="280" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
