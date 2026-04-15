import type { ProtocolType } from './run'

export interface Device {
  id: number
  name: string
  slug: string
  type: string
  location: string | null
  is_active: boolean
  description?: string | null
  created_at?: string
  updated_at?: string
}

export interface Measurement {
  id: number
  device_id: number
  protocol: ProtocolType
  value: number
  unit: string | null
  source: string | null
  recorded_at: string
  created_at?: string
  updated_at?: string
}

export interface DeviceFilters {
  active?: boolean
}

export interface MeasurementFilters {
  protocol?: ProtocolType
  limit?: number
}
