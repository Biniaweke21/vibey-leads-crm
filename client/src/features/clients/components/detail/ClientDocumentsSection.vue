<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { FileStack, Send } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { documentAmount } from '@/features/documents/types'
import type { Client } from '@/stores/clients'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ client: Client }>()
const router = useRouter()
const toast = useToast()
const documentsStore = useDocumentsStore()

// Proposals, contracts, and invoices — separate from the "Files" attachments
// card above; these are the formal Documents generated for this client
// (e.g. by the AI proposal generator on a converted lead).
const documents = computed(() =>
  [...documentsStore.documents]
    .filter((d) => d.clientId === props.client.id)
    .sort((a, b) => b.updatedAt.localeCompare(a.updatedAt)),
)

function open(id: string, category: string) {
  router.push(`/dashboard/documents/${category}s/${id}`)
}

async function send(id: string) {
  try {
    await documentsStore.send(id)
    toast.success('Sent to client.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not send this document.'))
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Proposals &amp; Invoices</CardTitle>
    </CardHeader>
    <CardContent class="space-y-2">
      <EmptyState v-if="documents.length === 0" :icon="FileStack" title="No documents yet" />
      <div
        v-for="doc in documents"
        :key="doc.id"
        class="flex flex-wrap items-center gap-3 rounded-lg border p-3"
      >
        <button
          class="min-w-40 flex-1 text-left"
          @click="open(doc.id, doc.category)"
        >
          <p class="text-primary text-sm font-medium hover:underline">{{ doc.title }}</p>
          <p class="text-muted-foreground text-xs capitalize">{{ doc.category }}</p>
        </button>
        <DocumentStatusBadge :category="doc.category" :status="doc.status" />
        <p class="w-24 text-right text-sm font-medium">{{ formatEtb(documentAmount(doc)) }}</p>
        <Button
          v-if="doc.status === 'draft'"
          variant="outline"
          size="sm"
          @click="send(doc.id)"
        >
          <Send class="size-4" /> Send
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
