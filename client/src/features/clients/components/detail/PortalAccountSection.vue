<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { ShieldCheck, ShieldOff, KeyRound, RefreshCw, Mail, Copy } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import { useClientsStore, type Client } from '@/stores/clients'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ client: Client }>()
const clientsStore = useClientsStore()
const authStore = useAuthStore()
const toast = useToast()

const busy = ref(false)
const revealedPassword = ref<string | null>(null)

function formatDate(iso: string | null) {
  if (!iso) return 'Never'
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

async function run<T>(action: () => Promise<T>, successMessage: string, failureMessage: string) {
  busy.value = true
  revealedPassword.value = null
  try {
    const result = await action()
    toast.success(successMessage)
    return result
  } catch (err) {
    toast.error(apiErrorMessage(err, failureMessage))
    return undefined
  } finally {
    busy.value = false
  }
}

async function enable() {
  const result = await run(
    () => clientsStore.enablePortal(props.client.id),
    'Portal account enabled.',
    'Could not enable the portal.',
  )
  if (result && !result.emailSent && result.temporaryPassword) {
    revealedPassword.value = result.temporaryPassword
  }
}

async function disable() {
  await run(
    () => clientsStore.disablePortal(props.client.id),
    'Portal account disabled.',
    'Could not disable the portal.',
  )
}

async function resetPassword() {
  const result = await run(
    () => clientsStore.resetPortalPassword(props.client.id),
    'Password reset.',
    'Could not reset the password.',
  )
  if (result && !result.emailSent && result.temporaryPassword) {
    revealedPassword.value = result.temporaryPassword
  }
}

async function regeneratePassword() {
  const result = await run(
    () => clientsStore.regeneratePortalPassword(props.client.id),
    'Temporary password regenerated.',
    'Could not regenerate the password.',
  )
  if (result?.temporaryPassword) revealedPassword.value = result.temporaryPassword
}

async function resendWelcome() {
  const result = await run(
    () => clientsStore.resendWelcomeEmail(props.client.id),
    'Welcome email sent.',
    'Could not resend the welcome email.',
  )
  if (result && !result.emailSent && result.temporaryPassword) {
    revealedPassword.value = result.temporaryPassword
  }
}

async function copyCredentials() {
  if (!props.client.portalAccount || !revealedPassword.value) return
  await navigator.clipboard.writeText(
    `Email: ${props.client.portalAccount.email}\nPassword: ${revealedPassword.value}`,
  )
  toast.success('Login credentials copied to clipboard.')
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Portal Account</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <EmptyState
        v-if="!client.portalAccount"
        :icon="ShieldOff"
        title="No portal account"
        description="This client doesn't have a client portal account yet."
      />
      <template v-else>
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between gap-2">
            <p class="text-muted-foreground text-xs">Portal Status</p>
            <Badge :class="client.portalAccount.portalEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
              {{ client.portalAccount.portalEnabled ? 'Enabled' : 'Disabled' }}
            </Badge>
          </div>
          <div>
            <p class="text-muted-foreground text-xs">Email</p>
            <p class="font-medium break-all">{{ client.portalAccount.email }}</p>
          </div>
          <div>
            <p class="text-muted-foreground text-xs">Last Login</p>
            <p class="font-medium">{{ formatDate(client.portalAccount.lastLoginAt) }}</p>
          </div>
          <div class="flex items-center justify-between gap-2">
            <p class="text-muted-foreground text-xs">Password Status</p>
            <Badge :class="client.portalAccount.mustChangePassword ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
              {{ client.portalAccount.mustChangePassword ? 'Must Change Password' : 'Up to date' }}
            </Badge>
          </div>
        </div>

        <div v-if="revealedPassword" class="space-y-1.5 rounded-lg border p-3">
          <p class="text-muted-foreground text-xs">Temporary Password</p>
          <div class="flex items-center gap-2">
            <code class="bg-muted min-w-0 flex-1 truncate rounded px-2 py-1 text-sm">{{ revealedPassword }}</code>
            <Button variant="outline" size="icon-sm" class="shrink-0" @click="copyCredentials">
              <Copy class="size-4" />
            </Button>
          </div>
        </div>
      </template>

      <div v-if="authStore.isAdmin" class="flex flex-wrap gap-2">
        <Button
          v-if="!client.portalAccount || !client.portalAccount.portalEnabled"
          size="sm"
          variant="outline"
          :disabled="busy"
          @click="enable"
        >
          <ShieldCheck class="size-4" /> Enable Portal
        </Button>
        <Button v-else size="sm" variant="outline" :disabled="busy" @click="disable">
          <ShieldOff class="size-4" /> Disable Portal
        </Button>
        <template v-if="client.portalAccount">
          <Button size="sm" variant="outline" :disabled="busy" @click="resetPassword">
            <KeyRound class="size-4" /> Reset Password
          </Button>
          <Button size="sm" variant="outline" :disabled="busy" @click="regeneratePassword">
            <RefreshCw class="size-4" /> Generate New Temporary Password
          </Button>
          <Button size="sm" variant="outline" :disabled="busy" @click="resendWelcome">
            <Mail class="size-4" /> Resend Welcome Email
          </Button>
        </template>
      </div>
    </CardContent>
  </Card>
</template>
