<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import type { Client } from '@/stores/clients'
import { formatEtb } from '@/utils/currency'

defineProps<{ client: Client }>()

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
      <CardTitle>Company Profile</CardTitle>
    </CardHeader>
    <CardContent class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
      <div>
        <p class="text-muted-foreground text-xs">Industry</p>
        <p class="font-medium">{{ client.industry }}</p>
      </div>
      <div>
        <p class="text-muted-foreground text-xs">City</p>
        <p class="font-medium">{{ client.city }}</p>
      </div>
      <div>
        <p class="text-muted-foreground text-xs">Active Projects</p>
        <p class="font-medium">{{ client.activeProjectsCount }}</p>
      </div>
      <div>
        <p class="text-muted-foreground text-xs">Total Revenue</p>
        <p class="font-medium">{{ formatEtb(client.revenue) }}</p>
      </div>
      <div>
        <p class="text-muted-foreground text-xs">Last Interaction</p>
        <p class="font-medium">{{ formatDate(client.lastInteraction) }}</p>
      </div>
    </CardContent>
  </Card>
</template>
