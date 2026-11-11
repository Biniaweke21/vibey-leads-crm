<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Search, FolderKanban, Plus } from '@lucide/vue'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import EmptyState from '@/components/base/EmptyState.vue'
import CreateProjectDialog from '@/features/projects/components/CreateProjectDialog.vue'
import { useProjectsStore, projectStatuses, type Project } from '@/stores/projects'
import { useClientsStore } from '@/stores/clients'
import { projectStatusBadgeClass } from '@/features/projects/data/statusMeta'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

const router = useRouter()
const toast = useToast()
const projectsStore = useProjectsStore()
const clientsStore = useClientsStore()
const loading = ref(true)
const createOpen = ref(false)

onMounted(async () => {
  try {
    await Promise.all([projectsStore.fetchProjects(), clientsStore.fetchClients()])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load projects.'))
  } finally {
    loading.value = false
  }
})

const search = ref('')
const statusFilter = ref('all')
const clientFilter = ref('all')

const filteredProjects = computed(() =>
  projectsStore.projects.filter((p) => {
    const query = search.value.trim().toLowerCase()
    const matchesSearch = !query || p.name.toLowerCase().includes(query)
    const matchesStatus = statusFilter.value === 'all' || p.status === statusFilter.value
    const matchesClient = clientFilter.value === 'all' || p.clientId === clientFilter.value
    return matchesSearch && matchesStatus && matchesClient
  }),
)

function clientName(clientId: string) {
  return clientsStore.getClient(clientId)?.name ?? 'Unknown Client'
}

function statusLabel(status: Project['status']) {
  return projectStatuses.find((s) => s.value === status)?.label ?? status
}

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function formatDate(iso: string | null) {
  if (!iso) return 'Not set'
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function openProject(project: Project) {
  router.push(`/dashboard/projects/${project.id}`)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Projects</h1>
        <p class="text-muted-foreground mt-1">
          Everything currently in build, on hold, or delivered.
        </p>
      </div>
      <Button @click="createOpen = true"><Plus class="size-4" /> New Project</Button>
    </div>

    <CreateProjectDialog v-model:open="createOpen" />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div class="relative w-full sm:max-w-xs">
        <Search
          class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
        />
        <Input v-model="search" placeholder="Search projects…" class="pl-9" />
      </div>
      <Select v-model="statusFilter">
        <SelectTrigger class="w-full sm:w-48"><SelectValue placeholder="Status" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem v-for="s in projectStatuses" :key="s.value" :value="s.value">{{
            s.label
          }}</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="clientFilter">
        <SelectTrigger class="w-full sm:w-56"><SelectValue placeholder="Client" /></SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All clients</SelectItem>
          <SelectItem v-for="c in clientsStore.clients" :key="c.id" :value="c.id">{{
            c.name
          }}</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <p v-if="loading" class="text-muted-foreground py-10 text-center text-sm">
      Loading projects…
    </p>

    <EmptyState
      v-else-if="filteredProjects.length === 0"
      :icon="FolderKanban"
      title="No projects match your filters"
      description="Try adjusting your search or filters."
    />

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card
        v-for="project in filteredProjects"
        :key="project.id"
        class="hover:border-primary/40 cursor-pointer transition-colors"
        @click="openProject(project)"
      >
        <CardContent class="space-y-3">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="line-clamp-1 font-semibold">{{ project.name }}</p>
              <p class="text-muted-foreground text-xs">{{ clientName(project.clientId) }}</p>
            </div>
            <Badge :class="projectStatusBadgeClass[project.status]">{{
              statusLabel(project.status)
            }}</Badge>
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

          <div class="flex items-center justify-between border-t pt-3 text-sm">
            <div>
              <p class="text-muted-foreground text-xs">Budget</p>
              <p class="font-medium">{{ formatEtb(project.budget) }}</p>
            </div>
            <div class="flex -space-x-2">
              <Avatar
                v-for="member in project.team"
                :key="member"
                class="border-background size-7 border-2"
              >
                <AvatarFallback class="text-[10px]">{{ initials(member) }}</AvatarFallback>
              </Avatar>
            </div>
          </div>
          <p class="text-muted-foreground text-xs">
            {{ formatDate(project.startDate) }} – {{ formatDate(project.endDate) }}
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
