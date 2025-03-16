import { defineStore, acceptHMRUpdate } from 'pinia';
import { apiClient } from 'src/boot/axios';
import type { TestResult } from 'src/types/test.type';
import { useQuasar } from 'quasar';

export const useTestStore = defineStore('test', {
  state: () => ({
    tests: [] as TestResult[],
    loading: false,
    error: null as string | null
  }),
  actions: {
    async fetchTests() {
      const $q = useQuasar()
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching tests...')
        const res = await apiClient.get('/tests')
        console.log('Response:', res.data)
        this.tests = res.data
        
        if (this.tests.length === 0) {
          console.log('No tests found')
          $q.notify({
            message: 'Нет доступных тестов',
            color: 'warning',
            icon: 'info'
          })
        }
      } catch (error) {
        console.error('Error fetching tests:', error)
        this.error = error instanceof Error ? error.message : 'Unknown error'
        $q.notify({
          message: `Ошибка загрузки тестов: ${this.error}`,
          color: 'negative',
          icon: 'error'
        })
      } finally {
        this.loading = false
      }
    }
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useTestStore, import.meta.hot))
}
