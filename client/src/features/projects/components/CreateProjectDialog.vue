<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore, projectStatuses, type ProjectStatus, type Project } from '@/stores/projects'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ defaultClientId?: string }>()
const emit = defineEmits<{ created: [project: Project] }>()
const open = defineModel<boolean>('open', { required: true })

const clientsStore = useClientsStore()
const projectsStore = useProjectsStore()
const toast = useToast()

onMounted(() => {
  if (clientsStore.clients.length === 0) clientsStore.fetchClients().catch(() => {})
})

const clientId = ref('')
const name = ref('')
const description = ref('')
const status = ref<ProjectStatus>('planning')
const budget = ref('')
const startDate = ref('')
const endDate = ref('')
const saving = ref(false)

// Pre-select the client when opened from a client's own page — matches
// e.g. CreateMeetingDialog's client pre-fill pattern used elsewhere.
watch(open, (isOpen) => {
  if (isOpen && props.defaultClientId) clientId.value = props.defaultClientId
})

function reset() {
  clientId.value = ''
  name.value = ''
  description.value = ''
  status.value = 'planning'
  budget.value = ''
  startDate.value = ''
  endDate.value = ''
}

async function create() {
  if (!clientId.value || !name.value.trim()) return
  saving.value = true
  try {
    const project = await projectsStore.createProject({
      clientId: clientId.value,
      name: name.value.trim(),
      description: description.value,
      status: status.value,
      budget: budget.value ? Number(budget.value) : undefined,
      startDate: startDate.value,
      endDate: endDate.value,
    })
    toast.success(`${project.name} created.`)
    emit('created', project)
    reset()
    open.value = false
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not create the project.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>New Project</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <div class="space-y-1.5">
          <Label>Client</Label>
          <Select v-model="clientId">
            <SelectTrigger class="w-full"><SelectValue placeholder="Select a client" /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="client in clientsStore.clients" :key="client.id" :value="client.id">
                {{ client.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1.5">
          <Label>Project Name</Label>
          <Input v-model="name" placeholder="Website Redesign" />
        </div>
        <div class="space-y-1.5">
          <Label>Description</Label>
          <Textarea v-model="description" placeholder="Optional details about the project" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Status</Label>
            <Select v-model="status">
              <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="s in projectStatuses" :key="s.value" :value="s.value">{{
                  s.label
                }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <Label>Budget (ETB)</Label>
            <Input v-model="budget" type="number" min="0" placeholder="50000" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Start Date</Label>
            <Input v-model="startDate" type="date" />
          </div>
          <div class="space-y-1.5">
            <Label>End Date</Label>
            <Input v-model="endDate" type="date" />
          </div>
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="open = false">Cancel</Button>
        <Button :disabled="!clientId || !name.trim() || saving" @click="create">
          {{ saving ? 'Creating…' : 'Create Project' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
