<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore } from '@/stores/projects'
import { useTeamStore } from '@/stores/team'
import { useMeetingsStore } from '@/features/meetings/store/meetings'
import { apiErrorMessage } from '@/services/apiError'

const open = defineModel<boolean>('open', { required: true })

const clientsStore = useClientsStore()
const projectsStore = useProjectsStore()
const teamStore = useTeamStore()
const meetingsStore = useMeetingsStore()
const toast = useToast()

onMounted(() => {
  if (clientsStore.clients.length === 0) clientsStore.fetchClients().catch(() => {})
  teamStore.ensureLoaded()
})

const title = ref('')
const date = ref('')
const durationMinutes = ref('30')
const location = ref('Google Meet')
const clientId = ref('')
const projectId = ref('')
const attendeeIds = ref<number[]>([])

const projectsForClient = computed(() =>
  clientId.value ? projectsStore.forClient(clientId.value) : [],
)

watch(clientId, () => {
  projectId.value = ''
})

function toggleAttendee(userId: number, checked: boolean) {
  attendeeIds.value = checked
    ? [...attendeeIds.value, userId]
    : attendeeIds.value.filter((id) => id !== userId)
}

function reset() {
  title.value = ''
  date.value = ''
  durationMinutes.value = '30'
  location.value = 'Google Meet'
  clientId.value = ''
  projectId.value = ''
  attendeeIds.value = []
}

async function create() {
  if (!title.value.trim() || !date.value) return
  try {
    const meeting = await meetingsStore.addMeeting({
      title: title.value.trim(),
      startsAt: new Date(date.value).toISOString(),
      durationMinutes: Number(durationMinutes.value),
      location: location.value.trim(),
      clientId: clientId.value || undefined,
      projectId: projectId.value || undefined,
    })
    for (const userId of attendeeIds.value) {
      await meetingsStore.addAttendee(meeting.id, userId)
    }
    if (attendeeIds.value.length > 0) await meetingsStore.fetchMeeting(meeting.id)
    reset()
    open.value = false
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not create the meeting.'))
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>New Meeting</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <div class="space-y-1.5">
          <Label>Title</Label>
          <Input v-model="title" placeholder="Discovery call" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Date & Time</Label>
            <Input v-model="date" type="datetime-local" />
          </div>
          <div class="space-y-1.5">
            <Label>Duration</Label>
            <Select v-model="durationMinutes">
              <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="15">15 min</SelectItem>
                <SelectItem value="30">30 min</SelectItem>
                <SelectItem value="45">45 min</SelectItem>
                <SelectItem value="60">60 min</SelectItem>
                <SelectItem value="90">90 min</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div class="space-y-1.5">
          <Label>Location</Label>
          <Input v-model="location" placeholder="Google Meet, phone, address…" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Client</Label>
            <Select v-model="clientId">
              <SelectTrigger class="w-full"><SelectValue placeholder="None" /></SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="client in clientsStore.clients"
                  :key="client.id"
                  :value="client.id"
                >
                  {{ client.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <Label>Project</Label>
            <Select v-model="projectId" :disabled="!clientId">
              <SelectTrigger class="w-full"><SelectValue placeholder="None" /></SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="project in projectsForClient"
                  :key="project.id"
                  :value="project.id"
                >
                  {{ project.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div class="space-y-1.5">
          <Label>Attendees</Label>
          <div class="flex flex-wrap gap-3">
            <label
              v-for="member in teamStore.members"
              :key="member.id"
              class="flex items-center gap-1.5 text-sm"
            >
              <Checkbox
                :model-value="attendeeIds.includes(member.id)"
                @update:model-value="(v) => toggleAttendee(member.id, !!v)"
              />
              {{ member.name }}
            </label>
          </div>
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="open = false">Cancel</Button>
        <Button :disabled="!title.trim() || !date" @click="create">Create Meeting</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
