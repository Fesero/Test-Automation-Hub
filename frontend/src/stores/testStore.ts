import { defineStore, acceptHMRUpdate } from 'pinia';
import { apiClient } from 'src/boot/axios';

export const useTestStore = defineStore('test', {
  state: () => ({
    tests: [],
    loading: false
  }),
  actions: {
    async fetchTests() {
      this.loading = true
      try {
        const res = await apiClient.get('/tests')
        this.tests = res.data
      } finally {
        this.loading = false
      }
    }
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useTestStore, import.meta.hot));
}
