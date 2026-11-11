<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft, MapPin, Clock, Users, FolderKanban, Check, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { apiErrorMessage } from '@/services/apiError'
import type { PortalMeeting } from '@/features/client-portal/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const portalStore = useClientPortalStore()

const meeting = ref<PortalMeeting | null>(null)
const loading = ref(true)
const responding = ref(false)
const showUnavailableNote = ref(false)
const unavailableNote = ref('')

onMounted(async () => {
  try {
    meeting.value = await portalStore.fetchMeeting(route.params.id as string)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this meeting.'))
  } finally {
    loading.value = false
  }
})

async function respond(response: 'available' | 'unavailable', note?: string) {
  responding.value = true
  try {
    meeting.value = await portalStore.respondToMeeting(meeting.value!.id, response, note)
    showUnavailableNote.value = false
    unavailableNote.value = ''
    toast.success(response === 'available' ? 'Thanks — marked as available.' : 'Team notified you\'re unavailable.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not record your response.'))
  } finally {
    responding.value = false
  }
}

const statusClass: Record<string, string> = {
  upcoming: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  completed: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
  cancelled: 'bg-red-500/10 text-red-600 border-red-500/20',
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <div v-if="!loading && !meeting" class="py-20 text-center">
    <p class="text-muted-foreground">Meeting not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/client/meetings')"
      >Back to Meetings</Button
    >
  </div>
  <div v-else-if="meeting" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/client/meetings')">
          <ArrowLeft class="size-5" />
        </Button>
        <h1 class="text-2xl font-bold tracking-tight">{{ meeting.title }}</h1>
      </div>
      <Badge :class="statusClass[meeting.status]" class="text-sm">{{ meeting.status_label }}</Badge>
    </div>

    <Card>
      <CardHeader><CardTitle>Details</CardTitle></CardHeader>
      <CardContent class="space-y-3 text-sm">
        <p class="flex items-center gap-2">
          <Clock class="text-muted-foreground size-4" /> {{ formatDate(meeting.starts_at) }}
          <template v-if="meeting.duration_minutes"> · {{ meeting.duration_minutes }} min</template>
        </p>
        <p v-if="meeting.location" class="flex items-center gap-2">
          <MapPin class="text-muted-foreground size-4" /> {{ meeting.location }}
        </p>
        <p v-if="meeting.attendees.length > 0" class="flex items-center gap-2">
          <Users class="text-muted-foreground size-4" />
          {{ meeting.attendees.map((a) => a.name).join(', ') }}
        </p>
        <p v-if="meeting.project" class="flex items-center gap-2">
          <FolderKanban class="text-muted-foreground size-4" /> {{ meeting.project.name }}
        </p>
      </CardContent>
    </Card>

    <Card v-if="meeting.status === 'upcoming'">
      <CardHeader><CardTitle>Your Availability</CardTitle></CardHeader>
      <CardContent class="space-y-3">
        <p v-if="meeting.client_response" class="text-sm">
          You marked yourself as
          <span class="font-medium">{{ meeting.client_response_label }}</span>
          <template v-if="meeting.client_response_note"> — "{{ meeting.client_response_note }}"</template>
        </p>
        <div v-else class="flex flex-wrap gap-2">
          <Button size="sm" :disabled="responding" @click="respond('available')">
            <Check class="size-4" /> I'm Available
          </Button>
          <Button
            variant="outline"
            size="sm"
            :disabled="responding"
            @click="showUnavailableNote = true"
          >
            <X class="size-4" /> Not Available
          </Button>
        </div>
        <div v-if="showUnavailableNote" class="space-y-2">
          <Textarea
            v-model="unavailableNote"
            placeholder="Optional — let the team know why, or suggest another time"
          />
          <Button
            variant="destructive"
            size="sm"
            :disabled="responding"
            @click="respond('unavailable', unavailableNote || undefined)"
          >
            {{ responding ? 'Sending…' : 'Confirm Not Available' }}
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
