<script setup lang="ts">
import { onMounted, ref } from 'vue'
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
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { apiErrorMessage } from '@/services/apiError'

const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ booked: [] }>()

const portalStore = useClientPortalStore()
const toast = useToast()

onMounted(() => {
  if (portalStore.projects.length === 0) portalStore.fetchProjects().catch(() => {})
})

const title = ref('')
const date = ref('')
const durationMinutes = ref('30')
const location = ref('Google Meet')
const projectId = ref('')
const description = ref('')
const booking = ref(false)

function reset() {
  title.value = ''
  date.value = ''
  durationMinutes.value = '30'
  location.value = 'Google Meet'
  projectId.value = ''
  description.value = ''
}

async function book() {
  if (!title.value.trim() || !date.value) return
  booking.value = true
  try {
    const startsAt = new Date(date.value)
    const endsAt = new Date(startsAt.getTime() + Number(durationMinutes.value) * 60_000)
    await portalStore.requestMeeting({
      title: title.value.trim(),
      description: description.value.trim() || undefined,
      starts_at: startsAt.toISOString(),
      ends_at: endsAt.toISOString(),
      location: location.value.trim() || undefined,
      project_id: projectId.value ? Number(projectId.value) : undefined,
    })
    toast.success('Meeting booked.')
    emit('booked')
    reset()
    open.value = false
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not book the meeting.'))
  } finally {
    booking.value = false
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Book a Meeting</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <div class="space-y-1.5">
          <Label>Title</Label>
          <Input v-model="title" placeholder="Project check-in" />
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
        <div class="space-y-1.5">
          <Label>Project <span class="text-muted-foreground text-xs">(optional)</span></Label>
          <Select v-model="projectId">
            <SelectTrigger class="w-full"><SelectValue placeholder="None" /></SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="project in portalStore.projects"
                :key="project.id"
                :value="String(project.id)"
              >
                {{ project.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1.5">
          <Label>Notes <span class="text-muted-foreground text-xs">(optional)</span></Label>
          <Textarea v-model="description" placeholder="What would you like to discuss?" />
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="open = false">Cancel</Button>
        <Button :disabled="!title.trim() || !date || booking" @click="book">
          {{ booking ? 'Booking…' : 'Book Meeting' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
