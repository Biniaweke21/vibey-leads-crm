<script setup lang="ts">
import HeroSection from '@/features/landing/components/HeroSection.vue'
import ServicesSection from '@/features/landing/components/ServicesSection.vue'
import PortfolioSection from '@/features/landing/components/PortfolioSection.vue'
import FaqSection from '@/features/landing/components/FaqSection.vue'
</script>

<template>
  <HeroSection />
  <ServicesSection class="bg-muted/30" />
  <PortfolioSection />
  <FaqSection class="bg-muted/30" />
</template>
