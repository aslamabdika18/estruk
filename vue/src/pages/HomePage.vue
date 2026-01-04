<template>
  <div class="w-full max-w-md bg-white rounded-lg shadow-md p-6">

    <h1 class="text-2xl font-semibold text-center mb-4">
      Cari E-Struk
    </h1>

    <hr class="mb-4" />

    <p class="text-sm text-gray-600 mb-3">
      Cari Struk Berdasarkan:
    </p>

    <div class="space-y-3">

      <!-- Tanggal & Keyword -->
      <RouterLink
        to="/keyword"
        class="flex items-center gap-3 p-3 border rounded-lg
               hover:bg-blue-50 transition"
      >
        <span class="w-4 h-4 rounded-full border border-blue-500"></span>
        <span class="font-medium">Tanggal & Kata / Barcode</span>
      </RouterLink>

      <!-- Tanggal & Kassa -->
      <RouterLink
        to="/tanggal"
        class="flex items-center gap-3 p-3 border rounded-lg
               hover:bg-blue-50 transition"
      >
        <span class="w-4 h-4 rounded-full border border-blue-500"></span>
        <span class="font-medium">Tanggal & Kassa</span>
      </RouterLink>

      <!-- Nomor / E-Struk -->
      <RouterLink
        to="/nomor"
        class="flex items-center gap-3 p-3 border rounded-lg
               hover:bg-blue-50 transition"
      >
        <span class="font-medium">E-Struk</span>
      </RouterLink>

    </div>

    <hr class="my-4" />

    <p class="text-center text-xs text-gray-500">
      © {{ currentYear }} Suzuya Mall Banda Aceh
    </p>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'

const currentYear: number = new Date().getFullYear()
</script>
