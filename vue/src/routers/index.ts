import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  { path: '/', component: () => import('@/pages/HomePage.vue') },
  { path: '/nomor', component: () => import('@/pages/CariStruk.vue') },
  { path: '/tanggal', component: () => import('@/pages/CariStrukTanggal.vue') },
  { path: '/keyword', component: () => import('@/pages/CariStrukKeyword.vue') },
  { path: '/preview/:tahun/:key',name: 'preview-struk', component: () => import('@/pages/PreviewStruk.vue'),
}

]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
