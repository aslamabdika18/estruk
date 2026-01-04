import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path' // ⬅️ WAJIB

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(), // ✅ Tailwind CSS v4
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
})
