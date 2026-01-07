<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { cariByTanggal } from '@/services/struk.service'
import type { StrukItem } from '@/types/struk'
import BackButton from '@/components/BackButton.vue'

const tanggalPicker = ref('')
const kassaInput = ref('')

const hasil = ref<StrukItem[]>([])
const error = ref('')
const loading = ref(false)

const today = new Date().toISOString().slice(0, 10)

// ddmmyyyy
const tanggal = computed(() => {
  if (!tanggalPicker.value) return ''
  const [y, m, d] = tanggalPicker.value.split('-')
  return `${d}${m}${y}`
})

// 01 – 80
const kassa = computed(() => {
  if (!kassaInput.value) return ''
  return kassaInput.value.padStart(2, '0')
})

watch([tanggalPicker, kassaInput], () => {
  hasil.value = []
  error.value = ''
})

async function onSubmit(): Promise<void> {
  error.value = ''
  hasil.value = []
  loading.value = true

  if (!/^\d{1,2}$/.test(kassaInput.value)) {
    error.value = 'Kassa harus 1–2 digit angka'
    loading.value = false
    return
  }

  try {
    hasil.value = await cariByTanggal({
      tanggal: tanggal.value,
      kassa: kassa.value,
    })
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

function openStruk(row: StrukItem): void {
  const key = `${row.kassa}.${row.nomor}`
  window.open(
    `/estruk/preview/${row.tahun}/${key}`, // ✅ FIX
    '_blank',
    'width=900,height=600',
  )
}
</script>

<template>
  <div class="w-full max-w-md bg-white p-6 rounded shadow">
    <BackButton />

    <h1 class="text-xl font-bold text-center mb-4">
      Cari E-Struk (Tanggal & Kassa)
    </h1>

    <form @submit.prevent="onSubmit" class="space-y-3">
      <input
        type="date"
        v-model="tanggalPicker"
        :max="today"
        class="w-full border px-2 py-1"
        required
      />

      <input
        v-model="kassaInput"
        type="text"
        inputmode="numeric"
        maxlength="2"
        placeholder="KASSA"
        class="w-full border px-2 py-1 text-center"
        required
      />

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
        class="w-full border rounded p-2 hover:bg-blue-50 text-left"
      >
        {{ i + 1 }}. {{ row.label }} ({{ row.datetime }})
      </button>
    </div>
  </div>
</template>
