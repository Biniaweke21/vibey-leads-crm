<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { LeadStatus } from '@/stores/leads'
import { chartChrome, statusColors } from '@/features/analytics/data/chartColors'

const props = defineProps<{ data: { label: string; value: number; status: LeadStatus }[] }>()

/** Ordinal ramp for in-flight stages; won-family in green shades; lost in red — status colors carry meaning, never decorative. */
const stageColor: Record<LeadStatus, string> = {
  new: '#9ec5f4',
  contacted: '#6da7ec',
  'discovery-call': '#3987e5',
  'proposal-sent': '#2a78d6',
  negotiation: '#184f95',
  won: statusColors.good,
  lost: statusColors.critical,
  'in-development': '#3a9d3a',
  delivered: '#238c23',
  maintenance: '#0ca30c',
}

const options = computed<ApexOptions>(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
  colors: props.data.map((d) => stageColor[d.status]),
  plotOptions: {
    bar: { horizontal: true, borderRadius: 4, distributed: true, barHeight: '65%' },
  },
  legend: { show: false },
  dataLabels: { enabled: true, style: { colors: [chartChrome.mutedText] }, offsetX: 20 },
  grid: { borderColor: chartChrome.gridline, strokeDashArray: 3 },
  xaxis: {
    categories: props.data.map((d) => d.label),
    labels: { style: { colors: chartChrome.mutedText } },
    axisBorder: { color: chartChrome.axis },
  },
  tooltip: { y: { formatter: (v: number) => `${v} lead${v === 1 ? '' : 's'}` } },
}))

const series = computed(() => [{ name: 'Leads', data: props.data.map((d) => d.value) }])
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Lead Pipeline</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="bar" height="360" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
