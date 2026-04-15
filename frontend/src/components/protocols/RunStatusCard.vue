<script setup lang="ts">
import type { RunStatus } from '@/types/run'

const props = defineProps<{
  status: RunStatus | null
  runId?: number | null
  protocol?: 'http' | 'mqtt' | null
  errorMessage?: string | null
  startedAt?: string | null
  finishedAt?: string | null
}>()

const statusClasses: Record<string, string> = {
  queued: 'border-gray-500 text-gray-400',
  running: 'border-accent-blue text-accent-blue',
  completed: 'border-green-400 text-green-300',
  failed: 'border-red-400 text-red-300',
  cancelled: 'border-yellow-400 text-yellow-300',
}

function formatDate(value?: string | null) {
  if (!value) return 'Not available'

  return new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
    timeStyle: 'medium',
  }).format(new Date(value))
}

const badgeClass = props.status ? statusClasses[props.status] ?? statusClasses.queued : statusClasses.queued
</script>

<template>
  <div
    class="rounded-xl border-2 border-accent-blue bg-gradient-to-r from-accent-blue/15 to-accent-purple/15 p-8 text-left"
  >
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs uppercase tracking-widest text-gray-400">Run status</p>
        <h3 class="mt-3 text-2xl font-semibold text-white">
          {{ status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Idle' }}
        </h3>
      </div>
      <span
        class="inline-flex w-fit rounded-lg border px-4 py-2 text-sm font-semibold uppercase"
        :class="badgeClass"
      >
        {{ protocol ?? 'benchmark' }}
      </span>
    </div>

    <dl class="mt-8 grid gap-4 text-sm text-gray-400 sm:grid-cols-2">
      <div>
        <dt class="uppercase tracking-widest">Run ID</dt>
        <dd class="mt-2 text-base text-white">{{ runId ?? 'No run selected' }}</dd>
      </div>
      <div>
        <dt class="uppercase tracking-widest">Started</dt>
        <dd class="mt-2 text-base text-white">{{ formatDate(startedAt) }}</dd>
      </div>
      <div>
        <dt class="uppercase tracking-widest">Finished</dt>
        <dd class="mt-2 text-base text-white">{{ formatDate(finishedAt) }}</dd>
      </div>
      <div>
        <dt class="uppercase tracking-widest">Error</dt>
        <dd class="mt-2 text-base text-white">{{ errorMessage || 'None' }}</dd>
      </div>
    </dl>
  </div>
</template>
