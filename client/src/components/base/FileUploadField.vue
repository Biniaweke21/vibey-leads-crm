<script setup lang="ts">
import { X, Upload } from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    modelValue: File[]
    multiple?: boolean
    accept?: string
    hint?: string
  }>(),
  { multiple: false },
)

const emit = defineEmits<{ 'update:modelValue': [File[]] }>()

function onChange(e: Event) {
  const input = e.target as HTMLInputElement
  const picked = Array.from(input.files ?? [])
  emit('update:modelValue', props.multiple ? [...props.modelValue, ...picked] : picked)
  input.value = ''
}

function remove(index: number) {
  emit(
    'update:modelValue',
    props.modelValue.filter((_, i) => i !== index),
  )
}

function formatSize(bytes: number) {
  return bytes < 1024 * 1024
    ? `${Math.round(bytes / 1024)} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <label
      class="border-input hover:border-primary/50 hover:bg-accent/50 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-4 text-sm transition-colors"
    >
      <Upload class="text-muted-foreground size-4" />
      <span>Click to upload{{ multiple ? ' files' : '' }}</span>
      <input type="file" class="hidden" :multiple="multiple" :accept="accept" @change="onChange" />
    </label>
    <p v-if="hint" class="text-muted-foreground text-xs">{{ hint }}</p>
    <ul v-if="modelValue.length" class="flex flex-col gap-1.5">
      <li
        v-for="(file, i) in modelValue"
        :key="`${file.name}-${i}`"
        class="bg-muted/50 flex items-center justify-between rounded-md px-3 py-1.5 text-xs"
      >
        <span class="truncate">{{ file.name }} · {{ formatSize(file.size) }}</span>
        <button
          type="button"
          class="text-muted-foreground hover:text-destructive shrink-0"
          @click="remove(i)"
        >
          <X class="size-3.5" />
        </button>
      </li>
    </ul>
  </div>
</template>
