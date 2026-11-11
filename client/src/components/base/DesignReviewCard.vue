<script setup lang="ts">
import { ref } from 'vue'
import { Palette, Upload, ExternalLink, Download, Check, X } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import EmptyState from '@/components/base/EmptyState.vue'
import type { DesignSubmission } from '@/stores/projects'
import {
  type UploadedFileMeta,
  uploadFiles,
  isImage,
  formatSize,
  openAttachment,
  downloadAttachment,
} from '@/utils/attachment'
import { useToast } from 'vue-toastification'
import { apiErrorMessage } from '@/services/apiError'

defineProps<{ designs: DesignSubmission[]; role: 'team' | 'client' }>()
const emit = defineEmits<{
  submit: [files: UploadedFileMeta[]]
  review: [designId: string, decision: 'approved' | 'rejected']
}>()

const toast = useToast()
const busy = ref(false)

async function onPicked(event: Event) {
  const input = event.target as HTMLInputElement
  const picked = Array.from(input.files ?? [])
  input.value = ''
  if (!picked.length) return
  busy.value = true
  try {
    emit('submit', await uploadFiles(picked))
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the file.'))
  } finally {
    busy.value = false
  }
}

const statusClass: Record<DesignSubmission['status'], string> = {
  pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-red-100 text-red-700',
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Designs</CardTitle>
      <CardDescription>
        {{
          role === 'client'
            ? 'Review and approve designs submitted by the team.'
            : 'Submit designs for the client to approve.'
        }}
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <label
        v-if="role === 'team'"
        class="border-input hover:border-primary/50 hover:bg-accent/50 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-3 text-sm transition-colors"
      >
        <Upload class="text-muted-foreground size-4" />
        <span>{{ busy ? 'Uploading…' : 'Submit a design' }}</span>
        <input type="file" multiple accept="image/*,.pdf" class="hidden" @change="onPicked" />
      </label>

      <EmptyState v-if="designs.length === 0" :icon="Palette" title="No designs yet" />
      <div v-else class="space-y-3">
        <div v-for="design in designs" :key="design.id" class="rounded-lg border p-3">
          <div class="flex items-center gap-3">
            <img
              v-if="isImage(design) && design.url"
              :src="design.url"
              :alt="design.name"
              class="size-12 shrink-0 rounded object-cover"
            />
            <div
              v-else
              class="bg-muted text-muted-foreground flex size-12 shrink-0 items-center justify-center rounded"
            >
              <Palette class="size-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ design.name }}</p>
              <p class="text-muted-foreground text-xs">
                {{ formatSize(design.size) }} · submitted {{ formatDate(design.submittedAt) }}
              </p>
            </div>
            <Badge :class="statusClass[design.status]" class="shrink-0 capitalize">{{
              design.status
            }}</Badge>
          </div>

          <div class="mt-3 flex flex-wrap items-center gap-2">
            <Button v-if="design.url" variant="outline" size="sm" @click="openAttachment(design)">
              <ExternalLink class="size-4" /> Open
            </Button>
            <Button
              v-if="design.url"
              variant="outline"
              size="sm"
              @click="downloadAttachment(design)"
            >
              <Download class="size-4" /> Download
            </Button>

            <template v-if="role === 'client' && design.status === 'pending'">
              <Button size="sm" class="ml-auto" @click="emit('review', design.id, 'approved')">
                <Check class="size-4" /> Approve
              </Button>
              <Button
                size="sm"
                variant="outline"
                class="text-red-600"
                @click="emit('review', design.id, 'rejected')"
              >
                <X class="size-4" /> Reject
              </Button>
            </template>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
