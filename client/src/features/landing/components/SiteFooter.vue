<script setup lang="ts">
import { Mail, Camera, X, ThumbsUp, Music2 } from '@lucide/vue'

const year = new Date().getFullYear()

const navLinks = [
  { label: 'Home', href: '/#home' },
  { label: 'Services', href: '/#services' },
  { label: 'Portfolio', href: '/#portfolio' },
  { label: 'FAQ', href: '/#faq' },
]

const socials = [
  {
    label: 'Email',
    handle: 'info@vibeyworlds.com',
    href: 'mailto:info@vibeyworlds.com',
    icon: Mail,
  },
  {
    label: 'Instagram',
    handle: '@vibey_worlds',
    href: 'https://instagram.com/vibey_worlds',
    icon: Camera,
  },
  { label: 'X (Twitter)', handle: '@VibeyWorld', href: 'https://x.com/VibeyWorld', icon: X },
  {
    label: 'Facebook',
    handle: 'Vibey World',
    href: 'https://facebook.com/VibeyWorld',
    icon: ThumbsUp,
  },
  {
    label: 'TikTok',
    handle: '@vibey.world5',
    href: 'https://tiktok.com/@vibey.world5',
    icon: Music2,
  },
]
</script>

<template>
  <footer class="bg-foreground text-background">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
      <div class="grid gap-8 md:grid-cols-3">
        <div>
          <p class="text-lg font-bold">Vibey World</p>
          <p class="text-background/70 text-xs">Modernizing Lives</p>
          <p class="text-background/70 mt-3 max-w-xs text-sm">
            Ethiopian AI and technology company building AI systems, business automation platforms,
            digital infrastructure, retail technology, and software solutions.
          </p>
          <p class="text-background/60 mt-3 text-xs">
            Based in Addis Ababa, Ethiopia · Building for Africa, Engineered for the World
          </p>
        </div>
        <div>
          <h3 class="mb-3 text-sm font-semibold">Navigate</h3>
          <ul class="text-background/80 space-y-2 text-sm">
            <li v-for="link in navLinks" :key="link.label">
              <a :href="link.href" class="hover:text-background transition-colors">{{
                link.label
              }}</a>
            </li>
          </ul>
        </div>
        <div>
          <h3 class="mb-3 text-sm font-semibold">Get in Touch</h3>
          <ul class="text-background/80 space-y-2 text-sm">
            <li>
              <RouterLink to="/request" class="hover:text-background transition-colors"
                >Request a Quote</RouterLink
              >
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-background transition-colors"
                >Book a Meeting</RouterLink
              >
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-background transition-colors"
                >Contact Us</RouterLink
              >
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-10 text-center">
        <h3 class="text-sm font-semibold tracking-wide uppercase">Connect With Us</h3>
        <p class="text-background/70 mt-1 text-xs">
          Join the movement building Africa's technological future
        </p>
        <div class="mt-5 flex flex-wrap justify-center gap-3">
          <a
            v-for="social in socials"
            :key="social.label"
            :href="social.href"
            target="_blank"
            rel="noopener noreferrer"
            class="border-background/20 hover:bg-background/10 flex items-center gap-2.5 rounded-lg border px-4 py-2 transition-colors"
          >
            <component :is="social.icon" class="size-4 shrink-0" />
            <span class="text-left">
              <span class="block text-xs font-semibold">{{ social.label }}</span>
              <span class="text-background/60 block text-[11px]">{{ social.handle }}</span>
            </span>
          </a>
        </div>
      </div>

      <div
        class="border-background/20 text-background/60 mt-10 flex flex-col items-center justify-between gap-3 border-t pt-5 text-xs sm:flex-row"
      >
        <p>
          ©
          <a
            href="https://instagram.com/heb_el1"
            target="_blank"
            rel="noopener noreferrer"
            class="hover:text-background transition-colors"
            >{{ year }}</a
          >
          Vibey World. All rights reserved.
        </p>
        <div class="flex gap-6">
          <a href="/#faq" class="hover:text-background transition-colors">FAQ</a>
          <RouterLink to="/contact" class="hover:text-background transition-colors"
            >Contact</RouterLink
          >
        </div>
      </div>
    </div>
  </footer>
</template>
