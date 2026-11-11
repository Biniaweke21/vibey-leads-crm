<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { featuresByService } from '@/features/landing/data/requestForm'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })

const applicableServices = computed(() =>
  Object.keys(featuresByService).filter((service) => form.value.services.includes(service)),
)

function isChecked(service: string, feature: string) {
  return form.value.features[service]?.includes(feature) ?? false
}

function toggle(service: string, feature: string, checked: boolean) {
  const current = new Set(form.value.features[service] ?? [])
  if (checked) current.add(feature)
  else current.delete(feature)
  form.value.features = { ...form.value.features, [service]: [...current] }
}
</script>

<template>
  <div
    v-if="applicableServices.length === 0"
    class="text-muted-foreground py-8 text-center text-sm"
  >
    No extra configuration needed for the services you selected — click Next to continue.
  </div>
  <div v-else class="grid gap-8">
    <div v-for="service in applicableServices" :key="service">
      <p class="mb-3 text-sm font-semibold">{{ service }} features</p>
      <div class="grid gap-3 sm:grid-cols-2">
        <label
          v-for="feature in featuresByService[service]"
          :key="feature"
          class="border-input has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-primary/5 flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors"
        >
          <Checkbox
            :model-value="isChecked(service, feature)"
            @update:model-value="(v) => toggle(service, feature, v === true)"
          />
          <span class="text-sm">{{ feature }}</span>
        </label>
      </div>
    </div>
  </div>
</template>
