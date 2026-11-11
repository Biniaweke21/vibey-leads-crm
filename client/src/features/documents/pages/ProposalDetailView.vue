<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeft, Download, Copy, SquarePen, Send, X, FileSignature, Save } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore } from '@/stores/projects'
import DocumentStatusBadge from '@/features/documents/components/DocumentStatusBadge.vue'
import DocumentPreview from '@/features/documents/components/DocumentPreview.vue'
import PricingTable from '@/features/documents/components/PricingTable.vue'
import TimelineSection from '@/features/documents/components/TimelineSection.vue'
import ActivityTimelineCard from '@/components/base/ActivityTimelineCard.vue'
import { apiErrorMessage } from '@/services/apiError'
import { formatEtb } from '@/utils/currency'

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
    toast.error(apiErrorMessage(err, 'Could not load this proposal.'))
  } finally {
    loading.value = false
  }
})

const proposal = computed(() => documentsStore.getProposal(route.params.id as string))
const client = computed(() =>
  proposal.value ? clientsStore.getClient(proposal.value.clientId) : undefined,
)
const project = computed(() =>
  proposal.value ? projectsStore.getProject(proposal.value.projectId) : undefined,
)

async function downloadPdf() {
  try {
    await documentsStore.downloadPdf(proposal.value!.id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not download the PDF.'))
  }
}

async function duplicate() {
  try {
    const newId = await documentsStore.duplicateProposal(proposal.value!.id)
    if (newId) {
      toast.success('Proposal duplicated.')
      router.push(`/dashboard/documents/proposals/${newId}`)
    }
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not duplicate the proposal.'))
  }
}

const editing = ref(false)
const savingEdit = ref(false)
// The raw body's fields NOT covered by the edit form (tech_stack, timeline,
// quotation, payment_terms, assumptions) are kept exactly as fetched and
// re-sent untouched — PUT /documents replaces `body` wholesale, so silently
// dropping any key here would wipe it from the saved document.
let rawBody: Record<string, unknown> = {}
const editForm = reactive({
  title: '',
  executiveSummary: '',
  scopeOfWork: '',
  deliverablesText: '',
  callToAction: '',
  terms: '',
})

async function startEdit() {
  if (!proposal.value) return
  try {
    const raw = await documentsStore.fetchRawDocument(proposal.value.id)
    rawBody = raw.body ?? {}
    editForm.title = raw.title
    editForm.executiveSummary = proposal.value.executiveSummary
    editForm.scopeOfWork = proposal.value.scopeOfWork
    editForm.deliverablesText = proposal.value.deliverables.join('\n')
    editForm.callToAction = proposal.value.callToAction
    editForm.terms = proposal.value.terms
    editing.value = true
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load this proposal for editing.'))
  }
}

function cancelEdit() {
  editing.value = false
}

async function saveEdit() {
  savingEdit.value = true
  try {
    await documentsStore.updateDocument(proposal.value!.id, {
      title: editForm.title,
      body: {
        ...rawBody,
        executive_summary: editForm.executiveSummary,
        scope_of_work: editForm.scopeOfWork,
        deliverables: editForm.deliverablesText
          .split('\n')
          .map((l) => l.trim())
          .filter(Boolean),
        call_to_action: editForm.callToAction,
        terms: editForm.terms,
      },
    })
    toast.success('Proposal updated.')
    editing.value = false
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not save the proposal.'))
  } finally {
    savingEdit.value = false
  }
}

const includeStamp = ref(true)
const includeSignature = ref(true)

async function send() {
  try {
    await documentsStore.send(proposal.value!.id, {
      includeStamp: includeStamp.value,
      includeSignature: includeSignature.value,
    })
    toast.success('Proposal sent to client.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not send the proposal.'))
  }
}

const converting = ref(false)

