<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Paperclip, Upload, ExternalLink, Download, FileText } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import {
  type Attachment,
  type UploadedFileMeta,
  uploadFiles,
  isImage,
  formatSize,
  openAttachment,
  downloadAttachment,
} from '@/utils/attachment'
import { apiErrorMessage } from '@/services/apiError'

withDefaults(
  defineProps<{
    title?: string
    files: Attachment[]
    addable?: boolean
    uploadedBy?: 'team' | 'client'
  }>(),
  { title: 'Attachments', addable: false, uploadedBy: 'team' },
)
const emit = defineEmits<{ add: [files: UploadedFileMeta[]] }>()
const toast = useToast()

const busy = ref(false)

async function onPicked(event: Event) {
  const input = event.target as HTMLInputElement
  const picked = Array.from(input.files ?? [])
  input.value = ''
  if (!picked.length) return
  busy.value = true
  try {
    emit('add', await uploadFiles(picked))
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the file.'))
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ title }}</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <label
        v-if="addable"
        class="border-input hover:border-primary/50 hover:bg-accent/50 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-3 text-sm transition-colors"
      >
        <Upload class="text-muted-foreground size-4" />
        <span>{{ busy ? 'Uploading…' : 'Click to upload files' }}</span>
        <input type="file" multiple class="hidden" @change="onPicked" />
      </label>

      <EmptyState v-if="files.length === 0" :icon="Paperclip" title="No attachments" />
      <ul v-else class="space-y-2">
        <li
          v-for="(file, i) in files"
          :key="file.id ?? i"
          class="flex items-center gap-3 rounded-lg border p-2 text-sm"
        >
          <img
            v-if="isImage(file) && file.url"
            :src="file.url"
            :alt="file.name"
            class="size-10 shrink-0 rounded object-cover"
          />
          <div
            v-else
            class="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded"
          >
            <FileText class="size-5" />
          </div>

          <div class="min-w-0 flex-1">
            <p class="truncate font-medium">{{ file.name }}</p>
            <p class="text-muted-foreground text-xs">
              {{ formatSize(file.size)
              }}<span v-if="file.uploadedBy"> · uploaded by {{ file.uploadedBy }}</span>
            </p>
          </div>

          <div v-if="file.url" class="flex shrink-0 gap-1">
            <Button variant="ghost" size="icon-sm" title="Open" @click="openAttachment(file)">
              <ExternalLink class="size-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon-sm"
              title="Download"
              @click="downloadAttachment(file)"
            >
              <Download class="size-4" />
            </Button>
          </div>
          <span v-else class="text-muted-foreground shrink-0 text-xs italic">sample</span>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
