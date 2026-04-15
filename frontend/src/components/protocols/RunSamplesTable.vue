<script setup lang="ts">
import type { RunSample } from '@/types/run'

defineProps<{
  samples: RunSample[]
}>()

function formatTimestamp(value: string | null) {
  if (!value) return 'N/A'

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
        <p class="text-xs uppercase tracking-widest text-gray-400">Samples</p>
        <h3 class="mt-3 text-2xl font-semibold text-white">Raw run data</h3>
      </div>
      <span class="rounded-lg border border-gray-500 px-4 py-2 text-sm text-gray-400">
        {{ samples.length }} records
      </span>
    </div>

    <div v-if="samples.length" class="mt-8 overflow-x-auto">
      <table class="w-full min-w-[900px] text-left text-sm">
        <thead class="border-y border-gray-500 text-gray-400">
          <tr>
            <th class="px-3 py-4">#</th>
            <th class="px-3 py-4">Latency</th>
            <th class="px-3 py-4">Result</th>
            <th class="px-3 py-4">Status / Error</th>
            <th class="px-3 py-4">Sent</th>
            <th class="px-3 py-4">Received</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="sample in samples"
            :key="sample.id"
            class="border-b border-gray-500 text-gray-400"
          >
            <td class="px-3 py-4 text-white">{{ sample.sequence_no }}</td>
            <td class="px-3 py-4 text-white">
              {{ sample.latency_ms !== null ? `${sample.latency_ms} ms` : 'N/A' }}
            </td>
            <td class="px-3 py-4">
              <span :class="sample.success ? 'text-green-300' : 'text-red-300'">
                {{ sample.success ? 'Success' : 'Failure' }}
              </span>
            </td>
            <td class="px-3 py-4 text-white">
              {{ sample.status_code ?? sample.error_code ?? 'N/A' }}
            </td>
            <td class="px-3 py-4">{{ formatTimestamp(sample.sent_at) }}</td>
            <td class="px-3 py-4">{{ formatTimestamp(sample.received_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="mt-8 text-gray-400">Run samples will appear here after a run is loaded.</p>
  </div>
</template>
