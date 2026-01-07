<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getStrukStream } from '@/services/struk.service'

const route = useRoute()
const content = ref('')
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    content.value = await getStrukStream({
      tahun: route.params.tahun as string,
      key: route.params.key as string,
    })
  } catch (e: any) {
    error.value = e.message ?? 'Gagal memuat struk'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page">
    <div v-if="loading">Memuat struk...</div>
    <div v-else-if="error">{{ error }}</div>

    <!-- RENDER TXT APA ADANYA -->
    <pre v-else class="struk">{{ content }}</pre>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  justify-content: center;
  padding: 16px;
}

/* STRUK HARUS FIXED-WIDTH */
.struk {
  font-family: "Courier New", Consolas, monospace;
  font-size: 13px;
  line-height: 1.3;
  white-space: pre;
  background: #ffffff;
  color: #000;
  padding: 12px;
  border: 1px dashed #ccc;
  max-width: 460px; /* mirip kertas struk */
  overflow-x: auto;
}
</style>
