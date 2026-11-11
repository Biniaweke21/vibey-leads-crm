<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useProjectsStore, projectStatuses } from '@/stores/projects'
import { useClientsStore } from '@/stores/clients'
import { useLeadsStore } from '@/stores/leads'
import { projectStatusBadgeClass } from '@/features/projects/data/statusMeta'
import AttachmentsCard from '@/components/base/AttachmentsCard.vue'
import DocumentsCard from '@/components/base/DocumentsCard.vue'
import ActivityTimelineCard from '@/components/base/ActivityTimelineCard.vue'
import DesignReviewCard from '@/components/base/DesignReviewCard.vue'
import ProjectOverviewCard from '@/features/projects/components/detail/ProjectOverviewCard.vue'
import MilestonesSection from '@/features/projects/components/detail/MilestonesSection.vue'
import TasksSection from '@/features/projects/components/detail/TasksSection.vue'
import AiAnalysisPanel from '@/features/leads/components/detail/AiAnalysisPanel.vue'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { apiErrorMessage } from '@/services/apiError'
import type { UploadedFileMeta } from '@/utils/attachment'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const projectsStore = useProjectsStore()
const clientsStore = useClientsStore()
const leadsStore = useLeadsStore()
const documentsStore = useDocumentsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    const project = await projectsStore.fetchProject(route.params.id as string)
    // Load the client too so ProjectOverviewCard shows its real name on a
    // direct visit/refresh (not just when arriving from the projects list).
    await clientsStore.fetchClient(project.clientId).catch(() => {})
    // The AI analysis lives on the originating Lead — load it so it's
    // embedded permanently on the Project page, not just reachable via a
    // link that navigates away.
    if (project.leadId) await leadsStore.fetchLead(project.leadId).catch(() => {})
    await fetchDocuments()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this project.'))
  } finally {
    loading.value = false
  }
})

// Proposal/contract/invoice generated for this project — stays visible
// permanently, like AttachmentsCard, instead of disappearing once you leave
// the AI panel or the proposal is converted/sent.
const projectDocuments = computed(() =>
  documentsStore.documents.filter((d) => d.projectId === (route.params.id as string)),
)

async function fetchDocuments() {
  await documentsStore.fetchDocuments({ project_id: route.params.id as string })
}

const project = computed(() => projectsStore.getProject(route.params.id as string))
const linkedLead = computed(() => (project.value?.leadId ? leadsStore.getLead(project.value.leadId) : undefined))

function statusLabel(status: string) {
  return projectStatuses.find((s) => s.value === status)?.label ?? status
}

async function addAttachments(files: UploadedFileMeta[]) {
  try {
    await projectsStore.addAttachments(project.value!.id, files)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the file.'))
  }
}
async function submitDesigns(files: UploadedFileMeta[]) {
  try {
    for (const file of files) await projectsStore.submitDesign(project.value!.id, file)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not submit the design.'))
  }
}
function activityLogNotSupported() {
  toast.info('Activity entries are recorded automatically and can’t be added manually.')
}
</script>

<template>
  <p v-if="loading" class="text-muted-foreground py-20 text-center text-sm">Loading…</p>
  <div v-else-if="!project" class="py-20 text-center">
    <p class="text-muted-foreground">Project not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/dashboard/projects')"
      >Back to Projects</Button
    >
  </div>
  <div v-else class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/dashboard/projects')">
          <ArrowLeft class="size-5" />
        </Button>
        <h1 class="text-2xl font-bold tracking-tight">{{ project.name }}</h1>
      </div>
      <Badge :class="projectStatusBadgeClass[project.status]" class="text-sm">{{
        statusLabel(project.status)
      }}</Badge>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <ProjectOverviewCard :project="project" />
        <AiAnalysisPanel v-if="linkedLead" :lead="linkedLead" @generated="fetchDocuments" />
        <DocumentsCard :documents="projectDocuments" title="Proposals & Documents" />
        <MilestonesSection :project="project" />
        <TasksSection :project="project" />
        <DesignReviewCard :designs="project.designs" role="team" @submit="submitDesigns" />
      </div>
      <div class="space-y-6">
        <AttachmentsCard :files="project.attachments" addable @add="addAttachments" />
        <ActivityTimelineCard :activity="project.activity" @log="activityLogNotSupported" />
      </div>
    </div>
  </div>
</template>
