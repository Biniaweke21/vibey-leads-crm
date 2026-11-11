<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Mail, Phone, UserPlus, Users } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import EmptyState from '@/components/base/EmptyState.vue'
import { useClientsStore, type Client } from '@/stores/clients'
import { apiErrorMessage } from '@/services/apiError'

const props = defineProps<{ client: Client }>()
const clientsStore = useClientsStore()
const toast = useToast()

const name = ref('')
const role = ref('')
const email = ref('')
const phone = ref('')

async function addContact() {
  if (!name.value.trim() || !email.value.trim()) return
  try {
    await clientsStore.addContact(props.client.id, {
      name: name.value.trim(),
      role: role.value.trim() || 'Contact',
      email: email.value.trim(),
      phone: phone.value.trim(),
    })
    name.value = ''
    role.value = ''
    email.value = ''
    phone.value = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not add the contact.'))
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Contacts</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <Input v-model="name" placeholder="Name" />
        <Input v-model="role" placeholder="Role" />
        <Input v-model="email" placeholder="Email" />
        <Input v-model="phone" placeholder="Phone" />
      </div>
      <Button size="sm" :disabled="!name.trim() || !email.trim()" @click="addContact">
        <UserPlus class="size-4" /> Add Contact
      </Button>

      <EmptyState v-if="client.contacts.length === 0" :icon="Users" title="No contacts yet" />
      <div v-else class="space-y-2">
        <div
          v-for="contact in client.contacts"
          :key="contact.id"
          class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"
        >
          <div>
            <p class="font-medium">{{ contact.name }}</p>
            <p class="text-muted-foreground text-xs">{{ contact.role }}</p>
          </div>
          <div class="text-muted-foreground flex flex-col gap-1 text-xs">
            <span class="flex items-center gap-1"
              ><Mail class="size-3.5" /> {{ contact.email }}</span
            >
            <span v-if="contact.phone" class="flex items-center gap-1"
              ><Phone class="size-3.5" /> {{ contact.phone }}</span
            >
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
