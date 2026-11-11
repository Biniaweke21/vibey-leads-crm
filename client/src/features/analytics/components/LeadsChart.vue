<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { TrendPoint } from '@/features/analytics/types'
import { sequentialHue, chartChrome } from '@/features/analytics/data/chartColors'

const props = defineProps<{ data: TrendPoint[] }>()

const options = computed<ApexOptions>(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: [sequentialHue],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
  dataLabels: { enabled: false },
  grid: { borderColor: chartChrome.gridline, strokeDashArray: 3 },
  xaxis: {
    categories: props.data.map((d) => d.label),
    labels: { style: { colors: chartChrome.mutedText } },
    axisBorder: { color: chartChrome.axis },
    axisTicks: { color: chartChrome.axis },
    tickAmount: Math.min(8, props.data.length - 1),
  },
  yaxis: { labels: { style: { colors: chartChrome.mutedText } }, forceNiceScale: true },
  tooltip: { y: { formatter: (v: number) => `${v} lead${v === 1 ? '' : 's'}` } },
}))

const series = computed(() => [{ name: 'New Leads', data: props.data.map((d) => d.value) }])
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>New Leads</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="bar" height="280" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
