<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { cariByKeyword } from '../services/struk.service'
import type { StrukItem } from '../types/struk'

const tanggalPicker = ref('')
const kassa = ref('')
const keyword = ref('')

const hasil = ref<StrukItem[]>([])
const error = ref('')
const loading = ref(false)
const searched = ref(false)

const today = new Date().toISOString().slice(0, 10)
const tanggal = computed(() => {
  if (!tanggalPicker.value) return undefined
  const [y, m, d] = tanggalPicker.value.split('-')
  return `${d}${m}${y}`
})

watch(keyword, v => (keyword.value = v.toUpperCase()))

async function onSubmit(): Promise<void> {
  loading.value = true
  error.value = ''
  hasil.value = []
  searched.value = true

  try {
    hasil.value = await cariByKeyword({
      keyword: keyword.value,
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
  window.open(
    `/struk/preview/${row.tahun}/${row.kassa}.${row.nomor}`,
    '_blank',
    'width=800,height=400',
  )
}
</script>

<template>
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold text-center mb-4">
      Cari e-Struk (Keyword)
    </h1>

    <form @submit.prevent="onSubmit" class="space-y-4">
      <input type="date" v-model="tanggalPicker" :max="today" />
      <input type="number" v-model="kassa" placeholder="Kassa (opsional)" />
      <input type="text" v-model="keyword" required placeholder="Keyword" />

      <button :disabled="loading" class="w-full bg-blue-600 text-white py-2 rounded">
        {{ loading ? 'Mencari...' : 'Cari' }}
      </button>
    </form>

    <p v-if="searched && !hasil.length && !error" class="mt-4 text-gray-500 text-center">
      Data tidak ditemukan
    </p>

    <p v-if="error" class="mt-4 text-red-600 text-center">{{ error }}</p>

    <div v-if="hasil.length" class="mt-4">
      <div v-for="(row, i) in hasil" :key="row.key">
        {{ i + 1 }}.
        <button @click="openStruk(row)" class="text-blue-600 underline">
          {{ row.label }}
        </button>
        ({{ row.datetime }})
      </div>
    </div>
  </div>
</template>
