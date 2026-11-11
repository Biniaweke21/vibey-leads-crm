<script setup lang="ts">
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { timelineOptions } from '@/features/landing/data/requestForm'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })
</script>

<template>
  <RadioGroup
    :model-value="form.timeline"
    class="gap-3"
    @update:model-value="(v) => (form.timeline = String(v))"
  >
    <label
      v-for="option in timelineOptions"
      :key="option"
      class="border-input has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-primary/5 flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors"
    >
      <RadioGroupItem :value="option" />
      <span class="text-sm">{{ option }}</span>
    </label>
  </RadioGroup>
</template>
