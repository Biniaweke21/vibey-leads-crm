<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { serviceOptions } from '@/features/landing/data/requestForm'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })

function toggle(service: string, checked: boolean) {
  const set = new Set(form.value.services)
  if (checked) set.add(service)
  else set.delete(service)
  form.value.services = [...set]
}
</script>

<template>
  <div class="grid gap-3 sm:grid-cols-2">
    <label
      v-for="service in serviceOptions"
      :key="service"
      class="border-input has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-primary/5 flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors"
    >
      <Checkbox
        :model-value="form.services.includes(service)"
        @update:model-value="(v) => toggle(service, v === true)"
      />
      <span class="text-sm">{{ service }}</span>
    </label>
    <label
      class="border-input has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-primary/5 flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors sm:col-span-2"
    >
      <Checkbox
        :model-value="form.services.includes('Other')"
        @update:model-value="(v) => toggle('Other', v === true)"
      />
      <span class="text-sm">Other</span>
      <Input
        v-if="form.services.includes('Other')"
        v-model="form.otherService"
        placeholder="Tell us what you need"
        class="ml-2 flex-1"
        @click.stop
      />
    </label>
  </div>
</template>
