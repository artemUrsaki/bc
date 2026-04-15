import type { ApiCollectionResponse, ApiResourceResponse } from '@/types/api'
import type { Device, DeviceFilters, Measurement, MeasurementFilters } from '@/types/telemetry'
import { api } from './api'

export async function fetchDevices(filters: DeviceFilters = {}) {
  const { data } = await api.get<ApiCollectionResponse<Device>>('/devices', {
    params: {
      active: typeof filters.active === 'boolean' ? Number(filters.active) : undefined,
    },
  })

  return data
}

export async function fetchDevice(deviceId: number) {
  const { data } = await api.get<ApiResourceResponse<Device>>(`/devices/${deviceId}`)
  return data
}

export async function fetchLatestMeasurement(deviceId: number) {
  const { data } = await api.get<ApiResourceResponse<Measurement>>(`/devices/${deviceId}/latest`)
  return data
}

export async function fetchMeasurements(deviceId: number, filters: MeasurementFilters = {}) {
  const { data } = await api.get<ApiCollectionResponse<Measurement>>(`/devices/${deviceId}/measurements`, {
    params: filters,
  })

  return data
}
