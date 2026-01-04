<template>
  <div class="bg-gray-100 min-h-screen flex items-center justify-center">
    <!-- === PENGGANTI {{ $slot }} === -->
    <RouterView />
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>
