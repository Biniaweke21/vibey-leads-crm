<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import RevealOnScroll from '@/components/base/RevealOnScroll.vue'

const router = useRouter()
</script>

<template>
  <section
    id="home"
    class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-8 pb-16 sm:px-6 sm:pt-10 md:grid-cols-2 md:gap-16 md:pt-12 md:pb-20"
  >
    <RevealOnScroll class="flex flex-col items-start gap-6 text-left">
      <p class="text-primary text-sm font-semibold tracking-wide uppercase">
        Inspire · Innovate · Impact
      </p>
      <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
        We build the software behind your next big idea
      </h1>
      <p class="text-muted-foreground max-w-xl text-lg">
        Vibey World designs and builds websites, apps, ERP systems, and AI-powered products for
        businesses that want to move fast without cutting corners.
      </p>
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <Button size="lg" @click="router.push('/request')">Request a Quote</Button>
        <!-- ponytail: points at /contact until a real scheduler link (Calendly/Cal.com) is wired up -->
        <Button size="lg" variant="outline" @click="router.push('/contact')">Book a Meeting</Button>
      </div>
    </RevealOnScroll>
    <RevealOnScroll :delay="150" class="flex justify-center md:justify-end">
      <img src="/img/hero.jpg" alt="" class="animate-float w-full max-w-lg" />
    </RevealOnScroll>
  </section>
</template>