async function convertToContract() {
  converting.value = true
  try {
    const contractId = await documentsStore.convertToContract(proposal.value!.id)
    toast.success('Contract created from this proposal.')
    router.push(`/dashboard/documents/contracts/${contractId}`)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not convert this proposal to a contract.'))
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
  <div v-else-if="!proposal" class="py-20 text-center">
    <p class="text-muted-foreground">Proposal not found.</p>
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
        <h1 class="text-2xl font-bold tracking-tight">{{ proposal.title }}</h1>
      </div>
      <DocumentStatusBadge category="proposal" :status="proposal.status" class="text-sm" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <Card v-if="editing">
          <CardHeader><CardTitle>Edit Proposal</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label>Title</Label>
              <Input v-model="editForm.title" />
            </div>
            <div class="space-y-1.5">
              <Label>Executive Summary</Label>
              <Textarea v-model="editForm.executiveSummary" rows="5" />
            </div>
            <div class="space-y-1.5">
              <Label>Scope of Work</Label>
              <Textarea v-model="editForm.scopeOfWork" rows="4" />
            </div>
            <div class="space-y-1.5">
              <Label>Deliverables <span class="text-muted-foreground text-xs">(one per line)</span></Label>
              <Textarea v-model="editForm.deliverablesText" rows="4" />
            </div>
            <div class="space-y-1.5">
              <Label>Call to Action</Label>
              <Textarea v-model="editForm.callToAction" rows="2" />
            </div>
            <div class="space-y-1.5">
              <Label>Terms</Label>
              <Textarea v-model="editForm.terms" rows="3" />
            </div>
            <div class="flex gap-2">
              <Button :disabled="savingEdit" @click="saveEdit">
                <Save class="size-4" /> {{ savingEdit ? 'Saving…' : 'Save Changes' }}
              </Button>
              <Button variant="outline" :disabled="savingEdit" @click="cancelEdit">
                <X class="size-4" /> Cancel
              </Button>
            </div>
          </CardContent>
        </Card>
        <DocumentPreview
          v-else
          title="Project Proposal"
          :subtitle="`v${proposal.version} · ${client?.name ?? 'Unknown Client'}`"
        >
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-muted-foreground text-xs">Client</p>
              <button
                class="text-primary font-medium hover:underline"
                @click="router.push(`/dashboard/clients/${proposal!.clientId}`)"
              >
                {{ client?.name ?? 'Unknown Client' }}
              </button>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Project</p>
              <button
                class="text-primary font-medium hover:underline"
                @click="router.push(`/dashboard/projects/${proposal!.projectId}`)"
              >
                {{ project?.name ?? 'Unknown Project' }}
              </button>
            </div>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Executive Summary</h3>
            <p class="text-sm">{{ proposal.executiveSummary }}</p>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Scope of Work</h3>
            <p class="text-sm">{{ proposal.scopeOfWork }}</p>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Deliverables</h3>
            <ul class="list-inside list-disc space-y-1 text-sm">
              <li v-for="item in proposal.deliverables" :key="item">{{ item }}</li>
            </ul>
          </div>

          <div v-if="Object.keys(proposal.techStack).length > 0">
            <h3 class="mb-2 font-semibold">Suggested Technology Stack</h3>
            <div class="space-y-2">
              <div
                v-for="(item, layer) in proposal.techStack"
                :key="layer"
                class="rounded-lg border p-3"
              >
                <p class="text-sm font-medium capitalize">{{ layer }}: {{ item.choice }}</p>
                <p class="text-muted-foreground mt-1 text-sm">{{ item.why }}</p>
              </div>
            </div>
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Timeline</h3>
            <TimelineSection :phases="proposal.timelinePhases" />
          </div>

          <div>
            <h3 class="mb-2 font-semibold">Pricing</h3>
            <PricingTable :items="proposal.pricing" />
          </div>

          <div v-if="proposal.quotationRangeHigh > 0">
            <h3 class="mb-2 font-semibold">Estimated Price Range</h3>
            <p class="text-sm font-medium">
              {{ formatEtb(proposal.quotationRangeLow) }} –
              {{ formatEtb(proposal.quotationRangeHigh) }}
            </p>
            <ul class="text-muted-foreground mt-1 list-inside list-disc space-y-1 text-sm">
              <li v-for="(r, i) in proposal.quotationReasoning" :key="i">{{ r }}</li>
            </ul>
          </div>

          <div v-if="proposal.paymentTerms.length > 0">
            <h3 class="mb-2 font-semibold">Payment Terms</h3>
            <ul class="list-inside list-disc space-y-1 text-sm">
              <li v-for="term in proposal.paymentTerms" :key="term.label">
                {{ term.percentage }}% — {{ term.label }}
              </li>
            </ul>
          </div>

          <div v-if="proposal.assumptions.length > 0">
            <h3 class="mb-2 font-semibold">Assumptions</h3>
            <ul class="list-inside list-disc space-y-1 text-sm">
              <li v-for="(item, i) in proposal.assumptions" :key="i">{{ item }}</li>
            </ul>
          </div>

          <div v-if="proposal.terms">
            <h3 class="mb-2 font-semibold">Terms</h3>
            <p class="text-muted-foreground text-sm">{{ proposal.terms }}</p>
          </div>

          <div v-if="proposal.callToAction">
            <p class="text-sm">{{ proposal.callToAction }}</p>
          </div>
        </DocumentPreview>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Actions</CardTitle>
          </CardHeader>
          <CardContent class="grid grid-cols-2 gap-2">
            <Button variant="outline" size="sm" @click="downloadPdf"
              ><Download class="size-4" /> Download PDF</Button
            >
            <Button variant="outline" size="sm" @click="duplicate"
              ><Copy class="size-4" /> Duplicate</Button
            >
            <Button variant="outline" size="sm" :disabled="editing" @click="startEdit"
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

            <p
              v-if="proposal.status === 'draft' || proposal.status === 'sent'"
              class="text-muted-foreground col-span-2 text-xs"
            >
              Waiting on the client to approve or reject this proposal from their portal.
            </p>

            <Button
              v-if="proposal.status === 'approved'"
              class="col-span-2"
              size="sm"
              :disabled="converting"
              @click="convertToContract"
            >
              <FileSignature class="size-4" />
              {{ converting ? 'Converting…' : 'Convert to Contract' }}
            </Button>
          </CardContent>
        </Card>
        <ActivityTimelineCard :activity="proposal.activity" @log="activityLogNotSupported" />
      </div>
    </div>
  </div>
</template>
