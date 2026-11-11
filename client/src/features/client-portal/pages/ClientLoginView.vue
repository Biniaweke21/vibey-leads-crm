<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import PasswordInput from '@/components/base/PasswordInput.vue'
import { useClientPortalAuthStore } from '@/features/client-portal/store/clientPortalAuth'
import { apiErrorMessage } from '@/services/apiError'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useClientPortalAuthStore()

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
    router.push((route.query.redirect as string) || '/client')
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
        <h1 class="mt-3 text-2xl font-bold tracking-tight">Client Portal</h1>
        <p class="text-muted-foreground mt-1 text-sm">
          Sign in to view your projects, documents, and meetings.
        </p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-1.5">
          <Label for="email">Email</Label>
          <Input id="email" v-model="email" type="email" placeholder="you@yourcompany.com" />
        </div>
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="password">Password</Label>
            <RouterLink
              to="/forgot-password"
              class="text-primary text-xs underline underline-offset-4"
              >Forgot password?</RouterLink
            >
          </div>
          <PasswordInput id="password" v-model="password" placeholder="••••••••" />
        </div>
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign In' }}
        </Button>
      </form>

      <p class="text-muted-foreground mt-6 text-center text-sm">
        Vibey World team member?
        <RouterLink to="/dashboard" class="text-primary underline underline-offset-4"
          >Team login</RouterLink
        >
      </p>
    </CardContent>
  </Card>
</template>
