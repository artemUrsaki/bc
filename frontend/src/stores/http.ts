import { defineStore } from 'pinia'
import axios from 'axios'
import { useDataStore } from './data'

export const useHttpStore = defineStore({
  id: 'http',
  state: () => ({
    dataStore: useDataStore(),
    latency: 0,
    status: 0,
  }),
  actions: {
    async request() {
      try {
        const startTime = performance.now()
        const response = await axios.get('https://jsonplaceholder.typicode.com/todos/1')
        const endTime = performance.now()

        this.latency = Number((endTime - startTime).toFixed(2))
        this.status = response.status
        this.dataStore.addHttpItem(this.latency, this.status)
      } catch (err) {
        console.log(err)
      }
    },
  },
})
