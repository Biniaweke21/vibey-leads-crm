<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Switch } from '@/components/ui/switch'
import { Button } from '@/components/ui/button'
import { useSettingsStore } from '@/features/settings/store/settings'
import { apiErrorMessage } from '@/services/apiError'

const settingsStore = useSettingsStore()
const toast = useToast()
const saving = ref(false)

const form = reactive({ ...settingsStore.notifications })

onMounted(async () => {
  try {
    await settingsStore.fetchNotifications()
    Object.assign(form, settingsStore.notifications)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load notification preferences.'))
  }
})

const options: { key: keyof typeof form; label: string; description: string }[] = [
  {
    key: 'newLeads',
    label: 'New Leads',
    description: 'Notify the team when a new lead submits the request form.',
  },
  {
    key: 'invoicePaid',
    label: 'Invoice Paid',
    description: 'Notify when a client pays an invoice.',
  },
  {
    key: 'meetingReminders',
    label: 'Meeting Reminders',
    description: 'Remind the team before scheduled meetings.',
  },
  {
    key: 'weeklyDigest',
    label: 'Weekly Digest',
    description: 'A weekly summary of pipeline activity.',
  },
  {
    key: 'productUpdates',
    label: 'Product Updates',
    description: 'News about new Vibey Leads features.',
  },
]

async function save() {
  saving.value = true
  try {
    await settingsStore.updateNotifications({ ...form })
    toast.success('Notification preferences saved.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not save preferences.'))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Notifications</CardTitle>
      <CardDescription>Choose what the team gets notified about.</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <label
        v-for="option in options"
        :key="option.key"
        class="flex items-center justify-between gap-4"
      >
        <span>
          <span class="block text-sm font-medium">{{ option.label }}</span>
          <span class="text-muted-foreground text-xs">{{ option.description }}</span>
        </span>
        <Switch v-model="form[option.key]" />
      </label>
      <Button :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save Preferences' }}</Button>
    </CardContent>
  </Card>
</template>
