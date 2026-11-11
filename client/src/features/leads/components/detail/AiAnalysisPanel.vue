<script setup lang="ts">
import { computed, reactive, ref, toRaw, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { Sparkles, Save, RotateCcw, FileText, Pencil, X } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import EmptyState from '@/components/base/EmptyState.vue'
import { useLeadsStore, type Lead, type AiAnalysis } from '@/stores/leads'
import { complexityBadgeClass } from '@/features/leads/data/statusMeta'
import { apiErrorMessage } from '@/services/apiError'
import { formatEtb } from '@/utils/currency'

const props = defineProps<{ lead: Lead }>()
const emit = defineEmits<{ generated: [] }>()
const leadsStore = useLeadsStore()
const toast = useToast()

const analyzing = ref(false)
const saving = ref(false)
const generatingProposal = ref(false)
// The AI already fills everything in — default to a plain read-only report,
// not an edit form. "Edit" is an explicit opt-in for the rare manual tweak.
const editing = ref(false)

const complexityLevels = ['Low', 'Medium', 'High', 'Enterprise'] as const

function emptyAnalysis(): AiAnalysis {
  return {
    project_summary: '',
    tech_stack: {
      frontend: { choice: '', why: '' },
      backend: { choice: '', why: '' },
      database: { choice: '', why: '' },
      hosting: { choice: '', why: '' },
      authentication: { choice: '', why: '' },
      integrations: { choice: '', why: '' },
    },
    complexity: { level: 'Low', explanation: '' },
    timeline: { total_duration: '', phases: [] },
    missing_requirements: [],
    questions: [],
    risks: [],
    quotation: { currency: 'ETB', range_low: 0, range_high: 0, reasoning: [] },
  }
}

const form = reactive<AiAnalysis>(props.lead.aiAnalysis ? structuredClone(toRaw(props.lead.aiAnalysis)) : emptyAnalysis())

watch(
  () => props.lead.aiAnalysis,
  (analysis) => {
    Object.assign(form, analysis ? structuredClone(toRaw(analysis)) : emptyAnalysis())
  },
)

const missingText = computed({
  get: () => form.missing_requirements.join('\n'),
  set: (v: string) => {
    form.missing_requirements = v.split('\n').map((l) => l.trim()).filter(Boolean)
  },
})
const questionsText = computed({
  get: () => form.questions.join('\n'),
  set: (v: string) => {
    form.questions = v.split('\n').map((l) => l.trim()).filter(Boolean)
  },
})
const reasoningText = computed({
  get: () => form.quotation.reasoning.join('\n'),
  set: (v: string) => {
    form.quotation.reasoning = v.split('\n').map((l) => l.trim()).filter(Boolean)
  },
})

const techLayers: { key: keyof AiAnalysis['tech_stack']; label: string }[] = [
  { key: 'frontend', label: 'Frontend' },
  { key: 'backend', label: 'Backend' },
  { key: 'database', label: 'Database' },
  { key: 'hosting', label: 'Hosting' },
  { key: 'authentication', label: 'Authentication' },
  { key: 'integrations', label: 'Third-party Integrations' },
]

async function analyze() {
  analyzing.value = true
  try {
    await leadsStore.runAiAnalysis(props.lead.id)
    editing.value = false
    toast.success('AI requirement analysis generated.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not generate the analysis.'))
  } finally {
    analyzing.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await leadsStore.updateAiAnalysis(props.lead.id, structuredClone(toRaw(form)))
    editing.value = false
    toast.success('Analysis saved.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not save the analysis.'))
  } finally {
    saving.value = false
  }
}

/** Discards unsaved edits and returns to the read-only report. */
function cancelEdit() {
  Object.assign(form, props.lead.aiAnalysis ? structuredClone(toRaw(props.lead.aiAnalysis)) : emptyAnalysis())
  editing.value = false
}

/**
 * Re-syncs the edit form from the current analysis at the exact moment
 * editing starts, rather than trusting the background watcher to have
 * already caught up — guarantees the form is never stale or empty when
 * "Edit" is clicked.
 */
function startEdit() {
  Object.assign(form, props.lead.aiAnalysis ? structuredClone(toRaw(props.lead.aiAnalysis)) : emptyAnalysis())
  editing.value = true
}

