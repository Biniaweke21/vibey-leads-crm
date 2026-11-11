<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Flag } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import EmptyState from '@/components/base/EmptyState.vue'
import { useProjectsStore, type Project } from '@/stores/projects'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ project: Project }>()
const projectsStore = useProjectsStore()
const toast = useToast()

const title = ref('')
const dueDate = ref('')

async function addMilestone() {
  if (!title.value.trim() || !dueDate.value) return
  try {
    await projectsStore.addMilestone(
      props.project.id,
      title.value.trim(),
      new Date(dueDate.value).toISOString(),
    )
    title.value = ''
    dueDate.value = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the milestone.'))
  }
}

async function toggleMilestone(milestoneId: string) {
  try {
    await projectsStore.toggleMilestone(props.project.id, milestoneId)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the milestone.'))
  }
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
  <Card>
    <CardHeader>
      <CardTitle>Milestones</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="flex gap-2">
        <Input v-model="title" placeholder="Milestone title" class="flex-1" />
        <Input v-model="dueDate" type="date" class="w-40" />
        <Button size="sm" :disabled="!title.trim() || !dueDate" @click="addMilestone">Add</Button>
      </div>

      <EmptyState v-if="project.milestones.length === 0" :icon="Flag" title="No milestones yet" />
      <ul v-else class="space-y-2">
        <li
          v-for="milestone in project.milestones"
          :key="milestone.id"
          class="flex items-center gap-3 rounded-lg border p-3"
        >
          <Checkbox
            :model-value="milestone.done"
            @update:model-value="toggleMilestone(milestone.id)"
          />
          <div class="flex-1">
            <p class="text-sm" :class="milestone.done ? 'text-muted-foreground line-through' : ''">
              {{ milestone.title }}
            </p>
            <p class="text-muted-foreground text-xs">{{ formatDate(milestone.dueDate) }}</p>
          </div>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
