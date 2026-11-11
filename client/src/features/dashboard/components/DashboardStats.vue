<script setup lang="ts">
import { computed } from 'vue'
import { UsersRound, Briefcase, UserCheck, DollarSign } from '@lucide/vue'
import StatCard from '@/components/base/StatCard.vue'
import { useLeadsStore, wonStatuses } from '@/stores/leads'
import { useAuthStore } from '@/stores/auth'
import { useAnalyticsDataStore } from '@/features/analytics/composables/useAnalyticsData'
import { formatEtb } from '@/utils/currency'

const leadsStore = useLeadsStore()
const authStore = useAuthStore()
const analyticsDataStore = useAnalyticsDataStore()

const wonLeads = computed(
  () => leadsStore.leads.filter((l) => wonStatuses.includes(l.status)).length,
)
</script>

<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <StatCard label="Total Leads" :value="leadsStore.leads.length" :icon="UsersRound" />
    <StatCard label="Active Projects" :value="analyticsDataStore.activeProjects" :icon="Briefcase" />
    <StatCard label="Won Leads" :value="wonLeads" :icon="UserCheck" />
    <StatCard
      v-if="authStore.isAdmin"
      label="Revenue"
      :value="formatEtb(analyticsDataStore.revenue)"
      :icon="DollarSign"
    />
  </div>
</template>
