<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/base/FormField.vue'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })
</script>

<template>
  <div class="grid gap-5">
    <div class="grid gap-5 sm:grid-cols-2">
      <FormField label="Business / Brand Name" required>
        <Input v-model="form.businessName" placeholder="Your brand name" />
      </FormField>
      <FormField label="Industry" required>
        <Input v-model="form.industry" placeholder="e.g. Retail, Fintech, Education" />
      </FormField>
      <FormField label="Existing Website URL">
        <Input v-model="form.websiteUrl" placeholder="https://your-site.com (optional)" />
      </FormField>
      <FormField label="Social Media Links">
        <Input v-model="form.socialLinks" placeholder="Instagram, TikTok, LinkedIn... (optional)" />
      </FormField>
    </div>
    <FormField
      label="Short Business Description"
      :hint="`${form.businessDescription.length}/300 characters`"
    >
      <Textarea
        v-model="form.businessDescription"
        maxlength="300"
        rows="4"
        placeholder="What does your business do?"
      />
    </FormField>
  </div>
</template>
