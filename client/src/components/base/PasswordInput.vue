<script setup lang="ts">
import { ref } from 'vue'
import { Eye, EyeOff } from '@lucide/vue'
import { Input } from '@/components/ui/input'

defineProps<{ modelValue: string; placeholder?: string; id?: string }>()
defineEmits<{ 'update:modelValue': [value: string] }>()

const visible = ref(false)
</script>

<template>
  <div class="relative">
    <Input
      :id="id"
      :model-value="modelValue"
      :type="visible ? 'text' : 'password'"
      :placeholder="placeholder"
      class="pr-9"
      @update:model-value="(v) => $emit('update:modelValue', String(v))"
    />
    <button
      type="button"
      tabindex="-1"
      class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex items-center px-2.5"
      @click="visible = !visible"
    >
      <component :is="visible ? EyeOff : Eye" class="size-4" />
    </button>
  </div>
</template>
