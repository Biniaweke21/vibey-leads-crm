<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import PasswordInput from '@/components/base/PasswordInput.vue'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/services/apiError'
import { uploadFiles } from '@/utils/attachment'

const authStore = useAuthStore()
const toast = useToast()
const uploadingSignature = ref(false)

async function uploadSignature(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploadingSignature.value = true
  try {
    const [uploaded] = await uploadFiles([file])
    await authStore.updateSignature(uploaded.secure_url)
    toast.success('Signature saved.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not upload your signature.'))
  } finally {
    uploadingSignature.value = false
  }
}

async function removeSignature() {
  try {
    await authStore.updateSignature(null)
    toast.success('Signature removed.')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not remove your signature.'))
  }
}

const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  newPasswordConfirmation: '',
})
const changingPassword = ref(false)

async function changePassword() {
  if (
    !passwordForm.currentPassword ||
    !passwordForm.newPassword ||
    !passwordForm.newPasswordConfirmation
  ) {
    toast.error('Fill in all password fields.')
    return
  }
  if (passwordForm.newPassword !== passwordForm.newPasswordConfirmation) {
    toast.error('New passwords do not match.')
    return
  }
  changingPassword.value = true
  try {
    await authStore.changePassword({
      current_password: passwordForm.currentPassword,
      password: passwordForm.newPassword,
      password_confirmation: passwordForm.newPasswordConfirmation,
    })
    toast.success('Password changed successfully.')
    passwordForm.currentPassword = ''
    passwordForm.newPassword = ''
    passwordForm.newPasswordConfirmation = ''
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not change the password.'))
  } finally {
    changingPassword.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Change Password</CardTitle>
      <CardDescription>Update the password you sign in with.</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-1.5">
        <Label for="current-password">Current Password</Label>
        <PasswordInput
          id="current-password"
          v-model="passwordForm.currentPassword"
          placeholder="••••••••"
        />
      </div>
      <div class="space-y-1.5">
        <Label for="new-password">New Password</Label>
        <PasswordInput id="new-password" v-model="passwordForm.newPassword" placeholder="••••••••" />
      </div>
      <div class="space-y-1.5">
        <Label for="new-password-confirmation">Confirm New Password</Label>
        <PasswordInput
          id="new-password-confirmation"
          v-model="passwordForm.newPasswordConfirmation"
          placeholder="••••••••"
        />
      </div>
      <Button :disabled="changingPassword" @click="changePassword">
        {{ changingPassword ? 'Updating…' : 'Change Password' }}
      </Button>
    </CardContent>
  </Card>

  <Card class="mt-6">
    <CardHeader>
      <CardTitle>My Signature</CardTitle>
      <CardDescription>
        Used on documents you send, when you choose to include it.
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-3">
      <div class="flex items-center gap-3">
        <img
          v-if="authStore.user?.signatureUrl"
          :src="authStore.user.signatureUrl"
          alt="My signature"
          class="h-14 rounded border object-contain p-1"
        />
        <input
          type="file"
          accept="image/*"
          :disabled="uploadingSignature"
          @change="uploadSignature"
        />
      </div>
      <Button v-if="authStore.user?.signatureUrl" variant="outline" size="sm" @click="removeSignature">
        Remove Signature
      </Button>
    </CardContent>
  </Card>
</template>
