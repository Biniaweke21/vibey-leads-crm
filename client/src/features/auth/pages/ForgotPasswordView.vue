<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { useAuthStore } from '@/stores/auth'
import { apiErrorMessage } from '@/services/apiError'

const toast = useToast()
const authStore = useAuthStore()

const email = ref('')
const loading = ref(false)
const sent = ref(false)

async function submit() {
  if (!email.value.trim()) {
    toast.error('Enter your email address.')
    return
  }
  loading.value = true
  try {
    await authStore.forgotPassword(email.value.trim())
    sent.value = true
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Could not send the reset link.'))
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
        <h1 class="mt-3 text-2xl font-bold tracking-tight">Reset Password</h1>
        <p class="text-muted-foreground mt-1 text-sm">
          Enter your email and we'll send you a reset link.
        </p>
      </div>

      <p v-if="sent" class="text-muted-foreground text-center text-sm">
        If an account exists for <strong>{{ email }}</strong
        >, a reset link has been sent.
      </p>
      <form v-else class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="email">Email</Label>
          <Input id="email" v-model="email" type="email" placeholder="you@vibeyworlds.com" />
        </div>
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Sending…' : 'Send Reset Link' }}
        </Button>
      </form>

      <p class="text-muted-foreground mt-6 text-center text-sm">
        <RouterLink to="/login" class="text-primary underline underline-offset-4"
          >Back to Sign In</RouterLink
        >
      </p>
    </CardContent>
  </Card>
</template>
