<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { ListChecks } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import EmptyState from '@/components/base/EmptyState.vue'
import { useProjectsStore, type Project, type ProjectTask } from '@/stores/projects'
import { useTeamStore } from '@/stores/team'
import { taskStatusBadgeClass } from '@/features/projects/data/statusMeta'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ project: Project }>()
const projectsStore = useProjectsStore()
const teamStore = useTeamStore()
const toast = useToast()

onMounted(() => teamStore.ensureLoaded())

const title = ref('')
const assignedTo = ref('')

async function addTask() {
  if (!title.value.trim()) return
  try {
    await projectsStore.addTask(
      props.project.id,
      title.value.trim(),
      assignedTo.value ? Number(assignedTo.value) : null,
    )
    title.value = ''
    assignedTo.value = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the task.'))
  }
}

async function changeStatus(taskId: string, value: unknown) {
  try {
    await projectsStore.updateTaskStatus(props.project.id, taskId, value as ProjectTask['status'])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the task.'))
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Tasks</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="flex flex-col gap-2 sm:flex-row">
        <Input v-model="title" placeholder="Task title" class="flex-1" />
        <Select v-model="assignedTo">
          <SelectTrigger class="sm:w-44"><SelectValue placeholder="Unassigned" /></SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="member in teamStore.members"
              :key="member.id"
              :value="String(member.id)"
              >{{ member.name }}</SelectItem
            >
          </SelectContent>
        </Select>
        <Button size="sm" :disabled="!title.trim()" @click="addTask">Add</Button>
      </div>

      <EmptyState v-if="project.tasks.length === 0" :icon="ListChecks" title="No tasks yet" />
      <div v-else class="space-y-2">
        <div
          v-for="task in project.tasks"
          :key="task.id"
          class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"
        >
          <div>
            <p class="font-medium">{{ task.title }}</p>
            <p class="text-muted-foreground text-xs">{{ task.assignedTo }}</p>
          </div>
          <Select :model-value="task.status" @update:model-value="(v) => changeStatus(task.id, v)">
            <SelectTrigger class="w-36">
              <Badge :class="taskStatusBadgeClass[task.status]">{{ task.status }}</Badge>
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="todo">To Do</SelectItem>
              <SelectItem value="in-progress">In Progress</SelectItem>
              <SelectItem value="done">Done</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
