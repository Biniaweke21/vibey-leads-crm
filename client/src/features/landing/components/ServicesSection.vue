<script setup lang="ts">
import { services } from '@/features/landing/data/services'
import FeatureCard from '@/components/base/FeatureCard.vue'
import RevealOnScroll from '@/components/base/RevealOnScroll.vue'
</script>

<template>
  <section id="services" class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-16">
    <RevealOnScroll class="mx-auto mb-12 max-w-2xl text-center">
      <h2 class="text-3xl font-bold tracking-tight">What we do</h2>
      <p class="text-muted-foreground mt-3">
        End-to-end product and engineering services under one roof.
      </p>
    </RevealOnScroll>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <RevealOnScroll
        v-for="(service, i) in services"
        :key="service.title"
        :delay="(i % 3) * 100"
        class="h-full"
      >
        <FeatureCard
          :title="service.title"
          :description="service.description"
          :icon="service.icon"
        />
      </RevealOnScroll>
    </div>
  </section>
</template>
