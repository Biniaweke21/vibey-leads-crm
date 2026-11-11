<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { CheckCircle2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { useLeadsStore, type NewLead, type RequestFormState } from '@/stores/leads'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { apiErrorMessage } from '@/services/apiError'
import { uploadFiles } from '@/utils/attachment'
import { stepTitles } from '@/features/landing/data/requestForm'
import Step1ContactInfo from '@/features/landing/components/request/Step1ContactInfo.vue'
import Step2ServiceNeeded from '@/features/landing/components/request/Step2ServiceNeeded.vue'
import Step3BusinessInfo from '@/features/landing/components/request/Step3BusinessInfo.vue'
import Step4ProjectDetails from '@/features/landing/components/request/Step4ProjectDetails.vue'
import Step5FeatureSelection from '@/features/landing/components/request/Step5FeatureSelection.vue'
import Step6DesignPreferences from '@/features/landing/components/request/Step6DesignPreferences.vue'
import Step7Budget from '@/features/landing/components/request/Step7Budget.vue'
import Step8Timeline from '@/features/landing/components/request/Step8Timeline.vue'
import Step10Notes from '@/features/landing/components/request/Step10Notes.vue'

const router = useRouter()
const toast = useToast()
const leadsStore = useLeadsStore()
const portalStore = useClientPortalStore()

const steps = [
  Step1ContactInfo,
  Step2ServiceNeeded,
  Step3BusinessInfo,
  Step4ProjectDetails,
  Step5FeatureSelection,
  Step6DesignPreferences,
  Step7Budget,
  Step8Timeline,
  Step10Notes,
]

const step = ref(1)
const submitted = ref(false)
const submitting = ref(false)

const form = reactive<RequestFormState>({
  fullName: '',
  companyName: '',
  phone: '',
  whatsapp: '',
  email: '',
  city: '',
  preferredContact: '',
  services: [],
  otherService: '',
  businessName: '',
  industry: '',
  websiteUrl: '',
  socialLinks: '',
  businessDescription: '',
  projectIdea: '',
  mainGoal: '',
  targetUsers: '',
  problems: '',
  coreFeatures: '',
  features: {},
  otherFeature: '',
  designStyle: '',
  referenceLinks: [],
  designFiles: [],
  budget: '',
  timeline: '',
  generalFiles: [],
  additionalNotes: '',
})

// Contact Info (step 1) is already known for a logged-in client — pre-fill
// it so only the project-specific steps (2 onward) need to be filled in.
onMounted(async () => {
  try {
    const profile = portalStore.profile ?? (await portalStore.fetchProfile())
    form.fullName = profile.account.name
    form.email = profile.account.email
    form.companyName = profile.company.name
    form.city = profile.company.city ?? ''
    form.phone = profile.primary_contact?.phone ?? ''
  } catch {
    // Non-critical — the client can still fill these in manually.
  }
})

const progress = computed(() => (step.value / steps.length) * 100)

const isStepValid = computed(() => {
  switch (step.value) {
    case 1:
      return !!(form.fullName && form.phone && form.email && form.preferredContact)
    case 2:
      return form.services.length > 0
    case 3:
      return !!(form.businessName && form.industry)
    case 4:
      return !!(form.projectIdea && form.mainGoal)
    case 6:
      return !!form.designStyle
    case 7:
      return !!form.budget
    case 8:
      return !!form.timeline
    default:
      return true
  }
})

function next() {
  if (!isStepValid.value) {
    toast.error('Please fill in the required fields before continuing.')
    return
  }
  if (step.value < steps.length) step.value++
}

function back() {
  if (step.value > 1) step.value--
}

async function submit() {
  if (!isStepValid.value) {
    toast.error('Please fill in the required fields before submitting.')
    return
  }
  submitting.value = true
  try {
    const [designFiles, generalFiles] = await Promise.all([
      uploadFiles(form.designFiles),
      uploadFiles(form.generalFiles),
    ])
    const lead: NewLead = { ...form, designFiles, generalFiles }
    await leadsStore.addLead(lead)
    submitted.value = true
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not submit your request. Please try again.'))
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <div v-if="submitted" class="flex flex-col items-center gap-4 py-20 text-center">
      <CheckCircle2 class="text-primary size-16" />
      <h1 class="text-2xl font-bold tracking-tight">Your request has been received.</h1>
      <p class="text-muted-foreground max-w-md">
        Our team will review it and reach out within 24 hours.
      </p>
      <Button size="lg" class="mt-4 rounded-full" @click="router.push('/client/projects')"
        >Back to Projects</Button
      >
    </div>

    <template v-else>
      <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight">Start a New Project</h1>
        <p class="text-muted-foreground mt-1">
          Your contact info is already filled in — tell us about the new project.
        </p>
      </div>

      <div class="mb-8">
        <div class="mb-2 flex items-center justify-between text-sm">
          <span class="font-medium"
            >Step {{ step }} of {{ steps.length }} — {{ stepTitles[step - 1] }}</span
          >
          <span class="text-muted-foreground">{{ Math.round(progress) }}%</span>
        </div>
        <div class="bg-muted h-1.5 w-full overflow-hidden rounded-full">
          <div
            class="bg-primary h-full rounded-full transition-all duration-300"
            :style="{ width: `${progress}%` }"
          />
        </div>
      </div>

      <Card class="border-border/60 rounded-2xl p-6 sm:p-8">
        <component :is="steps[step - 1]" :form="form" />
      </Card>

      <div class="mt-6 flex items-center justify-between gap-3">
        <Button variant="outline" class="rounded-full" :disabled="step === 1" @click="back"
          >Back</Button
        >
        <Button v-if="step < steps.length" class="rounded-full" @click="next">Next</Button>
        <Button v-else class="rounded-full" :disabled="submitting" @click="submit">
          {{ submitting ? 'Submitting…' : 'Submit Request' }}
        </Button>
      </div>
    </template>
  </div>
</template>
