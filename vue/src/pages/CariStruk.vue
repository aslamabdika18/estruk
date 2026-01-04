<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { cariByNomor } from '../services/struk.service'
import type { StrukItem } from '../types/struk'
import BackButton from '@/components/BackButton.vue'

const tahunList = ref<string[]>([])
const tahun = ref('')

// ⬇️ STRING, BUKAN NUMBER
const kassaInput = ref('')
const nomorInput = ref('')

const hasil = ref<StrukItem[]>([])
const error = ref('')
const loading = ref(false)

/* ======================
   FORMATTER
====================== */

// 01 – 80
const kassa = computed(() => {
  if (!kassaInput.value) return ''
  return kassaInput.value.padStart(2, '0')
})

// 000001 dst
const nomor = computed(() => {
  if (!nomorInput.value) return ''
  return nomorInput.value.padStart(6, '0')
})

// 25
const tahunShort = computed(() => {
  if (!tahun.value) return ''
  return tahun.value.slice(-2)
})

onMounted(loadTahun)

watch(tahun, () => {
  hasil.value = []
  error.value = ''
})

/* ======================
   LOAD TAHUN
====================== */
async function loadTahun(): Promise<void> {
  try {
    const res = await fetch('/api/struk/tahun')
    const data = (await res.json()) as string[]
    tahunList.value = data
    tahun.value = data[0] ?? new Date().getFullYear().toString()
  } catch {
    error.value = 'Gagal memuat daftar tahun'
  }
}

/* ======================
   CARI STRUK
====================== */
async function cari(): Promise<void> {
  error.value = ''
  hasil.value = []
  loading.value = true

  // VALIDASI FRONTEND
  if (!/^\d{1,2}$/.test(kassaInput.value)) {
    error.value = 'Kassa harus 1–2 digit angka'
    loading.value = false
    return
  }

  if (!/^\d{1,6}$/.test(nomorInput.value)) {
    error.value = 'Nomor struk maksimal 6 digit angka'
    loading.value = false
    return
  }

  try {
    hasil.value = await cariByNomor({
      tahun: tahun.value,
      kassa: kassa.value,   // 01
      nomor: nomor.value,   // 000006
    })
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

/* ======================
   OPEN STRUK
====================== */
function openStruk(row: StrukItem): void {
  const key = `${row.kassa}.${row.nomor}`
  window.open(
    `/preview/${row.tahun}/${key}`,
    '_blank',
    'width=900,height=600',
  )
}
</script>

<template>
  <div class="w-full max-w-md bg-white rounded-lg shadow p-6">
    <BackButton />

    <h1 class="text-xl font-bold text-center mb-4">
      Cari E-Struk (Nomor)
    </h1>

    <form @submit.prevent="cari" class="space-y-4">
      <!-- Tahun -->
      <select v-model="tahun" class="w-full border rounded px-2 py-1">
        <option v-for="t in tahunList" :key="t" :value="t">
          {{ t }}
        </option>
      </select>

      <!-- Nomor Struk -->
      <div class="flex gap-2 items-center">
        <span class="whitespace-nowrap">
          2031.SA.{{ tahunShort }}.
        </span>

        <!-- KASSA -->
        <input
          v-model="kassaInput"
          type="text"
          inputmode="numeric"
          maxlength="2"
          placeholder="01"
          class="w-20 border px-2 py-1 text-center"
          required
        />

        <!-- NOMOR -->
        <input
          v-model="nomorInput"
          type="text"
          inputmode="numeric"
          maxlength="6"
          placeholder="000001"
          class="flex-1 border px-2 py-1"
          required
        />
      </div>

      <!-- Preview format -->
      <p v-if="kassa && nomor" class="text-xs text-gray-500">
        Format: 2031.SA.{{ tahunShort }}.{{ kassa }}.{{ nomor }}
      </p>

      <button
        :disabled="loading"
        class="w-full bg-blue-600 text-white py-2 rounded"
      >
        {{ loading ? 'Mencari...' : 'Cari' }}
      </button>
    </form>

    <p v-if="error" class="mt-4 text-red-600 text-sm">
      {{ error }}
    </p>

    <div v-if="hasil.length" class="mt-4 space-y-2 text-sm">
      <button
        v-for="(row, i) in hasil"
        :key="row.key"
        @click="openStruk(row)"
        class="w-full text-left border rounded p-2 hover:bg-blue-50"
      >
        {{ i + 1 }}.
        {{ row.label }} ({{ row.datetime }})
      </button>
    </div>
  </div>
</template>
