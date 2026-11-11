<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { useSettingsStore } from '@/features/settings/store/settings'
import { apiErrorMessage } from '@/services/apiError'
import { uploadFiles } from '@/utils/attachment'

const settingsStore = useSettingsStore()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const saving = ref(false)
const uploadingStamp = ref(false)
const connectingGoogle = ref(false)

const form = reactive({ ...settingsStore.company })

async function uploadStamp(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploadingStamp.value = true
  try {
    const [uploaded] = await uploadFiles([file])
    form.stamp_url = uploaded.secure_url
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload the stamp image.'))
  } finally {
    uploadingStamp.value = false
  }
}

onMounted(async () => {
  try {
    await settingsStore.fetchCompany()
    Object.assign(form, settingsStore.company)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load company settings.'))
  }

  if (route.query.google === 'connected') toast.success('Google Calendar connected.')
  if (route.query.google === 'error') toast.error('Could not connect Google Calendar.')
  if (route.query.google) router.replace({ query: {} })

  try {
    await settingsStore.fetchGoogleStatus()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load the Google Calendar connection status.'))
  }
})

async function connectGoogle() {
  connectingGoogle.value = true
  try {
    window.location.href = await settingsStore.connectGoogle()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not start the Google connection.'))
    connectingGoogle.value = false
  }
}

async function disconnectGoogle() {
  try {
    await settingsStore.disconnectGoogle()
    toast.success('Google Calendar disconnected.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not disconnect Google Calendar.'))
  }
}

async function save() {
  saving.value = true
  try {
    await settingsStore.updateCompany({ ...form })
    toast.success('Company information saved.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not save company information.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Company</CardTitle>
      <CardDescription>Your business details, shown on proposals and invoices.</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-1.5">
        <Label>Company Name</Label>
        <Input v-model="form.name" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label>Industry</Label>
          <Input v-model="form.industry" />
        </div>
        <div class="space-y-1.5">
          <Label>Website</Label>
          <Input v-model="form.website" />
        </div>
      </div>
      <div class="space-y-1.5">
        <Label>Address</Label>
        <Input v-model="form.address" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label>Phone</Label>
          <Input v-model="form.phone" />
        </div>
        <div class="space-y-1.5">
          <Label>Email</Label>
          <Input v-model="form.email" type="email" />
        </div>
      </div>
      <div class="space-y-1.5">
        <Label>Logo URL</Label>
        <Input v-model="form.logo_url" placeholder="https://…/logo.png" />
        <p class="text-muted-foreground text-xs">Shown on generated proposal/contract/invoice PDFs.</p>
      </div>
      <div class="space-y-1.5">
        <Label>Company Stamp</Label>
        <div class="flex items-center gap-3">
          <img
            v-if="form.stamp_url"
            :src="form.stamp_url"
            alt="Company stamp"
            class="h-16 rounded border object-contain p-1"
          />
          <input type="file" accept="image/*" :disabled="uploadingStamp" @change="uploadStamp" />
        </div>
        <p class="text-muted-foreground text-xs">
          Stamped onto documents when the sender chooses to include it.
        </p>
      </div>
      <div class="space-y-1.5">
        <Label>Brand Color</Label>
        <div class="flex items-center gap-2">
          <input v-model="form.primary_color" type="color" class="h-9 w-12 rounded border" />
          <Input v-model="form.primary_color" class="w-32" />
        </div>
      </div>
      <Button :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save Changes' }}</Button>
    </CardContent>
  </Card>

  <Card class="mt-6">
    <CardHeader>
      <CardTitle>Google Calendar</CardTitle>
      <CardDescription>
        Connect one company Google account to generate a real Google Meet link automatically
        whenever a meeting is booked.
      </CardDescription>
    </CardHeader>
    <CardContent>
      <div v-if="settingsStore.googleCalendar.connected" class="flex items-center justify-between">
        <p class="text-sm">
          Connected as
          <span class="font-medium">{{ settingsStore.googleCalendar.email }}</span>
        </p>
        <Button variant="outline" size="sm" @click="disconnectGoogle">Disconnect</Button>
      </div>
      <div v-else class="flex items-center justify-between">
        <p class="text-muted-foreground text-sm">Not connected — meeting links must be entered manually.</p>
        <Button size="sm" :disabled="connectingGoogle" @click="connectGoogle">
          {{ connectingGoogle ? 'Redirecting…' : 'Connect Google Calendar' }}
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
