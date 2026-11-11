<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { FolderKanban, Plus } from '@lucide/vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { apiErrorMessage } from '@/services/apiError'

const router = useRouter()
const toast = useToast()
const portalStore = useClientPortalStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await portalStore.fetchProjects()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load your projects.'))
  } finally {
    loading.value = false
  }
})

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Projects</h1>
        <p class="text-muted-foreground mt-1">Everything Vibey World is building for you.</p>
      </div>
      <Button @click="router.push('/client/projects/new')">
        <Plus class="size-4" /> Start New Project
      </Button>
    </div>

    <EmptyState
      v-if="!loading && portalStore.projects.length === 0"
      :icon="FolderKanban"
      title="No projects yet"
    />

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card
        v-for="project in portalStore.projects"
        :key="project.id"
        class="hover:border-primary/40 cursor-pointer transition-colors"
        @click="router.push(`/client/projects/${project.id}`)"
      >
        <CardContent class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <p class="line-clamp-1 font-semibold">{{ project.name }}</p>
            <Badge variant="secondary">{{ project.status_label }}</Badge>
          </div>
          <div>
            <div class="bg-muted h-1.5 w-full overflow-hidden rounded-full">
              <div
                class="bg-primary h-full rounded-full"
                :style="{ width: `${project.progress}%` }"
              />
            </div>
            <p class="text-muted-foreground mt-1 text-xs">{{ project.progress }}% complete</p>
          </div>
          <p class="text-muted-foreground text-xs">
            {{ formatDate(project.start_date) }} – {{ formatDate(project.end_date) }}
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
