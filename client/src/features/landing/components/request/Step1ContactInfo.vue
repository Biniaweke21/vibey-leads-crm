<script setup lang="ts">
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import FormField from '@/components/base/FormField.vue'
import { contactMethods } from '@/features/landing/data/requestForm'
import type { RequestFormState } from '@/stores/leads'

const form = defineModel<RequestFormState>('form', { required: true })
</script>

<template>
  <div class="grid gap-5 sm:grid-cols-2">
    <FormField label="Full Name" required>
      <Input v-model="form.fullName" placeholder="Abebe Kebede" />
    </FormField>
    <FormField label="Company Name" required>
      <Input v-model="form.companyName" placeholder="Your company (optional)" />
    </FormField>
    <FormField label="Phone Number" required>
      <Input v-model="form.phone" type="tel" placeholder="+251 9xx xxx xxx" />
    </FormField>
    <FormField label="WhatsApp Number">
      <Input v-model="form.whatsapp" type="tel" placeholder="+251 9xx xxx xxx" />
    </FormField>
    <FormField label="Email Address" required>
      <Input v-model="form.email" type="email" placeholder="you@email.com" />
    </FormField>
    <FormField label="City / Location">
      <Input v-model="form.city" placeholder="Addis Ababa" />
    </FormField>
    <FormField label="Preferred Contact Method" required>
      <Select v-model="form.preferredContact">
        <SelectTrigger class="w-full">
          <SelectValue placeholder="Select a method" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="method in contactMethods" :key="method" :value="method">
            {{ method }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FormField>
  </div>
</template>
