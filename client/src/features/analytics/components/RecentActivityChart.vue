<script setup lang="ts">
import { UserPlus2, Building2, Rocket, FileCheck2, CalendarDays, Activity } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import EmptyState from '@/components/base/EmptyState.vue'
import type { ActivityFeedItem } from '@/features/analytics/types'

defineProps<{ data: ActivityFeedItem[] }>()

const iconByModule: Record<string, typeof UserPlus2> = {
  Lead: UserPlus2,
  Client: Building2,
  Project: Rocket,
  Document: FileCheck2,
  Meeting: CalendarDays,
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Recent Activity</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <EmptyState
        v-if="data.length === 0"
        :icon="Activity"
        title="No recent activity in this range"
      />
      <div v-for="item in data" :key="item.id" class="flex items-start gap-3">
        <component
          :is="iconByModule[item.module] ?? Activity"
          class="text-primary mt-0.5 size-4 shrink-0"
        />
        <div>
          <p class="text-sm">{{ item.text }}</p>
          <p class="text-muted-foreground text-xs">{{ formatDate(item.date) }}</p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
