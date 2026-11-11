<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Phone, Mail, MessageCircle, CalendarPlus, UserCheck } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  useLeadsStore,
  leadStatuses,
  leadPriorities,
  wonStatuses,
  type Lead,
} from '@/stores/leads'
import { useRemindersStore } from '@/stores/reminders'
import { apiErrorMessage } from '@/services/apiError'
import ConvertToClientDialog from '@/features/leads/components/detail/ConvertToClientDialog.vue'

const props = defineProps<{ lead: Lead }>()
const leadsStore = useLeadsStore()
const remindersStore = useRemindersStore()
const router = useRouter()
const toast = useToast()

const reminderTitle = ref('')
const reminderDate = ref('')
const convertDialogOpen = ref(false)

async function changeStatus(value: unknown) {
  if (wonStatuses.includes(value as Lead['status']) && !props.lead.convertedClientId) {
    convertDialogOpen.value = true
    return
  }
  try {
    await leadsStore.updateStatus(props.lead.id, value as Lead['status'])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the stage.'))
  }
}

function goToClient() {
  if (props.lead.convertedClientId) router.push(`/dashboard/clients/${props.lead.convertedClientId}`)
}

async function changePriority(value: unknown) {
  try {
    await leadsStore.updatePriority(props.lead.id, value as Lead['priority'])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the priority.'))
  }
}

async function addReminder() {
  if (!reminderTitle.value.trim() || !reminderDate.value) return
  try {
    await remindersStore.addReminderForLead(
      props.lead.id,
      reminderTitle.value.trim(),
      new Date(reminderDate.value).toISOString(),
    )
    toast.success('Reminder added.')
    reminderTitle.value = ''
    reminderDate.value = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the reminder.'))
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Quick Actions</CardTitle>
    </CardHeader>
    <CardContent class="space-y-5">
      <div>
        <p class="text-muted-foreground mb-2 text-xs">Pipeline Stage</p>
        <Select :model-value="lead.status" @update:model-value="changeStatus">
          <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem v-for="s in leadStatuses" :key="s.value" :value="s.value">{{
              s.label
            }}</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Button
        v-if="lead.convertedClientId"
        variant="outline"
        size="sm"
        class="w-full justify-start"
        @click="goToClient"
      >
        <UserCheck class="size-4" /> View Client
      </Button>
      <Button
        v-else
        size="sm"
        class="w-full justify-start"
        @click="convertDialogOpen = true"
      >
        <UserCheck class="size-4" /> Convert to Client
      </Button>
      <ConvertToClientDialog v-model:open="convertDialogOpen" :lead="lead" />

      <div>
        <p class="text-muted-foreground mb-2 text-xs">Priority</p>
        <Select :model-value="lead.priority" @update:model-value="changePriority">
          <SelectTrigger class="w-full"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem v-for="p in leadPriorities" :key="p.value" :value="p.value">{{
              p.label
            }}</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="flex flex-col gap-2">
        <Button as-child variant="outline" size="sm" class="justify-start">
          <a :href="`tel:${lead.phone}`"><Phone class="size-4" /> Call {{ lead.fullName }}</a>
        </Button>
        <Button as-child variant="outline" size="sm" class="justify-start">
          <a :href="`mailto:${lead.email}`"><Mail class="size-4" /> Email {{ lead.fullName }}</a>
        </Button>
        <Button v-if="lead.whatsapp" as-child variant="outline" size="sm" class="justify-start">
          <a
            :href="`https://wa.me/${lead.whatsapp.replace(/\D/g, '')}`"
            target="_blank"
            rel="noopener noreferrer"
          >
            <MessageCircle class="size-4" /> WhatsApp
          </a>
        </Button>
      </div>

      <div>
        <p class="text-muted-foreground mb-2 text-xs">Add Reminder</p>
        <div class="space-y-2">
          <Input v-model="reminderTitle" placeholder="Reminder title" />
          <Input v-model="reminderDate" type="datetime-local" />
          <Button
            size="sm"
            class="w-full"
            :disabled="!reminderTitle.trim() || !reminderDate"
            @click="addReminder"
          >
            <CalendarPlus class="size-4" /> Add Reminder
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
