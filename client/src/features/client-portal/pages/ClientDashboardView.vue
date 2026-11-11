<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Briefcase, FileStack, Receipt, CalendarDays, Activity } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import EmptyState from '@/components/base/EmptyState.vue'
import StatCard from '@/components/base/StatCard.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

const router = useRouter()
const toast = useToast()
const portalStore = useClientPortalStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await portalStore.fetchDashboard()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load the dashboard.'))
  } finally {
    loading.value = false
  }
})

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <div v-if="portalStore.dashboard" class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">
        Welcome back, {{ portalStore.dashboard.welcome_name }}
      </h1>
      <p class="text-muted-foreground mt-1">
        Here's where things stand with {{ portalStore.dashboard.company.name }}.
      </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        label="Active Projects"
        :value="portalStore.dashboard.active_projects.length"
        :icon="Briefcase"
      />
      <StatCard
        label="Documents"
        :value="portalStore.dashboard.documents_count"
        :icon="FileStack"
      />
      <StatCard
        label="Outstanding Invoices"
        :value="formatEtb(portalStore.dashboard.outstanding_total)"
        :icon="Receipt"
      />
      <StatCard
        label="Upcoming Meetings"
        :value="portalStore.dashboard.upcoming_meetings.length"
        :icon="CalendarDays"
      />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <Card>
          <CardHeader><CardTitle>Active Projects</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <EmptyState
              v-if="portalStore.dashboard.active_projects.length === 0"
              :icon="Briefcase"
              title="No active projects"
            />
            <div
              v-for="project in portalStore.dashboard.active_projects"
              :key="project.id"
              class="hover:border-primary/40 cursor-pointer rounded-lg border p-3 transition-colors"
              @click="router.push(`/client/projects/${project.id}`)"
            >
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium">{{ project.name }}</p>
                <Badge variant="secondary">{{ project.status_label }}</Badge>
              </div>
              <div class="bg-muted mt-2 h-1.5 w-full overflow-hidden rounded-full">
                <div
                  class="bg-primary h-full rounded-full"
                  :style="{ width: `${project.progress}%` }"
                />
              </div>
              <p class="text-muted-foreground mt-1 text-xs">{{ project.progress }}% complete</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Recent Documents</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <EmptyState
              v-if="portalStore.dashboard.recent_documents.length === 0"
              :icon="FileStack"
              title="No documents yet"
            />
            <div
              v-for="doc in portalStore.dashboard.recent_documents"
              :key="doc.id"
              class="hover:border-primary/40 flex cursor-pointer items-center justify-between gap-2 rounded-lg border p-3 text-sm transition-colors"
              @click="router.push(`/client/documents`)"
            >
              <span>{{ doc.title }}</span>
              <Badge variant="secondary">{{ doc.type_label }}</Badge>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader><CardTitle>Upcoming Meetings</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <EmptyState
              v-if="portalStore.dashboard.upcoming_meetings.length === 0"
              :icon="CalendarDays"
              title="No meetings scheduled"
            />
            <div
              v-for="meeting in portalStore.dashboard.upcoming_meetings"
              :key="meeting.id"
              class="hover:border-primary/40 cursor-pointer rounded-lg border p-3 transition-colors"
              @click="router.push(`/client/meetings/${meeting.id}`)"
            >
              <p class="text-sm font-medium">{{ meeting.title }}</p>
              <p class="text-muted-foreground text-xs">{{ formatDateTime(meeting.starts_at) }}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Recent Activity</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <EmptyState
              v-if="portalStore.dashboard.recent_activity.length === 0"
              :icon="Activity"
              title="No activity yet"
            />
            <div
              v-for="entry in portalStore.dashboard.recent_activity"
              :key="entry.id"
              class="flex items-start gap-2"
            >
              <Activity class="text-primary mt-0.5 size-4 shrink-0" />
              <div>
                <p class="text-sm">{{ entry.description }}</p>
                <p class="text-muted-foreground text-xs">{{ formatDate(entry.created_at) }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
  <div v-else-if="!loading" class="py-20 text-center">
    <p class="text-muted-foreground">Could not load your dashboard.</p>
  </div>
</template>
