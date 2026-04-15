<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import ExportButtons from '@/components/protocols/ExportButtons.vue'
import MetricCard from '@/components/protocols/MetricCard.vue'
import RunEventsTimeline from '@/components/protocols/RunEventsTimeline.vue'
import RunSamplesTable from '@/components/protocols/RunSamplesTable.vue'
import RunStatusCard from '@/components/protocols/RunStatusCard.vue'
import { getApiToken, setApiToken } from '@/services/api'
import { useExperimentsStore } from '@/stores/experiments'
import { useRunsStore } from '@/stores/runs'
import type { ProtocolType } from '@/types/run'

const experimentsStore = useExperimentsStore()
const runsStore = useRunsStore()

const authToken = ref(getApiToken() ?? '')
const form = reactive({
  experimentId: null as number | null,
  protocol: 'http' as ProtocolType,
  scenario: '',
  messageCount: 100,
  payloadBytes: 256,
  timeoutMs: 5000,
  delayMs: 0,
})

const scenarioOptions: Record<ProtocolType, { value: string; label: string }[]> = {
  http: [
    { value: '', label: 'Default configuration' },
    { value: 'baseline_latency', label: 'Baseline latency' },
    { value: 'large_payload', label: 'Large payload' },
    { value: 'retry_on_failure', label: 'Retry on failure' },
    { value: 'forced_timeout', label: 'Forced timeout' },
    { value: 'slow_polling', label: 'Slow polling' },
  ],
  mqtt: [
    { value: '', label: 'Default configuration' },
    { value: 'baseline_latency', label: 'Baseline latency' },
    { value: 'reliable_delivery', label: 'Reliable delivery' },
    { value: 'large_payload', label: 'Large payload' },
    { value: 'forced_timeout', label: 'Forced timeout' },
    { value: 'forced_connection_failure', label: 'Connection failure' },
  ],
}

const currentRun = computed(() => runsStore.current)
const aggregate = computed(() => runsStore.aggregate)
const recentRuns = computed(() => runsStore.items.slice(0, 6))
const canExport = computed(() => Boolean(currentRun.value))

const summaryMetrics = computed(() => {
  if (!aggregate.value) {
    return []
  }

  return [
    { label: 'Average latency', value: formatMetric(aggregate.value.avg_latency_ms, ' ms') },
    { label: 'Median latency', value: formatMetric(aggregate.value.median_latency_ms, ' ms') },
    { label: 'P95 latency', value: formatMetric(aggregate.value.p95_latency_ms, ' ms') },
    { label: 'P99 latency', value: formatMetric(aggregate.value.p99_latency_ms, ' ms') },
    { label: 'Throughput', value: formatMetric(aggregate.value.throughput_per_sec, ' req/s') },
    { label: 'Success rate', value: `${aggregate.value.success_rate}%` },
    { label: 'Timeouts', value: aggregate.value.timeout_count },
    { label: 'Duplicates', value: aggregate.value.duplicate_count },
    { label: 'Retries', value: aggregate.value.retry_count },
    { label: 'Reconnects', value: aggregate.value.reconnect_count },
  ]
})

watch(
  () => experimentsStore.items,
  (experiments) => {
    if (!experiments.length) return

    if (form.experimentId === null) {
      form.experimentId = experiments[0].id
      form.protocol = experiments[0].default_protocol
    }
  },
  { immediate: true },
)

watch(
  () => form.experimentId,
  (experimentId) => {
    const experiment = experimentsStore.items.find((item) => item.id === experimentId)

    if (experiment) {
      form.protocol = experiment.default_protocol
      form.scenario = ''
    }
  },
)

onMounted(async () => {
  await Promise.all([experimentsStore.fetchAll(), runsStore.fetchAll()])
})

function formatMetric(value: number | null, suffix = '') {
  if (value === null || Number.isNaN(value)) {
    return 'N/A'
  }

  return `${value}${suffix}`
}

function saveToken() {
  setApiToken(authToken.value.trim() || null)
}

