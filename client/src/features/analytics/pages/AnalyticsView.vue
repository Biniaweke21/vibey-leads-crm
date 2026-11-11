<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { UsersRound, Building2, Briefcase, Wallet, FileCheck2, Percent } from '@lucide/vue'
import { useAnalyticsStore } from '@/features/analytics/store/analytics'
import { useAnalyticsDataStore } from '@/features/analytics/composables/useAnalyticsData'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'
import KPIStatCard from '@/features/analytics/components/KPIStatCard.vue'
import DateRangeSelector from '@/features/analytics/components/DateRangeSelector.vue'
import RevenueChart from '@/features/analytics/components/RevenueChart.vue'
import LeadsChart from '@/features/analytics/components/LeadsChart.vue'
import PipelineChart from '@/features/analytics/components/PipelineChart.vue'
import ServiceDistributionChart from '@/features/analytics/components/ServiceDistributionChart.vue'
import ProjectStatusChart from '@/features/analytics/components/ProjectStatusChart.vue'
import RevenueByClientChart from '@/features/analytics/components/RevenueByClientChart.vue'
import StatusDonutChart from '@/features/analytics/components/StatusDonutChart.vue'
import TeamPerformanceTable from '@/features/analytics/components/TeamPerformanceTable.vue'
import RecentActivityChart from '@/features/analytics/components/RecentActivityChart.vue'

const toast = useToast()
const analyticsStore = useAnalyticsStore()
const data = useAnalyticsDataStore()

const dateRange = computed({
  get: () => analyticsStore.dateRange,
  set: (v) => analyticsStore.setDateRange(v),
})

async function load() {
  try {
    await data.fetchAll(dateRange.value)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load analytics.'))
  }
}

onMounted(load)
watch(dateRange, load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Analytics</h1>
        <p class="text-muted-foreground mt-1">
          How the business is doing, derived from your live data.
        </p>
      </div>
      <DateRangeSelector v-model="dateRange" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
      <KPIStatCard label="Total Leads" :value="data.totalLeads" :icon="UsersRound" />
      <KPIStatCard label="Active Clients" :value="data.activeClients" :icon="Building2" />
      <KPIStatCard label="Active Projects" :value="data.activeProjects" :icon="Briefcase" />
      <KPIStatCard label="Revenue" :value="formatEtb(data.revenue)" :icon="Wallet" />
      <KPIStatCard
        label="Proposal Conversion"
        :value="data.proposalConversionRate"
        suffix="%"
        :icon="FileCheck2"
      />
      <KPIStatCard
        label="Invoice Collection"
        :value="data.invoiceCollectionRate"
        suffix="%"
        :icon="Percent"
      />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <RevenueChart :data="data.revenueTrend" />
      <LeadsChart :data="data.leadsTrend" />
    </div>

    <PipelineChart :data="data.pipeline" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <ServiceDistributionChart :data="data.serviceDistribution" />
      <ProjectStatusChart :data="data.projectStatusCounts" />
    </div>

    <RevenueByClientChart :data="data.revenueByClient" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <StatusDonutChart title="Proposal Success Rate" :data="data.proposalSuccess" />
      <StatusDonutChart title="Invoice Status" :data="data.invoiceStatusCounts" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <TeamPerformanceTable :data="data.teamPerformance" />
      </div>
      <RecentActivityChart :data="data.recentActivity" />
    </div>
  </div>
</template>
