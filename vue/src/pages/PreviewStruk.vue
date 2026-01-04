<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getStrukStream } from '@/services/struk.service'

const route = useRoute()
const content = ref('')
const loading = ref(true)

onMounted(async () => {
  content.value = await getStrukStream({
    tahun: route.params.tahun as string,
    key: route.params.key as string,
  })
  loading.value = false
})
</script>

<template>
  <pre v-if="!loading" class="font-mono text-sm whitespace-pre-wrap">
{{ content }}
  </pre>
</template>
