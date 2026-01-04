import type {
  StrukItem,
  CariNomorPayload,
  CariTanggalPayload,
  CariKeywordPayload,
  ApiErrorResponse,
} from '../types/struk'

async function post<T>(url: string, body: unknown): Promise<T> {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })

  let data: unknown = null
  try {
    data = await res.json()
  } catch {}

  if (!res.ok) {
    const err = data as ApiErrorResponse | null
    throw new Error(err?.message ?? 'Request gagal')
  }

  return data as T
}

export function cariByNomor(
  payload: CariNomorPayload,
): Promise<StrukItem[]> {
  return post('/api/struk/by-nomor', payload)
}

export function cariByTanggal(
  payload: CariTanggalPayload,
): Promise<StrukItem[]> {
  return post('/api/struk/by-tanggal', payload)
}

export function cariByKeyword(
  payload: CariKeywordPayload,
): Promise<StrukItem[]> {
  return post('/api/struk/by-keyword', payload)
}

export function getStrukContent(payload: {
  tahun: string
  key: string
}): Promise<{ content: string }> {
  return post('/api/struk/content', payload)
}

export async function getStrukStream(payload: {
  tahun: string
  key: string
}): Promise<string> {
  const res = await fetch('/api/struk/content-stream', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })

  if (!res.ok) {
    throw new Error('Gagal memuat struk')
  }

  return await res.text()
}
