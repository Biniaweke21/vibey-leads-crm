<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { FileStack, Download, Eye, Check, X, Receipt, Upload } from '@lucide/vue'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import EmptyState from '@/components/base/EmptyState.vue'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import SubmitPaymentDialog from '@/features/client-portal/components/SubmitPaymentDialog.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

const toast = useToast()
const portalStore = useClientPortalStore()
const loading = ref(true)

onMounted(async () => {
  try {
    await portalStore.fetchDocuments()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load your documents.'))
  } finally {
    loading.value = false
  }
})

const proposals = computed(() => portalStore.documents.filter((d) => d.type === 'proposal'))
const contracts = computed(() => portalStore.documents.filter((d) => d.type === 'contract'))
const invoices = computed(() => portalStore.documents.filter((d) => d.type === 'invoice'))

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

async function download(id: number | string) {
  try {
    await portalStore.downloadDocumentPdf(id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not download the PDF.'))
  }
}

async function preview(id: number | string) {
  try {
    await portalStore.previewDocumentPdf(id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not preview the document.'))
  }
}

const paymentStatusClass: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-700',
  paid: 'bg-emerald-100 text-emerald-700',
}

const payDialogOpen = ref(false)
const payDialogDocumentId = ref<number | string | null>(null)

function openPayDialog(id: number | string) {
  payDialogDocumentId.value = id
  payDialogOpen.value = true
}

async function refreshDocuments() {
  await portalStore.fetchDocuments()
}

const reviewingId = ref<number | string | null>(null)

async function approve(id: number | string) {
  reviewingId.value = id
  try {
    await portalStore.approveDocument(id)
    toast.success('Proposal approved.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not approve the proposal.'))
  } finally {
    reviewingId.value = null
  }
}

async function reject(id: number | string) {
  reviewingId.value = id
  try {
    await portalStore.rejectDocument(id)
    toast.success('Proposal rejected.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not reject the proposal.'))
  } finally {
    reviewingId.value = null
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">Documents</h1>
      <p class="text-muted-foreground mt-1">
        Your proposals, contracts, and invoices from Vibey World.
      </p>
    </div>

    <Tabs default-value="proposals">
      <TabsList>
        <TabsTrigger value="proposals">Proposals</TabsTrigger>
        <TabsTrigger value="contracts">Contracts</TabsTrigger>
        <TabsTrigger value="invoices">Invoices</TabsTrigger>
      </TabsList>

      <TabsContent value="proposals" class="space-y-2">
        <EmptyState
          v-if="!loading && proposals.length === 0"
          :icon="FileStack"
          title="No proposals yet"
        />
        <Card v-for="doc in proposals" :key="doc.id">
          <CardContent class="flex flex-wrap items-center gap-3">
            <div class="min-w-40 flex-1">
              <p class="font-medium">{{ doc.title }}</p>
              <p class="text-muted-foreground text-xs">{{ formatDate(doc.updated_at) }}</p>
            </div>
            <DocumentStatusBadge category="proposal" :status="doc.status" />
            <p class="w-28 text-right text-sm font-medium">{{ formatEtb(doc.total) }}</p>
            <Button variant="outline" size="sm" @click="preview(doc.id)"
              ><Eye class="size-4" /> Preview</Button
            >
            <Button variant="outline" size="sm" @click="download(doc.id)"
              ><Download class="size-4" /> Download</Button
            >
            <template v-if="doc.status === 'draft' || doc.status === 'sent'">
              <Button
                size="sm"
                :disabled="reviewingId === doc.id"
                @click="approve(doc.id)"
              >
                <Check class="size-4" /> Approve
              </Button>
              <Button
                size="sm"
                variant="outline"
                class="text-red-600"
                :disabled="reviewingId === doc.id"
                @click="reject(doc.id)"
              >
                <X class="size-4" /> Reject
              </Button>
            </template>
          </CardContent>
        </Card>
      </TabsContent>

      <TabsContent value="contracts" class="space-y-2">
        <EmptyState
          v-if="!loading && contracts.length === 0"
          :icon="FileStack"
          title="No contracts yet"
        />
        <Card v-for="doc in contracts" :key="doc.id">
          <CardContent class="flex flex-wrap items-center gap-3">
            <div class="min-w-40 flex-1">
              <p class="font-medium">{{ doc.title }}</p>
              <p class="text-muted-foreground text-xs">{{ formatDate(doc.updated_at) }}</p>
            </div>
            <DocumentStatusBadge category="contract" :status="doc.status" />
            <p class="w-28 text-right text-sm font-medium">{{ formatEtb(doc.total) }}</p>
            <Button variant="outline" size="sm" @click="preview(doc.id)"
              ><Eye class="size-4" /> Preview</Button
            >
            <Button variant="outline" size="sm" @click="download(doc.id)"
              ><Download class="size-4" /> Download</Button
            >
          </CardContent>
        </Card>
      </TabsContent>

      <TabsContent value="invoices" class="space-y-2">
        <EmptyState
          v-if="!loading && invoices.length === 0"
          :icon="FileStack"
          title="No invoices yet"
        />
        <Card v-for="doc in invoices" :key="doc.id">
          <CardContent class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
              <div class="min-w-40 flex-1">
                <p class="font-medium">{{ doc.number }} — {{ doc.title }}</p>
                <p class="text-muted-foreground text-xs">Due {{ formatDate(doc.due_date) }}</p>
              </div>
              <DocumentStatusBadge category="invoice" :status="doc.status" />
              <p class="w-28 text-right text-sm font-medium">{{ formatEtb(doc.total) }}</p>
              <Button variant="outline" size="sm" @click="preview(doc.id)"
                ><Eye class="size-4" /> Preview</Button
              >
              <Button variant="outline" size="sm" @click="download(doc.id)"
                ><Download class="size-4" /> Download</Button
              >
              <Button size="sm" @click="openPayDialog(doc.id)">
                <Upload class="size-4" /> Submit Payment
              </Button>
            </div>

            <div v-if="doc.payments.length > 0" class="space-y-1.5 border-t pt-3">
              <div
                v-for="payment in doc.payments"
                :key="payment.id"
                class="flex flex-wrap items-center gap-2 text-xs"
              >
                <Receipt class="text-muted-foreground size-3.5" />
                <span class="font-medium">{{ formatEtb(Number(payment.amount)) }}</span>
                <span v-if="payment.stage_label" class="text-muted-foreground"
                  >· {{ payment.stage_label }}</span
                >
                <Badge :class="paymentStatusClass[payment.status]" class="capitalize">{{
                  payment.status_label
                }}</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>

    <SubmitPaymentDialog
      v-if="payDialogDocumentId"
      v-model:open="payDialogOpen"
      :document-id="payDialogDocumentId"
      @submitted="refreshDocuments"
    />
  </div>
</template>
