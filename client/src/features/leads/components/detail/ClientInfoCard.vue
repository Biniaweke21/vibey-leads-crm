<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import type { Lead } from '@/stores/leads'

defineProps<{ lead: Lead }>()

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Client Information</CardTitle>
    </CardHeader>
    <CardContent class="space-y-6">
      <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
        <div>
          <p class="text-muted-foreground text-xs">Contact</p>
          <p class="font-medium">{{ lead.fullName }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Email</p>
          <p class="font-medium">{{ lead.email }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Phone</p>
          <p class="font-medium">{{ lead.phone || '—' }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">City</p>
          <p class="font-medium">{{ lead.city || '—' }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Business</p>
          <p class="font-medium">{{ lead.businessName }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Industry</p>
          <p class="font-medium">{{ lead.industry || '—' }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Budget</p>
          <p class="font-medium">{{ lead.budget }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Timeline</p>
          <p class="font-medium">{{ lead.timeline || '—' }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Submission Date</p>
          <p class="font-medium">{{ formatDate(lead.createdAt) }}</p>
        </div>
      </div>

      <div v-if="lead.services.length > 0">
        <p class="text-muted-foreground mb-2 text-xs">Services Requested</p>
        <div class="flex flex-wrap gap-2">
          <Badge v-for="service in lead.services" :key="service" variant="secondary">{{
            service
          }}</Badge>
        </div>
      </div>

      <div v-if="lead.businessDescription || lead.projectIdea">
        <p class="text-muted-foreground mb-1 text-xs">Project Details</p>
        <p class="text-sm">{{ lead.projectIdea || lead.businessDescription }}</p>
      </div>
    </CardContent>
  </Card>
</template>
