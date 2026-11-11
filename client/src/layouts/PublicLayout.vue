<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import SiteFooter from '@/features/landing/components/SiteFooter.vue'

const route = useRoute()
const router = useRouter()

const navLinks = [
  { label: 'Home', to: '/#home', id: 'home' },
  { label: 'Services', to: '/#services', id: 'services' },
  { label: 'Portfolio', to: '/#portfolio', id: 'portfolio' },
  { label: 'FAQ', to: '/#faq', id: 'faq' },
]

const activeSection = ref('home')
let observer: IntersectionObserver | undefined

function setupObserver() {
  observer?.disconnect()
  const sections = navLinks
    .map((link) => document.getElementById(link.id))
    .filter((el): el is HTMLElement => el !== null)
  if (sections.length === 0) return

  observer = new IntersectionObserver(
    (entries) => {
      const mostVisible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0]
      if (mostVisible) activeSection.value = mostVisible.target.id
    },
    { rootMargin: '-96px 0px -55% 0px', threshold: [0, 0.25, 0.5, 0.75, 1] },
  )
  sections.forEach((section) => observer?.observe(section))
}

onMounted(setupObserver)
watch(
  () => route.path,
  () => nextTick(setupObserver),
)
onUnmounted(() => observer?.disconnect())
</script>

<template>
  <div class="flex min-h-svh flex-col">
    <header class="bg-background/80 border-border sticky top-0 z-40 border-b backdrop-blur">
      <nav class="mx-auto grid max-w-6xl grid-cols-2 items-center px-4 py-3 sm:px-6 md:grid-cols-3">
        <a
          href="https://instagram.com/heb_el1"
          target="_blank"
          rel="noopener noreferrer"
          class="-ml-1 flex items-center gap-1.5 justify-self-start sm:-ml-2"
        >
          <img
            src="/img/Vibeylogo.jpg"
            alt="Vibey World"
            class="h-14 w-14 rounded-full object-cover sm:h-16 sm:w-16"
          />
          <span class="hidden flex-col leading-tight sm:flex">
            <span class="text-foreground text-lg font-bold">Vibey World</span>
            <span class="text-muted-foreground text-xs">Modernizing Lives</span>
          </span>
        </a>
        <div class="hidden items-center gap-1 justify-self-center md:flex">
          <a
            v-for="link in navLinks"
            :key="link.label"
            :href="link.to"
            class="rounded-full px-5 py-2.5 text-base font-medium transition-all duration-200 hover:scale-105"
            :class="
              activeSection === link.id
                ? 'bg-accent text-foreground'
                : 'text-foreground hover:bg-accent'
            "
          >
            {{ link.label }}
          </a>
        </div>
        <div class="flex items-center gap-2 justify-self-end">
          <Button size="sm" class="rounded-full" @click="router.push('/request')"
            >Request a Quote</Button
          >
          <Button
            size="sm"
            variant="outline"
            class="hidden rounded-full sm:inline-flex"
            @click="router.push('/contact')"
          >
            Book a Meeting
          </Button>
        </div>
      </nav>
    </header>
    <main class="flex-1">
      <RouterView />
    </main>
    <SiteFooter />
  </div>
</template>
