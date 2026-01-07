import { apiFetch } from '@/utils/api'
import type {
  StrukItem,
  CariNomorPayload,
  CariTanggalPayload,
  CariKeywordPayload,
} from '@/types/struk'

// =====================
// POST JSON helper
// =====================
function post<T>(url: string, body: unknown): Promise<T> {
  return apiFetch<T>(url, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

// =====================
// SERVICES
// =====================
export function cariByNomor(
  payload: CariNomorPayload,
): Promise<StrukItem[]> {
  return post('/struk/by-nomor', payload)
}

export function cariByTanggal(
  payload: CariTanggalPayload,
): Promise<StrukItem[]> {
  return post('/struk/by-tanggal', payload)
}

export function cariByKeyword(
  payload: CariKeywordPayload,
): Promise<StrukItem[]> {
  return post('/struk/by-keyword', payload)
}

export function getStrukContent(payload: {
  tahun: string
  key: string
}): Promise<{ content: string }> {
  return post('/struk/content', payload)
}

// =====================
// GET TAHUN STRUK
// =====================
export function getTahunStruk(): Promise<string[]> {
  return apiFetch<string[]>('/struk/tahun')
}

// =====================
// STREAM (TEXT RESPONSE)
// =====================
export async function getStrukStream(payload: {
  tahun: string
  key: string
}): Promise<string> {

  const base =
    import.meta.env.VITE_API_BASE && import.meta.env.VITE_API_BASE !== ''
      ? import.meta.env.VITE_API_BASE
      : '/estruk/api'

  const res = await fetch(`${base}/struk/content-stream`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  })

  if (!res.ok) {
    throw new Error('Gagal memuat struk')
  }

  return await res.text()
}



