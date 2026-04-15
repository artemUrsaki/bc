import { defineStore } from 'pinia'
import type { Device, DeviceFilters, Measurement, MeasurementFilters } from '@/types/telemetry'
import {
  fetchDevice as fetchDeviceRequest,
  fetchDevices as fetchDevicesRequest,
  fetchLatestMeasurement as fetchLatestMeasurementRequest,
  fetchMeasurements as fetchMeasurementsRequest,
} from '@/services/telemetry'

interface TelemetryState {
  devices: Device[]
  currentDevice: Device | null
  latestMeasurement: Measurement | null
  measurements: Measurement[]
  loading: boolean
  error: string | null
}

export const useTelemetryStore = defineStore('telemetry', {
  state: (): TelemetryState => ({
    devices: [],
    currentDevice: null,
    latestMeasurement: null,
    measurements: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchDevices(filters: DeviceFilters = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await fetchDevicesRequest(filters)
        this.devices = response.data
        return this.devices
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to load devices.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchDevice(deviceId: number) {
      this.loading = true
      this.error = null

      try {
        const response = await fetchDeviceRequest(deviceId)
        this.currentDevice = response.data
        return this.currentDevice
      } catch (error) {
        this.error = error instanceof Error ? error.message : 'Failed to load device.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchLatestMeasurement(deviceId: number) {
      const response = await fetchLatestMeasurementRequest(deviceId)
      this.latestMeasurement = response.data
      return this.latestMeasurement
    },
    async fetchMeasurements(deviceId: number, filters: MeasurementFilters = {}) {
      const response = await fetchMeasurementsRequest(deviceId, filters)
      this.measurements = response.data
      return this.measurements
    },
    async hydrateDevice(deviceId: number, filters: MeasurementFilters = {}) {
      await Promise.all([
        this.fetchDevice(deviceId),
        this.fetchLatestMeasurement(deviceId),
        this.fetchMeasurements(deviceId, filters),
      ])
    },
    resetCurrent() {
      this.currentDevice = null
      this.latestMeasurement = null
      this.measurements = []
      this.error = null
    },
  },
})
