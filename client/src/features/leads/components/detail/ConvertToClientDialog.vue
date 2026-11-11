<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { CheckCircle2, Copy } from '@lucide/vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { useLeadsStore, type Lead, type ConvertLeadResult } from '@/stores/leads'
import { apiErrorMessage } from '@/services/apiError'

const open = defineModel<boolean>('open', { required: true })
const props = defineProps<{ lead: Lead }>()

const router = useRouter()
const leadsStore = useLeadsStore()
const toast = useToast()

const createPortalAccount = ref(true)
const sendWelcomeEmail = ref(true)
const generateTemporaryPassword = ref(true)
const converting = ref(false)
const result = ref<ConvertLeadResult | null>(null)

function reset() {
  createPortalAccount.value = true
  sendWelcomeEmail.value = true
  generateTemporaryPassword.value = true
  result.value = null
}

async function convert() {
  converting.value = true
  try {
    result.value = await leadsStore.convertToClient(props.lead.id, {
      createPortalAccount: createPortalAccount.value,
      sendWelcomeEmail: sendWelcomeEmail.value,
      generateTemporaryPassword: generateTemporaryPassword.value,
    })
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not convert this lead.'))
  } finally {
    converting.value = false
  }
}

async function copyPassword() {
  if (!result.value?.temporaryPassword) return
  await navigator.clipboard.writeText(result.value.temporaryPassword)
  toast.success('Password copied to clipboard.')
}

function viewClient() {
  if (!result.value) return
  const clientId = result.value.clientId
  open.value = false
  reset()
  router.push(`/dashboard/clients/${clientId}`)
}

function close() {
  open.value = false
  reset()
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-md">
      <template v-if="!result && lead.existingClientMatch">
        <DialogHeader>
          <DialogTitle>Add Project to Existing Client</DialogTitle>
          <DialogDescription>
            This lead's email already belongs to {{ lead.existingClientMatch.name }}, an existing
            client. Converting will add a new Project to their account and mark this lead Won —
            no new client or portal account will be created.
          </DialogDescription>
        </DialogHeader>
        <div class="grid grid-cols-2 gap-4 rounded-lg border p-3 text-sm">
          <div>
            <p class="text-muted-foreground text-xs">Existing Client</p>
            <p class="font-medium">{{ lead.existingClientMatch.name }}</p>
          </div>
          <div>
            <p class="text-muted-foreground text-xs">Client Email</p>
            <p class="font-medium">{{ lead.email }}</p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="close">Cancel</Button>
          <Button :disabled="converting" @click="convert">
            {{ converting ? 'Converting…' : 'Add Project' }}
          </Button>
        </DialogFooter>
      </template>

      <template v-else-if="!result">
        <DialogHeader>
          <DialogTitle>Convert to Client</DialogTitle>
          <DialogDescription>
            This creates a client record for {{ lead.businessName }} and marks the lead Won.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4 rounded-lg border p-3 text-sm">
            <div>
              <p class="text-muted-foreground text-xs">Company Name</p>
              <p class="font-medium">{{ lead.businessName }}</p>
            </div>
            <div>
              <p class="text-muted-foreground text-xs">Client Email</p>
              <p class="font-medium">{{ lead.email }}</p>
            </div>
          </div>
          <div class="space-y-3">
            <label class="flex items-center gap-2 text-sm">
              <Checkbox v-model="createPortalAccount" />
              Create Client Portal Account
            </label>
            <label class="flex items-center gap-2 text-sm">
              <Checkbox v-model="sendWelcomeEmail" :disabled="!createPortalAccount" />
              Send Welcome Email
            </label>
            <label class="flex items-center gap-2 text-sm">
              <Checkbox v-model="generateTemporaryPassword" :disabled="!createPortalAccount" />
              Generate Temporary Password
            </label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="close">Cancel</Button>
          <Button :disabled="converting" @click="convert">
            {{ converting ? 'Converting…' : 'Convert' }}
          </Button>
        </DialogFooter>
      </template>

      <template v-else-if="result.existingClient">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <CheckCircle2 class="size-5 text-emerald-600" /> New project added
          </DialogTitle>
          <DialogDescription>
            This lead's email already belongs to an existing client — no duplicate account was
            created. A new Project was added to their existing client record instead.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="close">Close</Button>
          <Button @click="viewClient">View Client</Button>
        </DialogFooter>
      </template>

      <template v-else>
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2">
            <CheckCircle2 class="size-5 text-emerald-600" /> Client created successfully
          </DialogTitle>
        </DialogHeader>
        <div class="space-y-3 text-sm">
          <p v-if="!createPortalAccount" class="text-muted-foreground">
            No client portal account was created for this client.
          </p>
          <template v-else>
            <p class="text-muted-foreground">Portal account created.</p>
            <p v-if="result.emailSent" class="text-muted-foreground">Welcome email sent.</p>
            <template v-else>
              <p class="text-destructive">Email could not be delivered.</p>
              <div v-if="result.temporaryPassword" class="space-y-1.5 rounded-lg border p-3">
                <p class="text-muted-foreground text-xs">Temporary Password</p>
                <div class="flex items-center gap-2">
                  <code class="bg-muted flex-1 rounded px-2 py-1 text-sm">{{
                    result.temporaryPassword
                  }}</code>
                  <Button variant="outline" size="icon-sm" @click="copyPassword">
                    <Copy class="size-4" />
                  </Button>
                </div>
              </div>
            </template>
          </template>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="close">Close</Button>
          <Button @click="viewClient">View Client</Button>
        </DialogFooter>
      </template>
    </DialogContent>
  </Dialog>
</template>
