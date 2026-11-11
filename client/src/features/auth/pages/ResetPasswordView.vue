<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import PasswordInput from '@/components/base/PasswordInput.vue'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/services/apiError'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const email = ref((route.query.email as string) ?? '')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)

async function submit() {
  const token = route.query.token as string | undefined
  if (!token) {
    toast.error('This reset link is invalid or has expired.')
    return
  }
  if (!email.value.trim() || !password.value || !passwordConfirmation.value) {
    toast.error('Fill in all fields.')
    return
  }
  if (password.value !== passwordConfirmation.value) {
    toast.error('Passwords do not match.')
    return
  }
  loading.value = true
  try {
    await authStore.resetPassword({
      token,
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    toast.success('Password set. You can now sign in.')
    router.push(route.path.startsWith('/client') ? '/client/login' : '/login')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not reset the password.'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Card class="w-full max-w-md border-border/60 shadow-xl">
    <CardContent class="p-8">
      <div class="mb-6 flex flex-col items-center text-center">
        <img src="/img/Vibeylogo.jpg" alt="Vibey World" class="size-16 rounded-full object-cover" />
        <h1 class="mt-3 text-2xl font-bold tracking-tight">Set New Password</h1>
        <p class="text-muted-foreground mt-1 text-sm">Choose a new password for your account.</p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="email">Email</Label>
          <Input id="email" v-model="email" type="email" placeholder="you@vibeyworlds.com" />
        </div>
        <div class="space-y-1.5">
          <Label for="password">New Password</Label>
          <PasswordInput id="password" v-model="password" placeholder="••••••••" />
        </div>
        <div class="space-y-1.5">
          <Label for="password_confirmation">Confirm Password</Label>
          <PasswordInput
            id="password_confirmation"
            v-model="passwordConfirmation"
            placeholder="••••••••"
          />
        </div>
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Saving…' : 'Set Password' }}
        </Button>
      </form>
    </CardContent>
  </Card>
</template>
