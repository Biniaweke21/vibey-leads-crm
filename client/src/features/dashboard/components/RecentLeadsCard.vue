<script setup lang="ts">
import { computed } from 'vue'
import { Users } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import EmptyState from '@/components/base/EmptyState.vue'
import { useLeadsStore } from '@/stores/leads'

const leadsStore = useLeadsStore()

const recentLeads = computed(() => leadsStore.leads.slice(0, 5))

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Recent Leads</CardTitle>
    </CardHeader>
    <CardContent>
      <EmptyState
        v-if="recentLeads.length === 0"
        :icon="Users"
        title="No leads yet"
        description="New quote requests will show up here."
      />
      <Table v-else>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Business</TableHead>
            <TableHead>Services</TableHead>
            <TableHead class="text-right">Submitted</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="lead in recentLeads" :key="lead.id">
            <TableCell class="font-medium">{{ lead.fullName }}</TableCell>
            <TableCell>{{ lead.businessName || lead.companyName || '—' }}</TableCell>
            <TableCell>
              <Badge variant="secondary" class="mr-1">{{ lead.services[0] ?? 'General' }}</Badge>
              <span v-if="lead.services.length > 1" class="text-muted-foreground text-xs">
                +{{ lead.services.length - 1 }} more
              </span>
            </TableCell>
            <TableCell class="text-muted-foreground text-right">{{
              formatDate(lead.createdAt)
            }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
  </Card>
</template>
