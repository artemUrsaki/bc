<script setup lang="ts">
import type { RunEvent } from '@/types/run'

defineProps<{
  events: RunEvent[]
}>()

const toneClasses: Record<string, string> = {
  info: 'border-accent-blue text-accent-blue',
  warning: 'border-yellow-400 text-yellow-300',
  error: 'border-red-400 text-red-300',
}

function toneClass(level: string) {
  return toneClasses[level] ?? toneClasses.info
}

function formatTimestamp(value: string) {
  return new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(new Date(value))
}
</script>

<template>
  <div class="rounded-xl border border-gray-500 bg-dark-blue p-8">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-widest text-gray-400">Events</p>
        <h3 class="mt-3 text-2xl font-semibold text-white">Execution timeline</h3>
      </div>
      <span class="rounded-lg border border-gray-500 px-4 py-2 text-sm text-gray-400">
        {{ events.length }} events
      </span>
    </div>

    <div v-if="events.length" class="mt-8 flex flex-col gap-4">
      <div
        v-for="event in [...events].reverse()"
        :key="event.id"
        class="rounded-lg border border-gray-500 px-4 py-4"
      >
        <div class="flex items-center justify-between gap-4">
          <p class="font-semibold text-white">{{ event.type }}</p>
          <span
            class="rounded-lg border px-3 py-1 text-xs uppercase tracking-widest"
            :class="toneClass(event.level)"
          >
            {{ event.level }}
          </span>
        </div>
        <p class="mt-2 text-sm text-gray-400">{{ event.message }}</p>
        <p class="mt-2 text-xs text-gray-400">{{ formatTimestamp(event.occurred_at) }}</p>
      </div>
    </div>
    <p v-else class="mt-8 text-gray-400">Start or load a run to inspect lifecycle and protocol events.</p>
  </div>
</template>
