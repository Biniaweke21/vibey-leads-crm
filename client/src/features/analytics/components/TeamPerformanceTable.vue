<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/components/ui/table'
import type { TeamPerformanceRow } from '@/features/analytics/types'
import { formatEtb } from '@/utils/currency'

defineProps<{ data: TeamPerformanceRow[] }>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Team Performance</CardTitle>
    </CardHeader>
    <CardContent>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Team Member</TableHead>
            <TableHead class="text-right">Assigned Leads</TableHead>
            <TableHead class="text-right">Won Clients</TableHead>
            <TableHead class="text-right">Active Projects</TableHead>
            <TableHead class="text-right">Revenue Managed</TableHead>
            <TableHead class="text-right">Completed Tasks</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="row in data" :key="row.userId">
            <TableCell class="font-medium">{{ row.member }}</TableCell>
            <TableCell class="text-right">{{ row.assignedLeads }}</TableCell>
            <TableCell class="text-right">{{ row.wonClients }}</TableCell>
            <TableCell class="text-right">{{ row.activeProjects }}</TableCell>
            <TableCell class="text-right">{{ formatEtb(row.revenueManaged) }}</TableCell>
            <TableCell class="text-right">{{ row.completedTasks }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
  </Card>
</template>
