import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],

  base: '/estruk/',

  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
})
