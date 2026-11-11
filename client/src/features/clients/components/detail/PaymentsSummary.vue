<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Receipt } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import EmptyState from '@/components/base/EmptyState.vue'
import type { Client, ClientPayment } from '@/stores/clients'
import { formatEtb } from '@/utils/currency'

defineProps<{ client: Client }>()
const toast = useToast()

const description = ref('')
const amount = ref('')
const status = ref<ClientPayment['status']>('paid')

// Recording payments from the CRM isn't backed by the API yet — invoices already track payments.
function addPayment() {
  if (!description.value.trim() || !Number(amount.value)) return
  toast.info('Recording payments here isn’t available yet — use Invoices instead.')
  description.value = ''
  amount.value = ''
  status.value = 'paid'
}

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
      <CardTitle>Payments Summary</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="bg-muted rounded-lg p-3 text-sm">
        <p class="text-muted-foreground text-xs">Total Received</p>
        <p class="text-lg font-semibold">{{ formatEtb(client.revenue) }}</p>
      </div>

      <div class="space-y-2">
        <Input v-model="description" placeholder="Description" class="w-full" />
        <div class="flex gap-2">
          <Input v-model="amount" type="number" placeholder="Amount (ETB)" class="flex-1" />
          <Select v-model="status">
            <SelectTrigger class="w-32"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="paid">Paid</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
            </SelectContent>
          </Select>
          <Button size="sm" :disabled="!description.trim() || !amount" @click="addPayment"
            >Add</Button
          >
        </div>
      </div>

      <EmptyState
        v-if="client.payments.length === 0"
        :icon="Receipt"
        title="No payments recorded"
      />
      <div v-else class="space-y-2">
        <div
          v-for="payment in client.payments"
          :key="payment.id"
          class="flex items-center justify-between gap-2 rounded-lg border p-3 text-sm"
        >
          <div>
            <p class="font-medium">{{ payment.description }}</p>
            <p class="text-muted-foreground text-xs">{{ formatDate(payment.date) }}</p>
          </div>
          <div class="flex items-center gap-2">
            <p class="font-medium">{{ formatEtb(payment.amount) }}</p>
            <Badge :variant="payment.status === 'paid' ? 'default' : 'secondary'">{{
              payment.status
            }}</Badge>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
