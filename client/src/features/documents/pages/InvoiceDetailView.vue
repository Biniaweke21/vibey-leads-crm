<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import {
  ArrowLeft,
  Download,
  Send,
  BellRing,
  CircleDollarSign,
  Receipt,
  Check,
  ExternalLink,
} from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import EmptyState from '@/components/base/EmptyState.vue'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore } from '@/stores/projects'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import DocumentPreview from '@/features/documents/components/DocumentPreview.vue'
import PricingTable from '@/features/documents/components/PricingTable.vue'
import ActivityTimelineCard from '@/components/base/ActivityTimelineCard.vue'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'
import { openAttachment, downloadAttachment } from '@/utils/attachment'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const documentsStore = useDocumentsStore()
const clientsStore = useClientsStore()
const projectsStore = useProjectsStore()
const loading = ref(true)

onMounted(async () => {
  try {
    const doc = await documentsStore.fetchDocument(route.params.id as string)
    await Promise.all([
      clientsStore.fetchClient(doc.clientId),
      doc.projectId ? projectsStore.fetchProject(doc.projectId) : Promise.resolve(),
    ])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this invoice.'))
  } finally {
    loading.value = false
  }
})

const invoice = computed(() => documentsStore.getInvoice(route.params.id as string))
const client = computed(() =>
  invoice.value ? clientsStore.getClient(invoice.value.clientId) : undefined,
)
const project = computed(() =>
  invoice.value ? projectsStore.getProject(invoice.value.projectId) : undefined,
)

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

async function download() {
  try {
    await documentsStore.downloadPdf(invoice.value!.id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not download the PDF.'))
  }
}

const includeStamp = ref(true)
const includeSignature = ref(true)

async function send() {
  try {
    await documentsStore.send(invoice.value!.id, {
      includeStamp: includeStamp.value,
      includeSignature: includeSignature.value,
    })
    toast.success('Invoice sent to client.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not send the invoice.'))
  }
}

async function sendReminder() {
  try {
    await documentsStore.sendReminder(invoice.value!.id)
    toast.success('Reminder sent.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not send the reminder.'))
  }
}

async function markPaid() {
  try {
    await documentsStore.markInvoicePaid(invoice.value!.id)
    toast.success('Invoice marked as paid.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the invoice.'))
  }
}

const approvingPaymentId = ref<string | null>(null)

async function approvePayment(paymentId: string) {
  approvingPaymentId.value = paymentId
  try {
    await documentsStore.approvePayment(invoice.value!.id, paymentId)
    toast.success('Payment approved — now counted toward revenue.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not approve this payment.'))
  } finally {
    approvingPaymentId.value = null
  }
}

const paymentStatusClass: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-700',
  paid: 'bg-emerald-100 text-emerald-700',
}

function activityLogNotSupported() {
  toast.info('Activity entries are recorded automatically and can’t be added manually.')
}
</script>

<template>
  <p v-if="loading" class="text-muted-foreground py-20 text-center text-sm">Loading…</p>
  <div v-else-if="!invoice" class="py-20 text-center">
    <p class="text-muted-foreground">Invoice not found.</p>
    <Button class="mt-4" variant="outline" @click="router.push('/dashboard/documents')"
      >Back to Documents</Button
    >
  </div>
  <div v-else class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="icon" @click="router.push('/dashboard/documents')">
          <ArrowLeft class="size-5" />
        </Button>
        <h1 class="text-2xl font-bold tracking-tight">{{ invoice.title }}</h1>
      </div>
      <DocumentStatusBadge category="invoice" :status="invoice.status" class="text-sm" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <DocumentPreview
          :title="invoice.invoiceNumber"
          :subtitle="client?.name ?? 'Unknown Client'"
        >
          <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
              <p class="text-muted-foreground text-xs">Client</p>
              <button
                class="text-primary font-medium hover:underline"
                @click="router.push(`/dashboard/clients/${invoice!.clientId}`)"
              >
                {{ client?.name ?? 'Unknown Client' }}
              </button>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Project</p>
              <button
                class="text-primary font-medium hover:underline"
                @click="router.push(`/dashboard/projects/${invoice!.projectId}`)"
              >
                {{ project?.name ?? 'Unknown Project' }}
              </button>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Due Date</p>
              <p class="font-medium">{{ formatDate(invoice.dueDate) }}</p>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Payment Status</p>
              <DocumentStatusBadge category="invoice" :status="invoice.status" />
            </div>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Line Items</h3>
            <PricingTable :items="invoice.lineItems" :tax-rate="invoice.taxRate" />
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Payment History</h3>
            <EmptyState
              v-if="invoice.paymentHistory.length === 0"
              :icon="Receipt"
              title="No payments recorded"
            />
            <ul v-else class="space-y-2">
              <li v-for="payment in invoice.paymentHistory" :key="payment.id" class="rounded-lg border p-3 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <span
                    >{{ formatDate(payment.date) }}
                    <span v-if="payment.stageLabel">· {{ payment.stageLabel }}</span></span
                  >
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ formatEtb(payment.amount) }}</span>
                    <Badge :class="paymentStatusClass[payment.status]" class="capitalize">{{
                      payment.statusLabel
                    }}</Badge>
                  </div>
                </div>
                <div v-if="payment.attachments.length > 0" class="mt-2 flex flex-wrap gap-2">
                  <div
                    v-for="att in payment.attachments"
                    :key="att.id"
                    class="bg-muted flex items-center gap-1 rounded px-2 py-1 text-xs"
                  >
                    <span class="max-w-32 truncate">{{ att.name }}</span>
                    <Button variant="ghost" size="icon-sm" title="Preview" @click="openAttachment(att)">
                      <ExternalLink class="size-3.5" />
                    </Button>
                    <Button variant="ghost" size="icon-sm" title="Download" @click="downloadAttachment(att)">
                      <Download class="size-3.5" />
                    </Button>
                  </div>
                </div>
                <Button
                  v-if="payment.status === 'pending'"
                  size="sm"
                  class="mt-2"
                  :disabled="approvingPaymentId === payment.id"
                  @click="approvePayment(payment.id)"
                >
                  <Check class="size-4" />
                  {{ approvingPaymentId === payment.id ? 'Approving…' : 'Approve Payment' }}
                </Button>
              </li>
            </ul>
          </div>
        </DocumentPreview>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Actions</CardTitle>
          </CardHeader>
          <CardContent class="grid grid-cols-1 gap-2">
            <Button variant="outline" size="sm" @click="download"
              ><Download class="size-4" /> Download</Button
            >
            <div class="space-y-1 border-t pt-2">
              <label class="flex items-center gap-2 text-xs">
                <Checkbox v-model="includeStamp" />
                Include company stamp
              </label>
              <label class="flex items-center gap-2 text-xs">
                <Checkbox v-model="includeSignature" />
                Include my signature
              </label>
            </div>
            <Button size="sm" @click="send"><Send class="size-4" /> Send</Button>
            <Button variant="outline" size="sm" @click="sendReminder"
              ><BellRing class="size-4" /> Send Reminder</Button
            >
            <Button size="sm" :disabled="invoice.status === 'paid'" @click="markPaid">
              <CircleDollarSign class="size-4" /> Mark Paid
            </Button>
          </CardContent>
        </Card>
        <ActivityTimelineCard :activity="invoice.activity" @log="activityLogNotSupported" />
      </div>
    </div>
  </div>
</template>
