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

const email = ref('')
const password = ref('')
const loading = ref(false)

async function submit() {
  if (!email.value.trim() || !password.value.trim()) {
    toast.error('Enter your email and password to sign in.')
    return
  }
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push((route.query.redirect as string) || '/dashboard')
  } catch (err) {
    toast.error(apiErrorMessage(err, 'Invalid email or password.'))
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
        <h1 class="mt-3 text-2xl font-bold tracking-tight">Team Sign In</h1>
        <p class="text-muted-foreground mt-1 text-sm">For Vibey World team members.</p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="email">Email</Label>
          <Input id="email" v-model="email" type="email" placeholder="you@vibeyworlds.com" />
        </div>
        <div class="space-y-1.5">
          <Label for="password">Password</Label>
          <PasswordInput id="password" v-model="password" placeholder="••••••••" />
        </div>
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign In' }}
        </Button>
      </form>

      <p class="mt-4 text-center text-sm">
        <RouterLink to="/forgot-password" class="text-primary underline underline-offset-4"
          >Forgot your password?</RouterLink
        >
      </p>

      <p class="text-muted-foreground mt-6 text-center text-sm">
        Are you a client?
        <RouterLink to="/client" class="text-primary underline underline-offset-4"
          >Client portal login</RouterLink
        >
      </p>
    </CardContent>
  </Card>
</template>
