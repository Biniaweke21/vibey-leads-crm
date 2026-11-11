<script setup lang="ts">
import { computed, onMounted } from 'vue'
import DashboardStats from '@/features/dashboard/components/DashboardStats.vue'
import RecentLeadsCard from '@/features/dashboard/components/RecentLeadsCard.vue'
import RemindersCard from '@/features/dashboard/components/RemindersCard.vue'
import RecentActivityChart from '@/features/analytics/components/RecentActivityChart.vue'
import { useAuthStore } from '@/stores/auth'
import { useLeadsStore } from '@/stores/leads'
import { useAnalyticsDataStore } from '@/features/analytics/composables/useAnalyticsData'

const authStore = useAuthStore()
const leadsStore = useLeadsStore()
const analyticsDataStore = useAnalyticsDataStore()

onMounted(() => {
  leadsStore.fetchLeads().catch(() => {})
  analyticsDataStore.fetchSummary().catch(() => {})
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">
        {{ greeting }}{{ authStore.user ? `, ${authStore.user.name}` : '' }} 👋
      </h1>
      <p class="text-muted-foreground mt-1">Here's what's happening across Vibey World today.</p>
    </div>

    <DashboardStats />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <RecentLeadsCard />
      </div>
      <div class="space-y-6">
        <RemindersCard />
        <RecentActivityChart :data="analyticsDataStore.recentActivity" />
      </div>
    </div>
  </div>
</template>
