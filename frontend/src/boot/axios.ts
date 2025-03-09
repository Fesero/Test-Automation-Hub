import { boot } from 'quasar/wrappers'
import axios from 'axios'
import type { AxiosInstance } from 'axios'  // Используйте import type

const apiClient: AxiosInstance = axios.create({
  baseURL: process.env.DEV 
    ? 'http://localhost:8000/api' 
    : '/api',
  headers: {
    'Content-Type': 'application/json'
  }
})

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = apiClient
})

export { apiClient }