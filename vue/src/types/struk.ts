export interface StrukItem {
  key: string
  tahun: string
  kassa: string
  nomor: string
  label: string
  datetime: string
}

/* ========= Payload ========= */

export interface CariNomorPayload {
  tahun: string
  kassa: string
  nomor: string
}

export interface CariTanggalPayload {
  tanggal: string
  kassa: string
}

export interface CariKeywordPayload {
  keyword: string
  tanggal?: string
  kassa?: string
}

export interface ApiErrorResponse {
  message: string
}
