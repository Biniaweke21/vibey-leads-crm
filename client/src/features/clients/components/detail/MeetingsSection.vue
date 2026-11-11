<script setup lang="ts">
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { CalendarPlus2, CalendarClock } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import type { Client } from '@/stores/clients'
import { useMeetingsStore } from '@/features/meetings/store/meetings'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ client: Client }>()
const meetingsStore = useMeetingsStore()
const toast = useToast()

const meetings = computed(() =>
  [...meetingsStore.forClient(props.client.id)].sort((a, b) => b.starts_at.localeCompare(a.starts_at)),
)

const title = ref('')
const date = ref('')
const notes = ref('')

async function addMeeting() {
  if (!title.value.trim() || !date.value) return
  try {
    const meeting = await meetingsStore.addMeeting({
      title: title.value.trim(),
      startsAt: new Date(date.value).toISOString(),
      durationMinutes: 30,
      location: 'Google Meet',
      clientId: props.client.id,
    })
    if (notes.value.trim()) {
      await meetingsStore.addNote(meeting.id, notes.value.trim())
    }
    title.value = ''
    date.value = ''
    notes.value = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not log the meeting.'))
  }
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Meetings</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-2">
        <Input v-model="title" placeholder="Meeting title" class="w-full" />
        <Input v-model="date" type="datetime-local" class="w-full" />
        <Textarea v-model="notes" placeholder="Notes (optional)" rows="2" />
        <Button size="sm" :disabled="!title.trim() || !date" @click="addMeeting">
          <CalendarPlus2 class="size-4" /> Log Meeting
        </Button>
      </div>

      <EmptyState v-if="meetings.length === 0" :icon="CalendarClock" title="No meetings logged" />
      <div v-else class="space-y-2">
        <RouterLink
          v-for="meeting in meetings"
          :key="meeting.id"
          :to="`/dashboard/meetings/${meeting.id}`"
          class="hover:border-primary/40 block rounded-lg border p-3 transition-colors"
        >
          <p class="text-sm font-medium">{{ meeting.title }}</p>
          <p class="text-muted-foreground text-xs">{{ formatDate(meeting.starts_at) }}</p>
        </RouterLink>
      </div>
    </CardContent>
  </Card>
</template>
