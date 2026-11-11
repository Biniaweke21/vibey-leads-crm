<script setup lang="ts">
import { computed } from 'vue'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
  TableFooter,
} from '@/components/ui/table'
import { lineItemsTotal, type PricingLineItem } from '@/features/documents/types'
import { formatEtb } from '@/utils/currency'

const props = defineProps<{ items: PricingLineItem[]; taxRate?: number }>()

const subtotal = computed(() => lineItemsTotal(props.items))
const tax = computed(() => (props.taxRate ? subtotal.value * (props.taxRate / 100) : 0))
const total = computed(() => subtotal.value + tax.value)
</script>

<template>
  <Table>
    <TableHeader>
      <TableRow>
        <TableHead>Description</TableHead>
        <TableHead class="text-right">Qty</TableHead>
        <TableHead class="text-right">Unit Price</TableHead>
        <TableHead class="text-right">Total</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="item in items" :key="item.id">
        <TableCell>{{ item.description }}</TableCell>
        <TableCell class="text-right">{{ item.quantity }}</TableCell>
        <TableCell class="text-right">{{ formatEtb(item.unitPrice) }}</TableCell>
        <TableCell class="text-right">{{ formatEtb(item.quantity * item.unitPrice) }}</TableCell>
      </TableRow>
    </TableBody>
    <TableFooter>
      <TableRow v-if="taxRate">
        <TableCell colspan="3">Subtotal</TableCell>
        <TableCell class="text-right">{{ formatEtb(subtotal) }}</TableCell>
      </TableRow>
      <TableRow v-if="taxRate">
        <TableCell colspan="3">Tax ({{ taxRate }}%)</TableCell>
        <TableCell class="text-right">{{ formatEtb(tax) }}</TableCell>
      </TableRow>
      <TableRow>
        <TableCell colspan="3" class="font-semibold">Total</TableCell>
        <TableCell class="text-right font-semibold">{{ formatEtb(total) }}</TableCell>
      </TableRow>
    </TableFooter>
  </Table>
</template>
