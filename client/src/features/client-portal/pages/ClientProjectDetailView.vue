<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft, CheckCircle2, Circle, Palette, Check, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import EmptyState from '@/components/base/EmptyState.vue'
import AttachmentsCard from '@/components/base/AttachmentsCard.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'
import type { Attachment, UploadedFileMeta } from '@/utils/attachment'
import type { PortalProject, PortalAttachment } from '@/features/client-portal/types'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const portalStore = useClientPortalStore()

const project = ref<PortalProject | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    project.value = await portalStore.fetchProject(route.params.id as string)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this project.'))
  } finally {
    loading.value = false
  }
})

function mapAttachment(a: PortalAttachment): Attachment {
  return {
    id: String(a.id),
    name: a.name,
    size: a.size ?? 0,
    type: a.mime_type ?? undefined,
    url: a.url ?? undefined,
    uploadedBy: a.uploaded_by_role,
  }
}

const reviewingDesignId = ref<number | null>(null)

async function reviewDesign(designId: number, decision: 'approved' | 'rejected') {
  if (!project.value) return
  reviewingDesignId.value = designId
  try {
    const updated = await portalStore.reviewDesign(project.value.id, designId, decision)
    const design = project.value.design_submissions.find((d) => d.id === designId)
    if (design) {
      design.status = updated.status
      design.status_label = updated.status_label
      design.decided_at = updated.decided_at
    }
    toast.success(decision === 'approved' ? 'Design approved.' : 'Design rejected.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not submit your decision.'))
  } finally {
    reviewingDesignId.value = null
  }
}

async function addAttachments(files: UploadedFileMeta[]) {
  if (!project.value) return
  try {
    for (const file of files) {
      await portalStore.addProjectAttachment(project.value.id, file)
    }
    project.value = await portalStore.fetchProject(project.value.id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the file.'))
  }
}

function initials(name: string) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const designStatusClass: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-red-100 text-red-700',
}
</script>

<template>
  <div v-if="!loading && !project" class="py-20 text-center">
    <p class="text-muted-foreground">Project not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/client/projects')"
      >Back to Projects</Button
    >
  </div>
  <div v-else-if="project" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/client/projects')">
          <ArrowLeft class="size-5" />
        </Button>
        <h1 class="text-2xl font-bold tracking-tight">{{ project.name }}</h1>
      </div>
      <Badge variant="secondary" class="text-sm">{{ project.status_label }}</Badge>
    </div>

    <Card>
      <CardHeader><CardTitle>Overview</CardTitle></CardHeader>
      <CardContent class="space-y-5">
        <p class="text-sm">{{ project.description }}</p>
        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
          <div>
            <p class="text-muted-foreground text-xs">Budget</p>
            <p class="font-medium">{{ formatEtb(Number(project.budget)) }}</p>
          </div>
          <div>
            <p class="text-muted-foreground text-xs">Timeline</p>
            <p class="font-medium">
              {{ formatDate(project.start_date) }} – {{ formatDate(project.end_date) }}
            </p>
          </div>
          <div class="col-span-2">
            <p class="text-muted-foreground mb-1 text-xs">Team</p>
            <div class="flex flex-wrap gap-2">
              <div
                v-for="member in project.members"
                :key="member.id"
                class="bg-muted flex items-center gap-2 rounded-full py-1 pr-3 pl-1"
              >
                <Avatar class="size-6"
                  ><AvatarFallback class="text-[10px]">{{
                    initials(member.name)
                  }}</AvatarFallback></Avatar
                >
                <span class="text-xs font-medium">{{ member.name }}</span>
              </div>
            </div>
          </div>
        </div>
        <div>
          <p class="text-muted-foreground mb-1 text-xs">Progress</p>
          <div class="bg-muted h-2 w-full overflow-hidden rounded-full">
            <div
              class="bg-primary h-full rounded-full"
              :style="{ width: `${project.progress}%` }"
            />
          </div>
          <p class="text-muted-foreground mt-1 text-xs">{{ project.progress }}% complete</p>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>Milestones</CardTitle></CardHeader>
      <CardContent class="space-y-2">
        <EmptyState v-if="project.milestones.length === 0" title="No milestones yet" />
        <div
          v-for="milestone in project.milestones"
          :key="milestone.id"
          class="flex items-center gap-3 rounded-lg border p-3"
        >
          <CheckCircle2 v-if="milestone.is_done" class="size-4 shrink-0 text-emerald-500" />
          <Circle v-else class="text-muted-foreground size-4 shrink-0" />
          <div class="flex-1">
            <p
              class="text-sm"
              :class="milestone.is_done ? 'text-muted-foreground line-through' : ''"
            >
              {{ milestone.title }}
            </p>
            <p class="text-muted-foreground text-xs">{{ formatDate(milestone.due_date) }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>Tasks</CardTitle></CardHeader>
      <CardContent class="space-y-2">
        <EmptyState v-if="project.tasks.length === 0" title="No tasks yet" />
        <div
          v-for="task in project.tasks"
          :key="task.id"
          class="flex items-center justify-between rounded-lg border p-3 text-sm"
        >
          <span :class="task.status === 'done' ? 'text-muted-foreground line-through' : ''">{{
            task.title
          }}</span>
          <Badge variant="secondary">{{ task.status_label }}</Badge>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>Designs</CardTitle></CardHeader>
      <CardContent class="space-y-3">
        <EmptyState
          v-if="project.design_submissions.length === 0"
          :icon="Palette"
          title="No designs yet"
        />
        <div v-for="design in project.design_submissions" :key="design.id" class="rounded-lg border p-3">
          <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ design.name }}</p>
              <p class="text-muted-foreground text-xs">
                v{{ design.version }} · submitted {{ formatDate(design.submitted_at) }}
              </p>
            </div>
            <Badge :class="designStatusClass[design.status]" class="shrink-0 capitalize">{{
              design.status_label
            }}</Badge>
          </div>

          <div v-if="design.status === 'pending'" class="mt-3 flex items-center gap-2">
            <Button
              size="sm"
              :disabled="reviewingDesignId === design.id"
              @click="reviewDesign(design.id, 'approved')"
            >
              <Check class="size-4" /> Approve
            </Button>
            <Button
              size="sm"
              variant="outline"
              class="text-red-600"
              :disabled="reviewingDesignId === design.id"
              @click="reviewDesign(design.id, 'rejected')"
            >
              <X class="size-4" /> Reject
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <AttachmentsCard
      :files="project.attachments.map(mapAttachment)"
      addable
      @add="addAttachments"
    />
  </div>
</template>
