import { createApp } from 'vue'
import App from './App.vue'
import { router } from '../src/routers'
import './style.css'

router.afterEach((to) => {
  const baseTitle = 'E-Struk Suzuya Mall Banda Aceh'
  document.title = to.meta.title
    ? `${to.meta.title} - ${baseTitle}`
    : baseTitle
})

createApp(App)
  .use(router)
  .mount('#app')
