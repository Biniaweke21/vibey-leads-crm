import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast, { POSITION } from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import './style.css'
import App from './App.vue'
import router from './router'

const app = createApp(App)

// Surface unexpected render/lifecycle errors instead of letting Vue silently
// blank the page — logs the real cause and keeps the app diagnosable.
app.config.errorHandler = (err, _instance, info) => {
  console.error(`[app error] ${info}`, err)
}

app.use(createPinia())
app.use(router)
app.use(Toast, {
  position: POSITION.TOP_RIGHT,
  timeout: 4000,
  closeOnClick: true,
  pauseOnFocusLoss: false,
  pauseOnHover: true,
  draggable: false,
  hideProgressBar: false,
  icon: true,
  maxToasts: 4,
  transition: 'Vue-Toastification__fade',
})

app.mount('#app')
