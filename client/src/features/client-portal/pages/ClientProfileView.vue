<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Mail, Phone } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import PasswordInput from '@/components/base/PasswordInput.vue'
import { useClientPortalStore } from '@/features/client-portal/store/clientPortal'
import { useClientPortalAuthStore } from '@/features/client-portal/store/clientPortalAuth'
import { apiErrorMessage } from '@/services/apiError'

const toast = useToast()
const portalStore = useClientPortalStore()
const portalAuthStore = useClientPortalAuthStore()
const loading = ref(true)
const saving = ref(false)
const changingPassword = ref(false)

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const notifyProjectUpdates = ref(true)
const notifyInvoices = ref(true)
const notifyMeetingReminders = ref(true)
const notifyMarketing = ref(false)

onMounted(async () => {
  try {
    const profile = await portalStore.fetchProfile()
    notifyProjectUpdates.value = profile.notification_preferences.project_updates
    notifyInvoices.value = profile.notification_preferences.invoices
    notifyMeetingReminders.value = profile.notification_preferences.meeting_reminders
    notifyMarketing.value = profile.notification_preferences.marketing
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load your profile.'))
  } finally {
    loading.value = false
  }
})

const contacts = computed(() => {
  const profile = portalStore.profile
  if (!profile) return []
  return [profile.primary_contact, ...profile.secondary_contacts].filter((c) => c !== null)
})

async function changePassword() {
  if (!currentPassword.value || !newPassword.value) {
    toast.error('Fill in your current and new password.')
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    toast.error('New password and confirmation do not match.')
    return
  }
  changingPassword.value = true
  try {
    await portalAuthStore.changePassword({
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    toast.success('Password changed successfully.')
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not change your password.'))
  } finally {
    changingPassword.value = false
  }
}

async function savePreferences() {
  saving.value = true
  try {
    await portalStore.updateProfile({
      notification_preferences: {
        project_updates: notifyProjectUpdates.value,
        invoices: notifyInvoices.value,
        meeting_reminders: notifyMeetingReminders.value,
        marketing: notifyMarketing.value,
      },
    })
    toast.success('Notification preferences saved.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not save your preferences.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="portalStore.profile" class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">Profile</h1>
      <p class="text-muted-foreground mt-1">Your company details and account preferences.</p>
    </div>

    <Card>
      <CardHeader><CardTitle>Company Information</CardTitle></CardHeader>
      <CardContent class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div>
          <p class="text-muted-foreground text-xs">Company</p>
          <p class="font-medium">{{ portalStore.profile.company.name }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">Industry</p>
          <p class="font-medium">{{ portalStore.profile.company.industry }}</p>
        </div>
        <div>
          <p class="text-muted-foreground text-xs">City</p>
          <p class="font-medium">{{ portalStore.profile.company.city }}</p>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>Contacts</CardTitle></CardHeader>
      <CardContent class="space-y-2">
        <div v-for="contact in contacts" :key="contact!.id" class="rounded-lg border p-3 text-sm">
          <p class="font-medium">{{ contact!.name }}</p>
          <p class="text-muted-foreground text-xs">{{ contact!.role }}</p>
          <div class="text-muted-foreground mt-1 flex flex-wrap gap-3 text-xs">
            <span class="flex items-center gap-1"
              ><Mail class="size-3.5" /> {{ contact!.email }}</span
            >
            <span v-if="contact!.phone" class="flex items-center gap-1"
              ><Phone class="size-3.5" /> {{ contact!.phone }}</span
            >
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Change Password</CardTitle>
      </CardHeader>
      <CardContent class="space-y-3">
        <p
          v-if="portalAuthStore.user?.mustChangePassword"
          class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
        >
          You're signed in with a temporary password. Please set a new password to continue.
        </p>
        <div class="space-y-1.5">
          <Label>Current Password</Label>
          <PasswordInput v-model="currentPassword" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <Label>New Password</Label>
            <PasswordInput v-model="newPassword" />
          </div>
          <div class="space-y-1.5">
            <Label>Confirm Password</Label>
            <PasswordInput v-model="confirmPassword" />
          </div>
        </div>
        <Button :disabled="changingPassword" @click="changePassword">
          {{ changingPassword ? 'Updating…' : 'Update Password' }}
        </Button>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>Notification Preferences</CardTitle></CardHeader>
      <CardContent class="space-y-4">
        <label class="flex items-center justify-between">
          <span class="text-sm">Project updates</span>
          <Switch v-model="notifyProjectUpdates" />
        </label>
        <label class="flex items-center justify-between">
          <span class="text-sm">New invoices</span>
          <Switch v-model="notifyInvoices" />
        </label>
        <label class="flex items-center justify-between">
          <span class="text-sm">Meeting reminders</span>
          <Switch v-model="notifyMeetingReminders" />
        </label>
        <label class="flex items-center justify-between">
          <span class="text-sm">Product news & offers</span>
          <Switch v-model="notifyMarketing" />
        </label>
        <Button variant="outline" :disabled="saving" @click="savePreferences">
          {{ saving ? 'Saving…' : 'Save Preferences' }}
        </Button>
      </CardContent>
    </Card>
  </div>
  <div v-else-if="!loading" class="py-20 text-center">
    <p class="text-muted-foreground">Could not load your profile.</p>
  </div>
</template>