function clearToken() {
  authToken.value = ''
  setApiToken(null)
}

async function refreshRuns() {
  await runsStore.fetchAll()
}

async function submitBenchmark() {
  if (!form.experimentId) {
    return
  }

  const createdRun = await runsStore.create({
    experiment_id: form.experimentId,
    protocol: form.protocol,
    scenario: form.scenario || undefined,
    config: {
      message_count: form.messageCount,
      payload_bytes: form.payloadBytes,
      timeout_ms: form.timeoutMs,
      delay_ms: form.delayMs,
    },
  })

  await runsStore.pollUntilFinished(createdRun.id)
  await runsStore.fetchAll()
}

async function loadRun(runId: number) {
  await runsStore.hydrateRun(runId)
}
</script>

<template>
  <section class="flex flex-col gap-16 py-[100px]">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 text-center">
      <p class="text-sm uppercase tracking-widest text-accent-blue">Benchmark dashboard</p>
      <h1 class="text-4xl sm:text-5xl">Run HTTP and MQTT tests from the backend</h1>
      <p class="text-lg text-gray-400">
        This page now controls the Laravel benchmark engine instead of measuring directly in the
        browser. Keep the design simple, but let the backend do the real work.
      </p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.1fr,0.9fr]">
      <div
        class="rounded-xl border-2 border-accent-blue bg-gradient-to-r from-accent-blue/20 to-accent-purple/20 p-8"
      >
        <div class="flex flex-col gap-8">
          <div>
            <p class="text-xs uppercase tracking-widest text-gray-400">Access token</p>
            <h2 class="mt-3 text-2xl sm:text-3xl">Protected benchmark control</h2>
            <p class="mt-4 text-gray-400">
              The backend protects run creation and exports with Sanctum. Paste a token here when
              you want to create runs from the UI.
            </p>
          </div>

          <div class="flex flex-col gap-4">
            <input
              v-model="authToken"
              type="password"
              placeholder="Paste API token"
              class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
            />
            <div class="flex flex-wrap gap-4">
              <button
                class="rounded-lg bg-accent-blue px-6 py-3 font-semibold transition-all hover:scale-105"
                @click="saveToken"
              >
                Save token
              </button>
              <button
                class="rounded-lg border border-gray-500 px-6 py-3 font-semibold text-gray-400 transition-all hover:scale-105 hover:text-white"
                @click="clearToken"
              >
                Clear token
              </button>
            </div>
          </div>

          <div class="grid gap-6 sm:grid-cols-2">
            <label class="flex flex-col gap-3 text-left">
              <span class="text-sm uppercase tracking-widest text-gray-400">Experiment</span>
              <select
                v-model="form.experimentId"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              >
                <option
                  v-for="experiment in experimentsStore.items"
                  :key="experiment.id"
                  :value="experiment.id"
                >
                  {{ experiment.name }}
                </option>
              </select>
            </label>

            <label class="flex flex-col gap-3 text-left">
              <span class="text-sm uppercase tracking-widest text-gray-400">Protocol</span>
              <select
                v-model="form.protocol"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              >
                <option value="http">HTTP</option>
                <option value="mqtt">MQTT</option>
              </select>
            </label>

            <label class="flex flex-col gap-3 text-left">
              <span class="text-sm uppercase tracking-widest text-gray-400">Scenario</span>
              <select
                v-model="form.scenario"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              >
                <option
                  v-for="scenario in scenarioOptions[form.protocol]"
                  :key="scenario.value || 'default'"
                  :value="scenario.value"
                >
                  {{ scenario.label }}
                </option>
              </select>
            </label>

            <label class="flex flex-col gap-3 text-left">
              <span class="text-sm uppercase tracking-widest text-gray-400">Message count</span>
              <input
                v-model.number="form.messageCount"
                type="number"
                min="1"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              />
            </label>

            <label class="flex flex-col gap-3 text-left">
              <span class="text-sm uppercase tracking-widest text-gray-400">Payload bytes</span>
              <input
                v-model.number="form.payloadBytes"
                type="number"
                min="1"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              />
            </label>

            <label class="flex flex-col gap-3 text-left">
              <span class="text-sm uppercase tracking-widest text-gray-400">Timeout ms</span>
              <input
                v-model.number="form.timeoutMs"
                type="number"
                min="100"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              />
            </label>

            <label class="flex flex-col gap-3 text-left sm:col-span-2">
              <span class="text-sm uppercase tracking-widest text-gray-400">Delay ms</span>
              <input
                v-model.number="form.delayMs"
                type="number"
                min="0"
                class="rounded-lg border border-gray-500 bg-dark-blue px-4 py-4 text-white outline-none"
              />
            </label>
          </div>

          <div class="flex flex-wrap gap-4">
            <button
              class="rounded-lg bg-accent-blue px-8 py-3 font-semibold transition-all hover:scale-105 disabled:opacity-60"
              :disabled="runsStore.loading || runsStore.polling || !form.experimentId"
              @click="submitBenchmark"
            >
              {{ runsStore.polling ? 'Running benchmark...' : 'Start benchmark' }}
            </button>
            <button
              v-if="currentRun"
              class="rounded-lg border border-gray-500 px-8 py-3 font-semibold text-gray-400 transition-all hover:scale-105 hover:text-white"
              @click="runsStore.resetCurrent"
            >
              Clear selection
            </button>
          </div>

          <p v-if="runsStore.error" class="rounded-lg border border-red-400 px-4 py-3 text-red-300">
            {{ runsStore.error }}
          </p>
        </div>
      </div>

      <RunStatusCard
        :status="currentRun?.status ?? null"
        :run-id="currentRun?.id ?? null"
        :protocol="currentRun?.protocol ?? null"
        :error-message="currentRun?.error_message ?? null"
        :started-at="currentRun?.started_at ?? null"
        :finished-at="currentRun?.finished_at ?? null"
      />
    </div>

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-5">
      <MetricCard
        v-for="metric in summaryMetrics"
        :key="metric.label"
        :label="metric.label"
        :value="metric.value"
      />
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
      <div class="rounded-xl border border-gray-500 bg-dark-blue p-8">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-widest text-gray-400">Recent runs</p>
            <h2 class="mt-3 text-2xl sm:text-3xl">History</h2>
          </div>
          <button
            class="rounded-lg border border-gray-500 px-4 py-2 text-sm font-semibold text-gray-400 transition-all hover:scale-105 hover:text-white"
            @click="refreshRuns"
          >
            Refresh
          </button>
        </div>

        <div v-if="recentRuns.length" class="mt-8 flex flex-col gap-4">
          <button
            v-for="run in recentRuns"
            :key="run.id"
            class="flex items-center justify-between rounded-lg border border-gray-500 px-4 py-4 text-left transition-all hover:border-accent-blue"
            @click="loadRun(run.id)"
          >
            <div>
              <p class="font-semibold text-white">Run #{{ run.id }}</p>
              <p class="text-sm text-gray-400">
                {{ run.protocol.toUpperCase() }} · {{ run.status }} · experiment
                {{ run.experiment_id }}
              </p>
            </div>
            <span class="text-sm text-accent-blue">Open</span>
          </button>
        </div>
        <p v-else class="mt-8 text-gray-400">No runs available yet.</p>
      </div>

      <ExportButtons v-if="canExport" />
      <div v-else class="rounded-xl border border-gray-500 bg-dark-blue p-8 text-gray-400">
        <p class="text-xs uppercase tracking-widest">Export</p>
        <h2 class="mt-3 text-2xl sm:text-3xl text-white">Download run data</h2>
        <p class="mt-4">Load a run first to enable JSON and CSV export.</p>
      </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.15fr,0.85fr]">
      <RunSamplesTable :samples="runsStore.samples" />
      <RunEventsTimeline :events="runsStore.events" />
    </div>
  </section>
</template>
