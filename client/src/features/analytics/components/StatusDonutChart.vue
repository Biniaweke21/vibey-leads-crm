<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import type { ApexOptions } from 'apexcharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { ChartPoint } from '@/features/analytics/types'
import { statusColors } from '@/features/analytics/data/chartColors'

const props = defineProps<{ title: string; data: ChartPoint[] }>()

/** Approved/Paid = good, Rejected/Overdue = critical, Pending = warning — status colors are reserved, never re-themed. */
const labelColor: Record<string, string> = {
  Approved: statusColors.good,
  Paid: statusColors.good,
  Rejected: statusColors.critical,
  Overdue: statusColors.critical,
  Pending: statusColors.warning,
}

const options = computed<ApexOptions>(() => ({
  chart: { type: 'donut', fontFamily: 'inherit' },
  labels: props.data.map((d) => d.label),
  colors: props.data.map((d) => labelColor[d.label] ?? statusColors.neutral),
  legend: { position: 'bottom', labels: { colors: '#52514e' } },
  dataLabels: { enabled: true, formatter: (val: number) => `${val.toFixed(0)}%` },
  stroke: { width: 2, colors: ['var(--card)'] },
}))

const series = computed(() => props.data.map((d) => d.value))
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ title }}</CardTitle>
    </CardHeader>
    <CardContent>
      <VueApexCharts type="donut" height="280" :options="options" :series="series" />
    </CardContent>
  </Card>
</template>
