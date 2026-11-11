<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { FileText, Download, ExternalLink } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { documentAmount, type CrmDocument } from '@/features/documents/types'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

withDefaults(defineProps<{ title?: string; documents: CrmDocument[] }>(), { title: 'Documents' })

const router = useRouter()
const documentsStore = useDocumentsStore()
const toast = useToast()

function openDocument(doc: CrmDocument) {
  router.push(`/dashboard/documents/${doc.category}s/${doc.id}`)
}

async function downloadPdf(doc: CrmDocument) {
  try {
    await documentsStore.downloadPdf(doc.id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not download the PDF.'))
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ title }}</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <EmptyState v-if="documents.length === 0" :icon="FileText" title="No documents yet" />
      <ul v-else class="space-y-2">
        <li
          v-for="doc in documents"
          :key="doc.id"
          class="flex items-center gap-3 rounded-lg border p-2 text-sm"
        >
          <div
            class="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded"
          >
            <FileText class="size-5" />
          </div>

          <div class="min-w-0 flex-1">
            <p class="truncate font-medium">{{ doc.title }}</p>
            <p class="text-muted-foreground text-xs capitalize">
              {{ doc.category }} · {{ formatEtb(documentAmount(doc)) }}
            </p>
          </div>

          <DocumentStatusBadge :category="doc.category" :status="doc.status" class="shrink-0" />

          <div class="flex shrink-0 gap-1">
            <Button variant="ghost" size="icon-sm" title="Open" @click="openDocument(doc)">
              <ExternalLink class="size-4" />
            </Button>
            <Button variant="ghost" size="icon-sm" title="Download PDF" @click="downloadPdf(doc)">
              <Download class="size-4" />
            </Button>
          </div>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
