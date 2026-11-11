<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { useClientsStore, clientStatuses } from '@/stores/clients'
import { useMeetingsStore } from '@/features/meetings/store/meetings'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { clientStatusBadgeClass } from '@/features/clients/data/statusMeta'
import NotesCard from '@/components/base/NotesCard.vue'
import AttachmentsCard from '@/components/base/AttachmentsCard.vue'
import ActivityTimelineCard from '@/components/base/ActivityTimelineCard.vue'
import CompanyProfileCard from '@/features/clients/components/detail/CompanyProfileCard.vue'
import ContactsSection from '@/features/clients/components/detail/ContactsSection.vue'
import ClientProjectsSection from '@/features/clients/components/detail/ClientProjectsSection.vue'
import ClientDocumentsSection from '@/features/clients/components/detail/ClientDocumentsSection.vue'
import MeetingsSection from '@/features/clients/components/detail/MeetingsSection.vue'
import PaymentsSummary from '@/features/clients/components/detail/PaymentsSummary.vue'
import PortalAccountSection from '@/features/clients/components/detail/PortalAccountSection.vue'
import { apiErrorMessage } from '@/services/apiError'
import type { UploadedFileMeta } from '@/utils/attachment'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const clientsStore = useClientsStore()
const meetingsStore = useMeetingsStore()
const documentsStore = useDocumentsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await clientsStore.fetchClient(route.params.id as string)
    // MeetingsSection and ClientDocumentsSection read from their own global
    // stores — load them scoped to this client so both cards show real data
    // on a direct visit/refresh, not just after browsing via their list pages.
    await Promise.all([
      meetingsStore.fetchMeetings().catch(() => {}),
      documentsStore.fetchDocuments({ client_id: route.params.id }).catch(() => {}),
    ])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this client.'))
  } finally {
    loading.value = false
  }
})

const client = computed(() => clientsStore.getClient(route.params.id as string))

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function statusLabel(status: string) {
  return clientStatuses.find((s) => s.value === status)?.label ?? status
}

async function addNote(text: string) {
  try {
    await clientsStore.addNote(client.value!.id, text)
    toast.success('Note added.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the note.'))
  }
}
async function updateNote(id: string, text: string) {
  try {
    await clientsStore.updateNote(client.value!.id, id, text)
    toast.success('Note updated.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the note.'))
  }
}
async function removeNote(id: string) {
  try {
    await clientsStore.deleteNote(client.value!.id, id)
    toast.success('Note deleted.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not delete the note.'))
  }
}
async function addAttachments(files: UploadedFileMeta[]) {
  try {
    await clientsStore.addAttachments(client.value!.id, files)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the file.'))
  }
}
function activityLogNotSupported() {
  toast.info('Activity entries are recorded automatically and can’t be added manually.')
}
</script>

<template>
  <p v-if="loading" class="text-muted-foreground py-20 text-center text-sm">Loading…</p>
  <div v-else-if="!client" class="py-20 text-center">
    <p class="text-muted-foreground">Client not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/dashboard/clients')"
      >Back to Clients</Button
    >
  </div>
  <div v-else class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/dashboard/clients')">
          <ArrowLeft class="size-5" />
        </Button>
        <Avatar class="size-10">
          <AvatarFallback>{{ initials(client.name) }}</AvatarFallback>
        </Avatar>
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ client.name }}</h1>
          <p class="text-muted-foreground text-sm">{{ client.industry }} · {{ client.city }}</p>
        </div>
      </div>
      <Badge :class="clientStatusBadgeClass[client.status]" class="text-sm">{{
        statusLabel(client.status)
      }}</Badge>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <CompanyProfileCard :client="client" />
        <ContactsSection :client="client" />
        <ClientProjectsSection :client="client" />
        <ClientDocumentsSection :client="client" />
        <AttachmentsCard
          title="Files"
          :files="client.documents"
          addable
          @add="addAttachments"
        />
        <NotesCard :notes="client.notes" @add="addNote" @update="updateNote" @remove="removeNote" />
      </div>
      <div class="space-y-6">
        <PortalAccountSection :client="client" />
        <PaymentsSummary :client="client" />
        <MeetingsSection :client="client" />
        <ActivityTimelineCard :activity="client.activity" @log="activityLogNotSupported" />
      </div>
    </div>
  </div>
</template>
