<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome(): void {
  router.push('/')
}
</script>

<template>
  <button
    type="button"
    @click="goHome"
    class="mb-4 text-sm text-blue-600 hover:underline flex items-center gap-1"
  >
    ← Home
  </button>
</template>
