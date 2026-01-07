<script setup lang="ts">
import { ref, computed } from 'vue'
import { cariByKeyword } from '@/services/struk.service'
import type { StrukItem } from '@/types/struk'
import BackButton from '@/components/BackButton.vue'

const tanggalPicker = ref('')
const kassa = ref('')
const keyword = ref('')

const hasil = ref<StrukItem[]>([])
const error = ref('')
const loading = ref(false)
const searched = ref(false)

const today = new Date().toISOString().slice(0, 10)

// ddmmyyyy | undefined
const tanggal = computed(() => {
  if (!tanggalPicker.value) return undefined
  const [y, m, d] = tanggalPicker.value.split('-')
  return `${d}${m}${y}`
})

async function onSubmit(): Promise<void> {
  loading.value = true
  error.value = ''
  hasil.value = []
  searched.value = true

  try {
    hasil.value = await cariByKeyword({
      keyword: keyword.value.toUpperCase(),
      tanggal: tanggal.value,
      kassa: kassa.value || undefined,
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
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <BackButton />

    <h1 class="text-xl font-bold text-center mb-4">
      Cari E-Struk (Keyword)
    </h1>

    <form @submit.prevent="onSubmit" class="space-y-4">
      <input
        type="date"
        v-model="tanggalPicker"
        :max="today"
        class="w-full border px-2 py-1"
      />

      <input
        type="number"
        v-model="kassa"
        class="w-full border px-2 py-1"
        placeholder="Kassa (opsional)"
      />

      <input
        type="text"
        v-model="keyword"
        class="w-full border px-2 py-1"
        required
        placeholder="Keyword"
      />

      <button
        :disabled="loading"
        class="w-full bg-blue-600 text-white py-2 rounded"
      >
        {{ loading ? 'Mencari...' : 'Cari' }}
      </button>
    </form>

    <p
      v-if="searched && !hasil.length && !error"
      class="mt-4 text-gray-500 text-center"
    >
      Data tidak ditemukan
    </p>

    <p v-if="error" class="mt-4 text-red-600 text-center">
      {{ error }}
    </p>

    <div v-if="hasil.length" class="mt-4 space-y-2">
      <div v-for="(row, i) in hasil" :key="row.key">
        {{ i + 1 }}.
        <button
          @click="openStruk(row)"
          class="text-blue-600 underline"
        >
          {{ row.label }}
        </button>
        ({{ row.datetime }})
      </div>
    </div>
  </div>
</template>
