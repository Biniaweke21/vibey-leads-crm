<script setup lang="ts"></script>

<template>
  <div class="relative flex min-h-svh items-center justify-center overflow-hidden p-4">
    <!-- ambient brand gradient -->
    <div
      class="from-primary/15 via-background to-background absolute inset-0 -z-10 bg-gradient-to-br"
    />
    <div class="bg-primary/20 absolute -top-24 -left-24 -z-10 size-72 rounded-full blur-3xl" />
    <div
      class="absolute -right-24 -bottom-24 -z-10 size-72 rounded-full bg-[#FACC15]/20 blur-3xl"
    />
    <RouterView />
  </div>
</template>
