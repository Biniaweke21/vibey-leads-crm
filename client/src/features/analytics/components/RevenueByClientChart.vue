<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { ChartPoint } from '@/features/analytics/types'
import { sequentialHue, chartChrome } from '@/features/analytics/data/chartColors'
import { formatEtb } from '@/utils/currency'

const props = defineProps<{ data: ChartPoint[] }>()

const options = computed<ApexOptions>(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: [sequentialHue],
  plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
  dataLabels: { enabled: false },
  grid: { borderColor: chartChrome.gridline, strokeDashArray: 3 },
  xaxis: {
    categories: props.data.map((d) => d.label),
    labels: {
      formatter: (v: string) => formatEtb(Number(v)),
      style: { colors: chartChrome.mutedText },
    },
    axisBorder: { color: chartChrome.axis },
  },
  tooltip: { y: { formatter: (v: number) => formatEtb(v) } },
}))

const series = computed(() => [{ name: 'Revenue', data: props.data.map((d) => d.value) }])
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Revenue by Client</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="bar" height="320" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
