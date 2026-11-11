<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { useProjectsStore, projectStatuses, type Project } from '@/stores/projects'
import { useClientsStore } from '@/stores/clients'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ project: Project }>()
const router = useRouter()
const toast = useToast()
const projectsStore = useProjectsStore()
const clientsStore = useClientsStore()

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
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

async function changeStatus(value: unknown) {
  try {
    await projectsStore.updateStatus(props.project.id, value as Project['status'])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the status.'))
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Overview</CardTitle>
    </CardHeader>
    <CardContent class="space-y-5">
      <p class="text-sm">{{ project.description }}</p>

      <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div>
          <p class="text-muted-foreground text-xs">Client</p>
          <button
            class="text-primary font-medium hover:underline"
            @click="router.push(`/dashboard/clients/${project.clientId}`)"
          >
            {{ clientsStore.getClient(project.clientId)?.name ?? 'Unknown Client' }}
          </button>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Budget</p>
          <p class="font-medium">{{ formatEtb(project.budget) }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Timeline</p>
          <p class="font-medium">
            {{ formatDate(project.startDate) }} – {{ formatDate(project.endDate) }}
          </p>
        </div>
      </div>

      <div>
        <p class="text-muted-foreground mb-2 text-xs">Team Members</p>
        <div class="flex flex-wrap items-center gap-2">
          <div
            v-for="member in project.team"
            :key="member"
            class="bg-muted flex items-center gap-2 rounded-full py-1 pr-3 pl-1"
          >
            <Avatar class="size-6">
              <AvatarFallback class="text-[10px]">{{ initials(member) }}</AvatarFallback>
            </Avatar>
            <span class="text-xs font-medium">{{ member }}</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <p class="text-muted-foreground mb-2 text-xs">Status</p>
          <Select :model-value="project.status" @update:model-value="changeStatus">
            <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="s in projectStatuses" :key="s.value" :value="s.value">{{
                s.label
              }}</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div>
          <p class="text-muted-foreground mb-2 text-xs">Progress — {{ project.progress }}%</p>
          <div class="bg-muted h-2 w-full overflow-hidden rounded-full">
            <div
              class="bg-primary h-full rounded-full"
              :style="{ width: `${project.progress}%` }"
            />
          </div>
          <p class="text-muted-foreground mt-1 text-xs">
            Computed automatically from completed tasks and milestones.
          </p>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
