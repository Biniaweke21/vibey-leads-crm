<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { UserPlus, Trash2 } from '@lucide/vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useSettingsStore, teamRoleOptions, type TeamMemberProfile } from '@/features/settings/store/settings'
import { apiErrorMessage } from '@/services/apiError'

const settingsStore = useSettingsStore()
const toast = useToast()
const loading = ref(true)

onMounted(async () => {
  try {
    await settingsStore.fetchTeam()
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not load the team.'))
  } finally {
    loading.value = false
  }
})

const inviteName = ref('')
const inviteEmail = ref('')
const inviteRole = ref<TeamMemberProfile['role']>('team_member')
const inviting = ref(false)

async function invite() {
  if (!inviteName.value.trim() || !inviteEmail.value.trim()) {
    toast.error('Enter a name and email to invite.')
    return
  }
  inviting.value = true
  try {
    await settingsStore.addTeamMember({
      name: inviteName.value.trim(),
      email: inviteEmail.value.trim(),
      role: inviteRole.value,
    })
    toast.success(`Invited ${inviteName.value.trim()}.`)
    inviteName.value = ''
    inviteEmail.value = ''
    inviteRole.value = 'team_member'
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not invite this team member.'))
  } finally {
    inviting.value = false
  }
}

async function changeRole(id: number, value: unknown) {
  try {
    await settingsStore.updateTeamMemberRole(id, value as TeamMemberProfile['role'])
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not update the role.'))
  }
}

async function remove(id: number) {
  try {
    await settingsStore.removeTeamMember(id)
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not remove this team member.'))
  }
}
</script>

<template>
  <div class="space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>Invite Team Member</CardTitle>
        <CardDescription>Add someone to your Vibey Leads workspace.</CardDescription>
      </CardHeader>
      <CardContent class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1 space-y-1.5">
          <Input v-model="inviteName" placeholder="Full name" />
        </div>
        <div class="flex-1 space-y-1.5">
          <Input v-model="inviteEmail" type="email" placeholder="Email" />
        </div>
        <Select v-model="inviteRole">
          <SelectTrigger class="w-full sm:w-40"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in teamRoleOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        <Button :disabled="inviting" @click="invite">
          <UserPlus class="size-4" /> {{ inviting ? 'Inviting…' : 'Invite' }}
        </Button>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Team</CardTitle>
        <CardDescription>{{ settingsStore.team.length }} members</CardDescription>
      </CardHeader>
      <CardContent>
        <p v-if="loading" class="text-muted-foreground py-6 text-center text-sm">Loading…</p>
        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Role</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="member in settingsStore.team" :key="member.id">
              <TableCell class="font-medium">{{ member.name }}</TableCell>
              <TableCell class="text-muted-foreground">{{ member.email }}</TableCell>
              <TableCell>
                <Select
                  :model-value="member.role"
                  @update:model-value="(v) => changeRole(member.id, v)"
                >
                  <SelectTrigger class="w-40"
                    ><Badge variant="secondary">{{ member.roleLabel }}</Badge></SelectTrigger
                  >
                  <SelectContent>
                    <SelectItem
                      v-for="option in teamRoleOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </TableCell>
              <TableCell class="text-right">
                <Button variant="ghost" size="icon-sm" @click="remove(member.id)">
                  <Trash2 class="size-4" />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>
