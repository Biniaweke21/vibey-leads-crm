<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft, MapPin, Clock, Users, Building2, FolderKanban, UserPlus, Check, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import NotesCard from '@/components/base/NotesCard.vue'
import { useMeetingsStore } from '@/features/meetings/store/meetings'
import { apiErrorMessage } from '@/services/apiError'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const meetingsStore = useMeetingsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await meetingsStore.fetchMeeting(route.params.id as string)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this meeting.'))
  } finally {
    loading.value = false
  }
})

const meeting = computed(() => meetingsStore.getMeeting(route.params.id as string))

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

async function changeStatus(status: 'completed' | 'cancelled') {
  try {
    await meetingsStore.updateStatus(meeting.value!.id, status)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the meeting.'))
  }
}

async function addNote(text: string) {
  try {
    await meetingsStore.addNote(meeting.value!.id, text)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the note.'))
  }
}

async function updateNote(id: string, text: string) {
  try {
    await meetingsStore.updateNote(meeting.value!.id, id, text)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the note.'))
  }
}

async function removeNote(id: string) {
  try {
    await meetingsStore.deleteNote(meeting.value!.id, id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not delete the note.'))
  }
}
</script>

<template>
  <p v-if="loading" class="text-muted-foreground py-20 text-center text-sm">Loading…</p>
  <div v-else-if="!meeting" class="py-20 text-center">
    <p class="text-muted-foreground">Meeting not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/dashboard/meetings')"
      >Back to Meetings</Button
    >
  </div>
  <div v-else class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/dashboard/meetings')">
          <ArrowLeft class="size-5" />
        </Button>
        <h1 class="text-2xl font-bold tracking-tight">{{ meeting.title }}</h1>
      </div>
      <Badge :class="statusClass[meeting.status]" class="text-sm">{{ meeting.status_label }}</Badge>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
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
            <p v-if="meeting.client" class="flex items-center gap-2">
              <span class="text-muted-foreground">Client availability:</span>
              <Badge
                v-if="meeting.client_response"
                :class="
                  meeting.client_response === 'available'
                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'
                    : 'bg-red-500/10 text-red-600 border-red-500/20'
                "
              >
                {{ meeting.client_response_label }}
              </Badge>
              <Badge v-else class="bg-amber-500/10 text-amber-600 border-amber-500/20">Pending</Badge>
              <span v-if="meeting.client_response_note" class="text-muted-foreground text-xs"
                >— "{{ meeting.client_response_note }}"</span
              >
            </p>
            <button
              v-if="meeting.client"
              class="text-primary flex items-center gap-2 hover:underline"
              @click="router.push(`/dashboard/clients/${meeting.client.id}`)"
            >
              <Building2 class="size-4" /> {{ meeting.client.name }}
            </button>
            <button
              v-if="meeting.project"
              class="text-primary flex items-center gap-2 hover:underline"
              @click="router.push(`/dashboard/projects/${meeting.project.id}`)"
            >
              <FolderKanban class="size-4" /> {{ meeting.project.name }}
            </button>
            <button
              v-if="meeting.lead"
              class="text-primary flex items-center gap-2 hover:underline"
              @click="router.push(`/dashboard/leads/${meeting.lead.id}`)"
            >
              <UserPlus class="size-4" /> {{ meeting.lead.name }}
            </button>
          </CardContent>
        </Card>

        <NotesCard
          title="Meeting Notes"
          :notes="meeting.notes"
          @add="addNote"
          @update="updateNote"
          @remove="removeNote"
        />
      </div>

      <Card>
        <CardHeader><CardTitle>Quick Actions</CardTitle></CardHeader>
        <CardContent class="flex flex-col gap-2">
          <Button
            variant="outline"
            size="sm"
            :disabled="meeting.status === 'completed'"
            @click="changeStatus('completed')"
          >
            <Check class="size-4" /> Mark Completed
          </Button>
          <Button
            variant="outline"
            size="sm"
            :disabled="meeting.status === 'cancelled'"
            @click="changeStatus('cancelled')"
          >
            <X class="size-4" /> Cancel Meeting
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
