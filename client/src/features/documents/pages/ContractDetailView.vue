<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft, Download, SquarePen, Send, Archive, Receipt } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore } from '@/stores/projects'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import DocumentPreview from '@/features/documents/components/DocumentPreview.vue'
import SignatureStatus from '@/features/documents/components/SignatureStatus.vue'
import ActivityTimelineCard from '@/components/base/ActivityTimelineCard.vue'
import { formatEtb } from '@/utils/currency'
import { apiErrorMessage } from '@/services/apiError'

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
    toast.error(apiErrorMessage(err, 'Could not load this contract.'))
  } finally {
    loading.value = false
  }
})

const contract = computed(() => documentsStore.getContract(route.params.id as string))
const client = computed(() =>
  contract.value ? clientsStore.getClient(contract.value.clientId) : undefined,
)
const project = computed(() =>
  contract.value ? projectsStore.getProject(contract.value.projectId) : undefined,
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
    await documentsStore.downloadPdf(contract.value!.id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not download the PDF.'))
  }
}

function edit() {
  toast.info('Editing is coming soon.')
}

const includeStamp = ref(true)
const includeSignature = ref(true)

async function send() {
  try {
    await documentsStore.send(contract.value!.id, {
      includeStamp: includeStamp.value,
      includeSignature: includeSignature.value,
    })
    toast.success('Contract sent to client.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not send the contract.'))
  }
}

// Archiving isn't backed by the API yet — there's no archived document status.
function archive() {
  toast.info('Archiving isn’t available yet.')
}

const converting = ref(false)

async function convertToInvoice() {
  converting.value = true
  try {
    const invoiceId = await documentsStore.convertToInvoice(contract.value!.id)
    toast.success('Invoice created from this contract.')
    router.push(`/dashboard/documents/invoices/${invoiceId}`)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not convert this contract to an invoice.'))
  } finally {
    converting.value = false
  }
}

function activityLogNotSupported() {
  toast.info('Activity entries are recorded automatically and can’t be added manually.')
}
</script>

<template>
  <p v-if="loading" class="text-muted-foreground py-20 text-center text-sm">Loading…</p>
  <div v-else-if="!contract" class="py-20 text-center">
    <p class="text-muted-foreground">Contract not found.</p>
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
        <h1 class="text-2xl font-bold tracking-tight">{{ contract.title }}</h1>
      </div>
      <DocumentStatusBadge category="contract" :status="contract.status" class="text-sm" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <DocumentPreview
          title="Service Agreement"
          :subtitle="`v${contract.version} · ${client?.name ?? 'Unknown Client'}`"
        >
          <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
              <p class="text-muted-foreground text-xs">Client</p>
              <button
                class="text-primary font-medium hover:underline"
                @click="router.push(`/dashboard/clients/${contract!.clientId}`)"
              >
                {{ client?.name ?? 'Unknown Client' }}
              </button>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Project</p>
              <button
                class="text-primary font-medium hover:underline"
                @click="router.push(`/dashboard/projects/${contract!.projectId}`)"
              >
                {{ project?.name ?? 'Unknown Project' }}
              </button>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Start Date</p>
              <p class="font-medium">{{ formatDate(contract.startDate) }}</p>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">End Date</p>
              <p class="font-medium">{{ formatDate(contract.endDate) }}</p>
            </div>
          </div>

          <div>
            <p class="text-muted-foreground text-xs">Contract Value</p>
            <p class="text-lg font-semibold">{{ formatEtb(contract.amount) }}</p>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Contract Terms</h3>
            <p class="text-sm">{{ contract.contractTerms }}</p>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Deliverables</h3>
            <ul class="list-inside list-disc space-y-1 text-sm">
              <li v-for="item in contract.deliverables" :key="item">{{ item }}</li>
            </ul>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Signature Status</h3>
            <SignatureStatus :status="contract.signatureStatus" />
          </div>
        </DocumentPreview>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Actions</CardTitle>
          </CardHeader>
          <CardContent class="grid grid-cols-2 gap-2">
            <Button variant="outline" size="sm" @click="download"
              ><Download class="size-4" /> Download</Button
            >
            <Button variant="outline" size="sm" @click="edit"
              ><SquarePen class="size-4" /> Edit</Button
            >
            <div class="col-span-2 space-y-1 border-t pt-2">
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
            <Button variant="outline" size="sm" @click="archive"
              ><Archive class="size-4" /> Archive</Button
            >

            <Button
              v-if="contract.signatureStatus === 'fully-signed'"
              class="col-span-2"
              size="sm"
              :disabled="converting"
              @click="convertToInvoice"
            >
              <Receipt class="size-4" />
              {{ converting ? 'Converting…' : 'Convert to Invoice' }}
            </Button>
          </CardContent>
        </Card>
        <ActivityTimelineCard :activity="contract.activity" @log="activityLogNotSupported" />
      </div>
    </div>
  </div>
</template>
