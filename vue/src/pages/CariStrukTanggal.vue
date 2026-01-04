<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { cariByTanggal } from '../services/struk.service'
import type { StrukItem } from '../types/struk'

const tanggalPicker = ref('')
const kassa = ref('')

const hasil = ref<StrukItem[]>([])
const error = ref('')
const loading = ref(false)

const today = new Date().toISOString().slice(0, 10)
const tanggal = computed(() => tanggalPicker.value.replace(/-/g, ''))

watch([tanggalPicker, kassa], () => {
  hasil.value = []
  error.value = ''
})

async function onSubmit(): Promise<void> {
  loading.value = true
  error.value = ''
  hasil.value = []

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
  window.open(
    `/struk/preview/${row.tahun}/${row.kassa}.${row.nomor}`,
    '_blank',
    'width=800,height=400',
  )
}
</script>

<template>
  <div class="w-full max-w-md bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold text-center mb-4">
      Cari E-Struk (Tanggal & Kassa)
    </h1>

    <form @submit.prevent="onSubmit" class="space-y-3">
      <input type="date" v-model="tanggalPicker" :max="today" required />
      <input type="number" v-model="kassa" required />

      <button :disabled="loading" class="w-full bg-blue-600 text-white py-2 rounded">
        {{ loading ? 'Mencari...' : 'Cari' }}
      </button>
    </form>

    <p v-if="error" class="mt-4 text-red-600 text-sm">{{ error }}</p>

    <div v-if="hasil.length" class="mt-4 space-y-2 text-sm">
      <button
        v-for="(row, i) in hasil"
        :key="row.key"
        @click="openStruk(row)"
        class="w-full border rounded p-2 hover:bg-blue-50"
      >
        {{ i + 1 }}. {{ row.label }} ({{ row.datetime }})
      </button>
    </div>
  </div>
</template>
