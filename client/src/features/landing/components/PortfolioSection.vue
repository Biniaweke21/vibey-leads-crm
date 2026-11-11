<script setup lang="ts">
import { projects, type ProjectStatus } from '@/features/landing/data/portfolio'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import RevealOnScroll from '@/components/base/RevealOnScroll.vue'

const statusLabel: Record<ProjectStatus, string> = {
  'in-development': 'In Development',
  delivered: 'Delivered',
  live: 'Live',
}

const statusClass: Record<ProjectStatus, string> = {
  'in-development': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  delivered: 'bg-sky-500/10 text-sky-600 border-sky-500/20',
  live: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
}
</script>

<template>
  <section id="portfolio" class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-16">
    <RevealOnScroll class="mx-auto mb-12 max-w-2xl text-center">
      <h2 class="text-3xl font-bold tracking-tight">Our work</h2>
      <p class="text-muted-foreground mt-3">Products we've designed, built, and shipped.</p>
    </RevealOnScroll>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <RevealOnScroll
        v-for="(project, i) in projects"
        :key="project.name"
        :delay="(i % 4) * 100"
        class="h-full"
      >
        <Card
          class="border-border/60 hover:border-primary/30 h-full overflow-hidden rounded-2xl py-0 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
        >
          <div class="bg-muted relative aspect-video">
            <video
              v-if="project.video"
              :src="project.video"
              class="size-full object-contain"
              autoplay
              muted
              loop
              playsinline
            />
            <img
              v-else-if="project.image"
              :src="project.image"
              :alt="project.name"
              class="size-full object-contain"
            />
            <div
              v-else
              class="from-primary/15 to-primary/5 flex size-full items-center justify-center bg-gradient-to-br"
            >
              <component :is="project.icon" class="text-primary size-10" />
            </div>
            <Badge :class="statusClass[project.status]" class="absolute top-3 right-3">
              {{ statusLabel[project.status] }}
            </Badge>
          </div>
          <CardContent class="flex flex-col pb-6">
            <p class="line-clamp-1 font-semibold">{{ project.name }}</p>
            <p class="text-muted-foreground mt-1 line-clamp-2 h-10 text-sm">
              {{ project.description }}
            </p>
            <Badge variant="secondary" class="mt-3 self-start">{{ project.category }}</Badge>
          </CardContent>
        </Card>
      </RevealOnScroll>
    </div>
  </section>
</template>
