<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Clock } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import EmptyState from '@/components/base/EmptyState.vue'
import { useRemindersStore } from '@/stores/reminders'

const remindersStore = useRemindersStore()

onMounted(() => {
  remindersStore.fetchReminders().catch(() => {})
})

const buckets = computed(() => [
  {
    label: 'Overdue',
    items: remindersStore.overdue,
    badgeClass: 'bg-red-500/10 text-red-600 border-red-500/20',
  },
  {
    label: 'Due Today',
    items: remindersStore.dueToday,
    badgeClass: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  },
  {
    label: 'Upcoming',
    items: remindersStore.upcoming,
    badgeClass: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  },
])

const hasAny = computed(() => buckets.value.some((b) => b.items.length > 0))

function formatDue(iso: string) {
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
      <CardTitle>Upcoming Reminders</CardTitle>
    </CardHeader>
    <CardContent class="space-y-5">
      <EmptyState v-if="!hasAny" :icon="Clock" title="No reminders" />
      <template v-else>
        <div v-for="bucket in buckets" :key="bucket.label">
          <div v-if="bucket.items.length > 0" class="mb-2 flex items-center gap-2">
            <Badge :class="bucket.badgeClass">{{ bucket.label }}</Badge>
          </div>
          <div
            v-for="reminder in bucket.items"
            :key="reminder.id"
            class="mb-3 flex items-start gap-3 last:mb-0"
          >
            <Clock class="text-primary mt-0.5 size-4 shrink-0" />
            <div>
              <p class="text-sm font-medium">{{ reminder.title }}</p>
              <p class="text-muted-foreground text-xs">{{ formatDue(reminder.dueAt) }}</p>
            </div>
          </div>
        </div>
      </template>
    </CardContent>
  </Card>
</template>
