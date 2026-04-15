import { defineStore } from 'pinia'
import type { CreateRunPayload, Run, RunAggregate, RunEvent, RunListFilters, RunSample } from '@/types/run'
import {
  createRun as createRunRequest,
  fetchRun as fetchRunRequest,
  fetchRunAggregate as fetchRunAggregateRequest,
  fetchRunEvents as fetchRunEventsRequest,
  fetchRuns as fetchRunsRequest,
  fetchRunSamples as fetchRunSamplesRequest,
  getRunExportUrls,
} from '@/services/runs'

interface RunsState {
  items: Run[]
  current: Run | null
  aggregate: RunAggregate | null
  samples: RunSample[]
  events: RunEvent[]
  loading: boolean
  polling: boolean
  error: string | null
}

export const useRunsStore = defineStore('runs', {
  state: (): RunsState => ({
    items: [],
    current: null,
    aggregate: null,
    samples: [],
    events: [],
    loading: false,
    polling: false,
    error: null,
  }),
  getters: {
    exportUrls(state) {
      return state.current ? getRunExportUrls(state.current.id) : null
    },
    isTerminal(state): boolean {
      return ['completed', 'failed', 'cancelled'].includes(state.current?.status ?? '')
    },
  },
  actions: {
    async fetchAll(filters: RunListFilters = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await fetchRunsRequest(filters)
        this.items = response.data
        return this.items
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to load runs.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchOne(runId: number) {
      this.loading = true
      this.error = null

      try {
        const response = await fetchRunRequest(runId)
        this.current = response.data
        this.aggregate = response.data.aggregate ?? null
        this.events = response.data.events ?? []
        return this.current
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to load run.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async create(payload: CreateRunPayload) {
      this.loading = true
      this.error = null

      try {
        const response = await createRunRequest(payload)
        this.current = response.data
        this.aggregate = response.data.aggregate ?? null
        this.events = response.data.events ?? []
        this.samples = []
        this.items.unshift(response.data)
        return response.data
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to create run.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchAggregate(runId: number) {
      const response = await fetchRunAggregateRequest(runId)
      this.aggregate = response.data
      return this.aggregate
    },
    async fetchSamples(runId: number, success?: boolean) {
      const response = await fetchRunSamplesRequest(runId, success)
      this.samples = response.data
      return this.samples
    },
    async fetchEvents(runId: number) {
      const response = await fetchRunEventsRequest(runId)
      this.events = response.data
      return this.events
    },
    async hydrateRun(runId: number) {
      await this.fetchOne(runId)

      const tasks: Promise<unknown>[] = [this.fetchEvents(runId), this.fetchSamples(runId)]

      if (this.current?.status === 'completed') {
        tasks.push(this.fetchAggregate(runId))
      }

      await Promise.all(tasks)
    },
    async pollUntilFinished(runId: number, intervalMs = 1500) {
      this.polling = true

      try {
        while (true) {
          const run = await this.fetchOne(runId)

          if (['completed', 'failed', 'cancelled'].includes(run.status)) {
            break
          }

          await new Promise((resolve) => setTimeout(resolve, intervalMs))
        }

        await this.hydrateRun(runId)
      } finally {
        this.polling = false
      }
    },
    resetCurrent() {
      this.current = null
      this.aggregate = null
      this.samples = []
      this.events = []
      this.error = null
      this.polling = false
    },
  },
})
