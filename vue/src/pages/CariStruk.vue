<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { cariByNomor } from '../services/struk.service'
import type { StrukItem } from '../types/struk'

const tahunList = ref<string[]>([])
const tahun = ref('')
const kassa = ref('')
const nomor = ref('')

const hasil = ref<StrukItem[]>([])
const error = ref('')
const loading = ref(false)

const tahunShort = computed(() =>
  tahun.value ? tahun.value.slice(-2) : '',
)

onMounted(loadTahun)

watch(tahun, () => {
  hasil.value = []
  error.value = ''
})

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

async function cari(): Promise<void> {
  error.value = ''
  hasil.value = []
  loading.value = true

  try {
    hasil.value = await cariByNomor({
      tahun: tahun.value,
      kassa: kassa.value,
      nomor: nomor.value,
    })
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

function openStruk(row: StrukItem): void {
  const url = `/struk/preview/${row.tahun}/${row.kassa}.${row.nomor}`
  window.open(url, '_blank', 'width=900,height=600')
}
</script>

<template>
  <div class="w-full max-w-md bg-white rounded-lg shadow p-6">
    <h1 class="text-xl font-bold text-center mb-4">
      Cari E-Struk (Nomor)
    </h1>

    <form @submit.prevent="cari" class="space-y-4">
      <select v-model="tahun" class="w-full border rounded px-2 py-1">
        <option v-for="t in tahunList" :key="t" :value="t">{{ t }}</option>
      </select>

      <div class="flex gap-2">
        <span>2031.SA.{{ tahunShort }}.</span>
        <input v-model="kassa" type="number" class="w-20 border px-2 py-1" required />
        <input v-model="nomor" type="number" class="flex-1 border px-2 py-1" required />
      </div>

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
        class="w-full text-left border rounded p-2 hover:bg-blue-50"
      >
        {{ i + 1 }}. {{ row.label }} ({{ row.datetime }})
      </button>
    </div>
  </div>
</template>
