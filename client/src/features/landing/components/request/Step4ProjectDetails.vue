<script setup lang="ts">
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/base/FormField.vue'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })
</script>

<template>
  <div class="grid gap-5">
    <FormField label="Tell us what you're trying to build" required>
      <Textarea
        v-model="form.projectIdea"
        rows="6"
        placeholder="Describe your idea as if you're explaining it to a friend."
      />
    </FormField>
    <div class="grid gap-5 sm:grid-cols-2">
      <FormField label="Main Goal of the Project" required>
        <Textarea v-model="form.mainGoal" rows="3" placeholder="What should this achieve?" />
      </FormField>
      <FormField label="Target Users / Audience">
        <Textarea v-model="form.targetUsers" rows="3" placeholder="Who is this for?" />
      </FormField>
      <FormField label="Problems You're Solving">
        <Textarea v-model="form.problems" rows="3" placeholder="What pain points does this fix?" />
      </FormField>
      <FormField label="Expected Core Features">
        <Textarea v-model="form.coreFeatures" rows="3" placeholder="Must-have functionality" />
      </FormField>
    </div>
  </div>
</template>