async function generateProposal() {
  generatingProposal.value = true
  try {
    await leadsStore.generateProposal(props.lead.id)
    toast.success('Proposal generated and saved to Documents.')
    emit('generated')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not generate the proposal.'))
  } finally {
    generatingProposal.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2"><Sparkles class="size-4" /> AI Requirement Analysis</CardTitle>
      <CardDescription>Generated from this lead's submitted data only.</CardDescription>
    </CardHeader>
    <CardContent class="space-y-6">
      <EmptyState
        v-if="!lead.aiAnalysis"
        :icon="Sparkles"
        title="No analysis yet"
        description="Generate a structured requirement analysis from this lead's data."
      >
        <template #action>
          <Button :disabled="analyzing" @click="analyze">
            {{ analyzing ? 'Analyzing…' : 'Analyze Requirements' }}
          </Button>
        </template>
      </EmptyState>

      <template v-else-if="!editing">
        <!-- Read-only report — the AI already filled everything in, so this
             is just the finished result, not a form to fill out. -->
        <div class="space-y-1.5">
          <Label>Project Summary</Label>
          <p class="text-sm whitespace-pre-line">{{ lead.aiAnalysis.project_summary }}</p>
        </div>

        <div class="space-y-2">
          <Label>Suggested Tech Stack</Label>
          <div v-for="layer in techLayers" :key="layer.key" class="rounded-lg border p-3">
            <p class="text-sm font-medium">
              {{ layer.label }}: {{ lead.aiAnalysis.tech_stack[layer.key].choice }}
            </p>
            <p class="text-muted-foreground mt-1 text-sm">
              {{ lead.aiAnalysis.tech_stack[layer.key].why }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="space-y-1.5">
            <Label>Estimated Complexity</Label>
            <Badge :class="complexityBadgeClass[lead.aiAnalysis.complexity.level]">{{
              lead.aiAnalysis.complexity.level
            }}</Badge>
          </div>
          <div class="space-y-1.5">
            <Label class="text-muted-foreground text-xs">Explanation</Label>
            <p class="text-sm">{{ lead.aiAnalysis.complexity.explanation }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <Label>Estimated Timeline — {{ lead.aiAnalysis.timeline.total_duration }}</Label>
          <ul class="space-y-1">
            <li
              v-for="(phase, i) in lead.aiAnalysis.timeline.phases"
              :key="i"
              class="flex items-center justify-between border-b pb-1 text-sm last:border-b-0"
            >
              <span>{{ phase.phase }}</span>
              <span class="text-muted-foreground">{{ phase.duration }}</span>
            </li>
          </ul>
        </div>

        <div v-if="lead.aiAnalysis.missing_requirements.length" class="space-y-1.5">
          <Label>Missing Requirements</Label>
          <ul class="list-inside list-disc space-y-1 text-sm">
            <li v-for="item in lead.aiAnalysis.missing_requirements" :key="item">{{ item }}</li>
          </ul>
        </div>

        <div v-if="lead.aiAnalysis.questions.length" class="space-y-1.5">
          <Label>Questions to Ask the Client</Label>
          <ul class="list-inside list-disc space-y-1 text-sm">
            <li v-for="q in lead.aiAnalysis.questions" :key="q">{{ q }}</li>
          </ul>
        </div>

        <div v-if="lead.aiAnalysis.risks.length" class="space-y-2">
          <Label>Potential Risks / Blockers</Label>
          <div v-for="(risk, i) in lead.aiAnalysis.risks" :key="i" class="rounded-lg border p-3">
            <p class="text-sm font-medium">{{ risk.title }}</p>
            <p class="text-muted-foreground mt-1 text-sm">{{ risk.explanation }}</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <Label>Initial Quotation Range</Label>
          <p class="text-sm font-medium">
            {{ formatEtb(lead.aiAnalysis.quotation.range_low) }} –
            {{ formatEtb(lead.aiAnalysis.quotation.range_high) }}
          </p>
          <ul class="text-muted-foreground list-inside list-disc space-y-1 text-sm">
            <li v-for="(r, i) in lead.aiAnalysis.quotation.reasoning" :key="i">{{ r }}</li>
          </ul>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button variant="outline" @click="startEdit"> <Pencil class="size-4" /> Edit </Button>
          <Button variant="outline" :disabled="analyzing" @click="analyze">
            <RotateCcw class="size-4" /> {{ analyzing ? 'Regenerating…' : 'Regenerate' }}
          </Button>
        </div>
      </template>

      <template v-else>
        <!-- Edit mode — only reached explicitly, for the rare manual tweak. -->
        <div class="space-y-1.5">
          <Label>Project Summary</Label>
          <Textarea v-model="form.project_summary" rows="6" />
        </div>

        <!-- Tech Stack -->
        <div class="space-y-2">
          <Label>Suggested Tech Stack</Label>
          <div v-for="layer in techLayers" :key="layer.key" class="grid grid-cols-1 gap-2 rounded-lg border p-3 sm:grid-cols-3">
            <div class="text-sm font-medium">{{ layer.label }}</div>
            <Input v-model="form.tech_stack[layer.key].choice" placeholder="Choice" />
            <Input v-model="form.tech_stack[layer.key].why" placeholder="Why" />
          </div>
        </div>

        <!-- Complexity -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="space-y-1.5">
            <Label>Estimated Complexity</Label>
            <Select v-model="form.complexity.level">
              <SelectTrigger class="w-full">
                <SelectValue />
                <Badge :class="complexityBadgeClass[form.complexity.level]" class="ml-2">{{ form.complexity.level }}</Badge>
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="level in complexityLevels" :key="level" :value="level">{{ level }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <Label>Explanation</Label>
            <Input v-model="form.complexity.explanation" />
          </div>
        </div>

        <!-- Timeline -->
        <div class="space-y-2">
          <Label>Estimated Timeline</Label>
          <Input v-model="form.timeline.total_duration" placeholder="Total duration" />
          <div v-for="(phase, i) in form.timeline.phases" :key="i" class="grid grid-cols-2 gap-2">
            <Input v-model="phase.phase" placeholder="Phase" />
            <Input v-model="phase.duration" placeholder="Duration" />
          </div>
        </div>

        <!-- Missing Requirements -->
        <div class="space-y-1.5">
          <Label>Missing Requirements <span class="text-muted-foreground text-xs">(one per line)</span></Label>
          <Textarea v-model="missingText" rows="4" />
        </div>

        <!-- Questions -->
        <div class="space-y-1.5">
          <Label>Questions to Ask the Client <span class="text-muted-foreground text-xs">(one per line)</span></Label>
          <Textarea v-model="questionsText" rows="4" />
        </div>

        <!-- Risks -->
        <div class="space-y-2">
          <Label>Potential Risks / Blockers</Label>
          <div v-for="(risk, i) in form.risks" :key="i" class="space-y-1.5 rounded-lg border p-3">
            <Input v-model="risk.title" placeholder="Risk title" class="font-medium" />
            <Textarea v-model="risk.explanation" placeholder="Why this is a risk" rows="2" />
          </div>
        </div>

        <!-- Quotation -->
        <div class="space-y-2">
          <Label>Initial Quotation Range</Label>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="space-y-1.5">
              <Label class="text-muted-foreground text-xs">Low</Label>
              <Input v-model.number="form.quotation.range_low" type="number" />
            </div>
            <div class="space-y-1.5">
              <Label class="text-muted-foreground text-xs">High</Label>
              <Input v-model.number="form.quotation.range_high" type="number" />
            </div>
            <div class="space-y-1.5">
              <Label class="text-muted-foreground text-xs">Currency</Label>
              <Input v-model="form.quotation.currency" />
            </div>
          </div>
          <p class="text-muted-foreground text-sm">
            {{ formatEtb(form.quotation.range_low) }} – {{ formatEtb(form.quotation.range_high) }}
          </p>
          <Label class="text-muted-foreground text-xs">Reasoning (one per line)</Label>
          <Textarea v-model="reasoningText" rows="4" />
        </div>

        <div class="flex flex-wrap gap-2">
          <Button :disabled="saving" @click="save">
            <Save class="size-4" /> {{ saving ? 'Saving…' : 'Save Analysis' }}
          </Button>
          <Button variant="outline" :disabled="saving" @click="cancelEdit">
            <X class="size-4" /> Cancel
          </Button>
        </div>
      </template>

      <template v-if="lead.aiAnalysis">
        <!-- Proposal -->
        <div class="space-y-2 border-t pt-4">
          <Label>Proposal</Label>
          <p v-if="!lead.convertedClientId" class="text-muted-foreground text-sm">
            Convert this lead to a client before generating a proposal.
          </p>
          <template v-else>
            <p class="text-muted-foreground text-sm">
              Generates a proposal document from the analysis above — find it, and any resulting
              contract or invoice, in the Documents section below.
            </p>
            <Button :disabled="generatingProposal" @click="generateProposal">
              <FileText class="size-4" /> {{ generatingProposal ? 'Generating…' : 'Generate Proposal' }}
            </Button>
          </template>
        </div>
      </template>
    </CardContent>
  </Card>
</template>
