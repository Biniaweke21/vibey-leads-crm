<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Briefcase, FileStack, Banknote, CalendarDays, Activity as ActivityIcon, History } from '@lucide/vue'
import type { Component } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import EmptyState from '@/components/base/EmptyState.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { apiErrorMessage } from '@/services/apiError'

const toast = useToast()
const portalStore = useClientPortalStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await portalStore.fetchTimeline()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load your timeline.'))
  } finally {
    loading.value = false
  }
})

const moduleIcons: Record<string, Component> = {
  Project: Briefcase,
  Document: FileStack,
  Payment: Banknote,
  Meeting: CalendarDays,
  Client: ActivityIcon,
}

function iconFor(module: string): Component {
  return moduleIcons[module] ?? ActivityIcon
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">Timeline</h1>
      <p class="text-muted-foreground mt-1">
        Project history, milestones, payments, documents, and meetings — all in one place.
      </p>
    </div>

    <Card>
      <CardHeader><CardTitle>History</CardTitle></CardHeader>
      <CardContent>
        <EmptyState
          v-if="!loading && portalStore.timeline.length === 0"
          :icon="History"
          title="Nothing here yet"
        />
        <ol v-else class="space-y-4">
          <li v-for="(entry, i) in portalStore.timeline" :key="i" class="flex gap-3">
            <div class="flex flex-col items-center">
              <span
                class="bg-primary/10 flex size-7 shrink-0 items-center justify-center rounded-full"
              >
                <component :is="iconFor(entry.module)" class="text-primary size-3.5" />
              </span>
              <span class="bg-border mt-1 w-px flex-1" />
            </div>
            <div class="pb-1">
              <p class="text-sm">{{ entry.description }}</p>
              <p class="text-muted-foreground text-xs">{{ formatDate(entry.date) }}</p>
            </div>
          </li>
        </ol>
      </CardContent>
    </Card>
  </div>
</template>
