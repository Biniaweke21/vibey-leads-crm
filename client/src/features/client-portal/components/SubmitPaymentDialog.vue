<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { uploadFiles } from '@/utils/attachment'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ documentId: number | string }>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ submitted: [] }>()

const portalStore = useClientPortalStore()
const toast = useToast()

const stages = [
  { value: 'deposit', label: 'Deposit' },
  { value: 'mid_project', label: 'Mid-Project' },
  { value: 'final_delivery', label: 'Final Delivery' },
] as const

const amount = ref('')
const stage = ref<'deposit' | 'mid_project' | 'final_delivery' | ''>('')
const file = ref<File | null>(null)
const submitting = ref(false)

function reset() {
  amount.value = ''
  stage.value = ''
  file.value = null
}

function onPicked(event: Event) {
  const input = event.target as HTMLInputElement
  file.value = input.files?.[0] ?? null
}

async function submit() {
  if (!amount.value || !stage.value || !file.value) return
  submitting.value = true
  try {
    const [uploaded] = await uploadFiles([file.value])
    await portalStore.submitPaymentProof(props.documentId, {
      amount: Number(amount.value),
      stage: stage.value,
      ...uploaded,
    })
    toast.success('Payment proof submitted — awaiting approval.')
    emit('submitted')
    reset()
    open.value = false
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not submit the payment proof.'))
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Submit a Payment</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <div class="space-y-1.5">
          <Label>Amount</Label>
          <Input v-model="amount" type="number" min="0.01" step="0.01" placeholder="0.00" />
        </div>
        <div class="space-y-1.5">
          <Label>Payment Stage</Label>
          <Select v-model="stage">
            <SelectTrigger class="w-full"><SelectValue placeholder="Select a stage" /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1.5">
          <Label>Receipt or Screenshot</Label>
          <label
            class="border-input hover:border-primary/50 hover:bg-accent/50 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-3 text-sm transition-colors"
          >
            <span>{{ file ? file.name : 'Click to choose a file' }}</span>
            <input type="file" accept="image/*,.pdf" class="hidden" @change="onPicked" />
          </label>
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="open = false">Cancel</Button>
        <Button :disabled="!amount || !stage || !file || submitting" @click="submit">
          {{ submitting ? 'Submitting…' : 'Submit Payment' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
