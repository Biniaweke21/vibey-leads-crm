<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft, Sparkles } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useLeadsStore, leadStatuses, leadPriorities } from '@/stores/leads'
import {
  statusBadgeClass,
  priorityBadgeClass,
  aiStatusBadgeClass,
} from '@/features/leads/data/statusMeta'
import ClientInfoCard from '@/features/leads/components/detail/ClientInfoCard.vue'
import QuickActionsPanel from '@/features/leads/components/detail/QuickActionsPanel.vue'
import AiAnalysisPanel from '@/features/leads/components/detail/AiAnalysisPanel.vue'
import NotesCard from '@/components/base/NotesCard.vue'
import AttachmentsCard from '@/components/base/AttachmentsCard.vue'
import DocumentsCard from '@/components/base/DocumentsCard.vue'
import ActivityTimelineCard from '@/components/base/ActivityTimelineCard.vue'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { apiErrorMessage } from '@/services/apiError'
import type { UploadedFileMeta } from '@/utils/attachment'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const leadsStore = useLeadsStore()
const documentsStore = useDocumentsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    const loaded = await leadsStore.fetchLead(route.params.id as string)
    await fetchDocuments(loaded.convertedClientId, loaded.projectId)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this lead.'))
  } finally {
    loading.value = false
  }
})

const lead = computed(() => leadsStore.getLead(route.params.id as string))

// Proposal/contract/invoice for this lead's own converted client+project —
// stays visible permanently (not just right after generating), same as
// AttachmentsCard, instead of disappearing once you leave the AI panel.
const leadDocuments = computed(() =>
  documentsStore.documents.filter(
    (d) => lead.value?.convertedClientId && d.clientId === lead.value.convertedClientId,
  ),
)

async function fetchDocuments(clientId: string | null, projectId: string | null) {
  if (!clientId) return
  await documentsStore.fetchDocuments({ client_id: clientId, project_id: projectId || undefined })
}

const attachments = computed(() => [
  ...(lead.value?.designFiles ?? []),
  ...(lead.value?.generalFiles ?? []),
])

function statusLabel(status: string) {
  return leadStatuses.find((s) => s.value === status)?.label ?? status
}

function priorityLabel(priority: string) {
  return leadPriorities.find((p) => p.value === priority)?.label ?? priority
}

async function addNote(text: string) {
  try {
    await leadsStore.addNote(lead.value!.id, text)
    toast.success('Note added.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the note.'))
  }
}

async function updateNote(id: string, text: string) {
  try {
    await leadsStore.updateNote(lead.value!.id, id, text)
    toast.success('Note updated.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the note.'))
  }
}

async function removeNote(id: string) {
  try {
    await leadsStore.deleteNote(lead.value!.id, id)
    toast.success('Note deleted.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not delete the note.'))
  }
}

async function addAttachments(files: UploadedFileMeta[]) {
  try {
    await leadsStore.addAttachments(lead.value!.id, files)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the file.'))
  }
}

// Free-text activity log entries aren't backed by the API yet.
function activityLogNotSupported() {
  toast.info('Activity entries are recorded automatically and can’t be added manually.')
}
</script>

<template>
  <p v-if="loading" class="text-muted-foreground py-20 text-center text-sm">Loading…</p>
  <div v-else-if="!lead" class="py-20 text-center">
    <p class="text-muted-foreground">Lead not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/dashboard/leads')"
      >Back to Leads</Button
    >
  </div>
  <div v-else class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/dashboard/leads')">
          <ArrowLeft class="size-5" />
        </Button>
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ lead.businessName }}</h1>
          <p class="text-muted-foreground text-sm">{{ lead.fullName }} · {{ lead.email }}</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Badge :class="statusBadgeClass[lead.status]" class="text-sm">{{
          statusLabel(lead.status)
        }}</Badge>
        <Badge :class="priorityBadgeClass[lead.priority]" class="text-sm">{{
          priorityLabel(lead.priority)
        }}</Badge>
        <Badge :class="aiStatusBadgeClass[lead.aiAnalysisStatus]" class="text-sm">
          <Sparkles v-if="lead.aiAnalysisStatus === 'ready'" class="size-3" />
          AI: {{ lead.aiAnalysisStatus === 'ready' ? 'Ready' : 'Pending' }}
        </Badge>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <ClientInfoCard :lead="lead" />
        <div id="ai-analysis">
          <AiAnalysisPanel
            :lead="lead"
            @generated="fetchDocuments(lead.convertedClientId, lead.projectId)"
          />
        </div>
        <DocumentsCard :documents="leadDocuments" title="Proposals & Documents" />
        <NotesCard :notes="lead.notes" @add="addNote" @update="updateNote" @remove="removeNote" />
        <AttachmentsCard :files="attachments" addable @add="addAttachments" />
      </div>
      <div class="space-y-6">
        <QuickActionsPanel :lead="lead" />
        <ActivityTimelineCard :activity="lead.activity" @log="activityLogNotSupported" />
      </div>
    </div>
  </div>
</template>
