<script setup lang="ts">
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/base/FormField.vue'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })
</script>

<template>
  <FormField label="Anything else we should know?">
    <Textarea
      v-model="form.additionalNotes"
      rows="6"
      placeholder="Optional notes, questions, or context..."
    />
  </FormField>
</template>
