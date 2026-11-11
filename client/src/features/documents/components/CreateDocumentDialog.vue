<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Plus, Trash2 } from '@lucide/vue'
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
import { useClientsStore } from '@/stores/clients'
import { useProjectsStore } from '@/stores/projects'
import { useDocumentsStore } from '@/features/documents/store/documents'
import { apiErrorMessage } from '@/services/apiError'

const open = defineModel<boolean>('open', { required: true })

const router = useRouter()
const toast = useToast()
const clientsStore = useClientsStore()
const projectsStore = useProjectsStore()
const documentsStore = useDocumentsStore()

onMounted(() => {
  if (clientsStore.clients.length === 0) clientsStore.fetchClients().catch(() => {})
  if (projectsStore.projects.length === 0) projectsStore.fetchProjects().catch(() => {})
})

const type = ref<'contract' | 'invoice'>('invoice')
const clientId = ref('')
const projectId = ref('')
const dueDate = ref('')
const saving = ref(false)

// Invoice-only — the stage IS the invoice's title/description, no free text needed.
const invoiceStages = ['20% Initial Deposit', '40% Mid Project Deposit', '40% Final Deposit'] as const
const stage = ref('')
const amount = ref('')

// Contract-only
const title = ref('')
const lineItems = ref([{ description: '', quantity: '1', unitPrice: '' }])

const projectsForClient = computed(() =>
  clientId.value ? projectsStore.forClient(clientId.value) : [],
)

function addLineItem() {
  lineItems.value.push({ description: '', quantity: '1', unitPrice: '' })
}

function removeLineItem(i: number) {
  lineItems.value.splice(i, 1)
}

const isValid = computed(() => {
  if (!clientId.value) return false
  if (type.value === 'invoice') {
    return !!stage.value && Number(amount.value) > 0
  }
  return (
    !!title.value.trim() &&
    lineItems.value.every((li) => li.description.trim() && Number(li.quantity) > 0 && li.unitPrice !== '')
  )
})

function reset() {
  type.value = 'invoice'
  clientId.value = ''
  projectId.value = ''
  dueDate.value = ''
  stage.value = ''
  amount.value = ''
  title.value = ''
  lineItems.value = [{ description: '', quantity: '1', unitPrice: '' }]
}

async function create() {
  if (!isValid.value) return
  saving.value = true
  try {
    let id: string
    if (type.value === 'invoice') {
      id = await documentsStore.createDocument({
        type: 'invoice',
        clientId: clientId.value,
        projectId: projectId.value || undefined,
        title: stage.value,
        dueDate: dueDate.value || undefined,
        lineItems: [{ description: stage.value, quantity: 1, unitPrice: Number(amount.value) }],
      })
      // "Send" is what actually emails the client — without this the invoice
      // would only be visible if they happen to check the portal.
      await documentsStore.send(id)
      toast.success('Invoice created and emailed to the client.')
    } else {
      id = await documentsStore.createDocument({
        type: 'contract',
        clientId: clientId.value,
        projectId: projectId.value || undefined,
        title: title.value.trim(),
        lineItems: lineItems.value.map((li) => ({
          description: li.description.trim(),
          quantity: Number(li.quantity),
          unitPrice: Number(li.unitPrice),
        })),
      })
      toast.success('Contract created.')
    }
    const createdType = type.value
    reset()
    open.value = false
    router.push(`/dashboard/documents/${createdType}s/${id}`)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not create the document.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-xl">
      <DialogHeader>
        <DialogTitle>New Document</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Type</Label>
            <Select v-model="type">
              <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="invoice">Invoice</SelectItem>
                <SelectItem value="contract">Contract</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div v-if="type === 'invoice'" class="space-y-1.5">
            <Label>Due Date</Label>
            <Input v-model="dueDate" type="date" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>Client</Label>
            <Select v-model="clientId">
              <SelectTrigger class="w-full"><SelectValue placeholder="Select a client" /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="client in clientsStore.clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <Label>Project <span class="text-muted-foreground text-xs">(optional)</span></Label>
            <Select v-model="projectId" :disabled="!clientId">
              <SelectTrigger class="w-full"><SelectValue placeholder="None" /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="project in projectsForClient" :key="project.id" :value="project.id">
                  {{ project.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <template v-if="type === 'invoice'">
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label>Payment Stage</Label>
              <Select v-model="stage">
                <SelectTrigger class="w-full"><SelectValue placeholder="Select a stage" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="s in invoiceStages" :key="s" :value="s">{{ s }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label>Amount (ETB)</Label>
              <Input v-model="amount" type="number" min="0.01" step="0.01" placeholder="0.00" />
            </div>
          </div>
        </template>

        <template v-else>
          <div class="space-y-1.5">
            <Label>Title</Label>
            <Input v-model="title" placeholder="Website Redesign — Contract" />
          </div>

          <div class="space-y-2">
            <Label>Line Items</Label>
            <div v-for="(item, i) in lineItems" :key="i" class="grid grid-cols-12 items-center gap-2">
              <Input v-model="item.description" placeholder="Description" class="col-span-6" />
              <Input v-model="item.quantity" type="number" min="1" placeholder="Qty" class="col-span-2" />
              <Input
                v-model="item.unitPrice"
                type="number"
                min="0"
                placeholder="Unit Price"
                class="col-span-3"
              />
              <Button
                variant="ghost"
                size="icon-sm"
                class="col-span-1"
                :disabled="lineItems.length === 1"
                @click="removeLineItem(i)"
              >
                <Trash2 class="size-4" />
              </Button>
            </div>
            <Button variant="outline" size="sm" @click="addLineItem">
              <Plus class="size-4" /> Add Line Item
            </Button>
          </div>
        </template>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="open = false">Cancel</Button>
        <Button :disabled="!isValid || saving" @click="create">
          {{
            type === 'invoice'
              ? saving
                ? 'Sending…'
                : 'Create & Send Invoice'
              : saving
                ? 'Creating…'
                : 'Create Document'
          }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
