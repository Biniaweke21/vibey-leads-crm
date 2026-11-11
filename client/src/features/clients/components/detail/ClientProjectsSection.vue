<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Briefcase, Plus } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import CreateProjectDialog from '@/features/projects/components/CreateProjectDialog.vue'
import { projectStatuses } from '@/stores/projects'
import { useClientsStore } from '@/stores/clients'
import { projectStatusBadgeClass } from '@/features/projects/data/statusMeta'
import type { Client } from '@/stores/clients'

const props = defineProps<{ client: Client }>()
const router = useRouter()
const clientsStore = useClientsStore()
const createOpen = ref(false)

function statusLabel(status: string) {
  return projectStatuses.find((s) => s.value === status)?.label ?? status
}

// Refresh so the new project (and the now-nonzero active-projects count)
// shows immediately without a manual page reload.
function onCreated() {
  clientsStore.fetchClient(props.client.id).catch(() => {})
}
</script>

<template>
  <Card>
    <CardHeader class="flex flex-row items-center justify-between">
      <CardTitle>Projects</CardTitle>
      <Button size="sm" variant="outline" @click="createOpen = true">
        <Plus class="size-4" /> New Project
      </Button>
    </CardHeader>
    <CardContent class="space-y-3">
      <CreateProjectDialog v-model:open="createOpen" :default-client-id="client.id" @created="onCreated" />
      <EmptyState v-if="props.client.projects.length === 0" :icon="Briefcase" title="No projects yet" />
      <div
        v-for="project in props.client.projects"
        :key="project.id"
        class="hover:border-primary/40 cursor-pointer rounded-lg border p-3 transition-colors"
        @click="router.push(`/dashboard/projects/${project.id}`)"
      >
        <div class="flex items-center justify-between gap-2">
          <p class="text-sm font-medium">{{ project.name }}</p>
          <Badge :class="projectStatusBadgeClass[project.status]">{{
            statusLabel(project.status)
          }}</Badge>
        </div>
        <div class="bg-muted mt-2 h-1.5 w-full overflow-hidden rounded-full">
          <div class="bg-primary h-full rounded-full" :style="{ width: `${project.progress}%` }" />
        </div>
        <p class="text-muted-foreground mt-1 text-xs">{{ project.progress }}% complete</p>
      </div>
    </CardContent>
  </Card>
</template>
