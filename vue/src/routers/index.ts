import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'landing-page',
    component: () => import('@/pages/HomePage.vue'),
    meta: { title: 'Cari E-Struk' },
  },
  {
    path: '/nomor',
    name: 'cari-struk-nomor',
    component: () => import('@/pages/CariStruk.vue'),
    meta: { title: 'Cari E-Struk (Nomor)' },
  },
  {
    path: '/tanggal',
    name: 'cari-struk-tanggal',
    component: () => import('@/pages/CariStrukTanggal.vue'),
    meta: { title: 'Cari E-Struk (Tanggal & Kassa)' },
  },
  {
    path: '/keyword',
    name: 'cari-struk-keyword',
    component: () => import('@/pages/CariStrukKeyword.vue'),
    meta: { title: 'Cari E-Struk (Keyword)' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
