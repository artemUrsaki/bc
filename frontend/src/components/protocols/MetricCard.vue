<script setup lang="ts">
defineProps<{
  label: string
  value: string | number
  helper?: string
}>()
</script>

<template>
  <div
    class="rounded-xl border border-gray-500 bg-gradient-to-b from-lighter-blue/40 to-dark-blue p-6 shadow-2xl"
  >
    <p class="text-xs uppercase tracking-widest text-gray-400">{{ label }}</p>
    <p class="mt-4 text-2xl font-semibold text-white">{{ value }}</p>
    <p v-if="helper" class="mt-2 text-sm text-gray-400">{{ helper }}</p>
  </div>
</template>
