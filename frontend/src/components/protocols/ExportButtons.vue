<script setup lang="ts">
import { ref } from 'vue'
import CustomButton from '@/components/CustomButton.vue'
import { useRunsStore } from '@/stores/runs'

const runsStore = useRunsStore()
const exporting = ref<'json' | 'csv' | null>(null)
const error = ref<string | null>(null)

async function exportRun(format: 'json' | 'csv') {
  exporting.value = format
  error.value = null

  try {
    await runsStore.exportCurrent(format)
  } catch (caughtError) {
    error.value = caughtError instanceof Error ? caughtError.message : 'Failed to export run.'
  } finally {
    exporting.value = null
  }
}
</script>

<template>
  <div class="rounded-xl border border-gray-500 bg-dark-blue p-8">
    <p class="text-xs uppercase tracking-widest text-gray-400">Export</p>
    <h3 class="mt-3 text-2xl font-semibold text-white">Download run data</h3>
    <p class="mt-3 text-gray-400">
      Export the selected run as JSON for structured analysis or CSV for spreadsheet work.
    </p>

    <div class="mt-6 flex flex-wrap gap-4">
      <CustomButton @click="exportRun('json')">
        {{ exporting === 'json' ? 'Exporting JSON...' : 'Export JSON' }}
      </CustomButton>
      <CustomButton @click="exportRun('csv')">
        {{ exporting === 'csv' ? 'Exporting CSV...' : 'Export CSV' }}
      </CustomButton>
    </div>

    <p v-if="error" class="mt-4 text-sm text-red-300">{{ error }}</p>
  </div>
</template>
