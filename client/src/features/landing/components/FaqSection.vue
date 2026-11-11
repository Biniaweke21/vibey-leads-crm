<script setup lang="ts">
import { faqs } from '@/features/landing/data/faq'
import {
  Accordion,
  AccordionItem,
  AccordionTrigger,
  AccordionContent,
} from '@/components/ui/accordion'
import RevealOnScroll from '@/components/base/RevealOnScroll.vue'
</script>

<template>
  <section id="faq" class="mx-auto max-w-3xl px-4 py-14 sm:px-6 sm:py-16">
    <RevealOnScroll class="mx-auto mb-12 max-w-2xl text-center">
      <h2 class="text-3xl font-bold tracking-tight">Frequently asked questions</h2>
    </RevealOnScroll>
    <RevealOnScroll :delay="100">
      <Accordion type="single" collapsible>
        <AccordionItem v-for="(item, i) in faqs" :key="i" :value="`item-${i}`">
          <AccordionTrigger>{{ item.question }}</AccordionTrigger>
          <AccordionContent>{{ item.answer }}</AccordionContent>
        </AccordionItem>
      </Accordion>
    </RevealOnScroll>
  </section>
</template>
