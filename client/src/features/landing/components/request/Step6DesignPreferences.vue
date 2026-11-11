<script setup lang="ts">
import { ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import FormField from '@/components/base/FormField.vue'
import FileUploadField from '@/components/base/FileUploadField.vue'
import { designStyles } from '@/features/landing/data/requestForm'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })

const logo = ref<File[]>([])
const brandGuidelines = ref<File[]>([])
const colorPalette = ref<File[]>([])
const inspiration = ref<File[]>([])

watch([logo, brandGuidelines, colorPalette, inspiration], () => {
  form.value.designFiles = [
    ...logo.value,
    ...brandGuidelines.value,
    ...colorPalette.value,
    ...inspiration.value,
  ]
})

function setReferenceLink(index: number, value: string) {
  const links = [...form.value.referenceLinks]
  links[index] = value
  form.value.referenceLinks = links
}
</script>

<template>
  <div class="grid gap-6">
    <div>
      <p class="mb-3 text-sm font-medium">Style <span class="text-destructive">*</span></p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="style in designStyles"
          :key="style"
          type="button"
          class="rounded-full border px-4 py-2 text-sm transition-colors"
          :class="
            form.designStyle === style
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-input hover:bg-accent'
          "
          @click="form.designStyle = style"
        >
          {{ style }}
        </button>
      </div>
    </div>

    <div>
      <p class="mb-3 text-sm font-medium">Reference Website Links</p>
      <div class="grid gap-2 sm:grid-cols-3">
        <Input
          v-for="i in 3"
          :key="i"
          :model-value="form.referenceLinks[i - 1] ?? ''"
          placeholder="https://example.com"
          @update:model-value="(v) => setReferenceLink(i - 1, String(v))"
        />
      </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <FormField label="Logo">
        <FileUploadField v-model="logo" accept="image/*" />
      </FormField>
      <FormField label="Brand Guidelines PDF">
        <FileUploadField v-model="brandGuidelines" accept="application/pdf" />
      </FormField>
      <FormField label="Color Palette">
        <FileUploadField v-model="colorPalette" accept="image/*" />
      </FormField>
      <FormField label="Inspiration Screenshots">
        <FileUploadField v-model="inspiration" multiple accept="image/*" />
      </FormField>
    </div>
  </div>
</template>
