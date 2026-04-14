<script lang="ts">
import { defineComponent } from 'vue'
import { useDataStore } from '@/stores/data'
import Remove from '@/components/icons/Remove.vue'
import CustomButton from '../CustomButton.vue'

export default defineComponent({
  name: 'DataTable',
  components: {
    Remove,
    CustomButton,
  },
  props: {
    protocol: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      dataStore: useDataStore(),
    }
  },
  computed: {
    array() {
      if (this.protocol == 'http') return this.dataStore.httpData
      else return this.dataStore.mqttData
    },
    averageLatency() {
      if (this.protocol == 'http') return this.dataStore.averageHttpLatency
      else return this.dataStore.averageMqttTotalLatency
    },
    averageMessageLatency() {
      return this.dataStore.averageMqttMessageLatency
    },
  },
  methods: {
    deleteItem(index: number) {
      if (this.protocol == 'http') this.dataStore.deleteHttpItem(index)
      else this.dataStore.deleteMqttItem(index)
    },
    clear() {
      if (this.protocol == 'http') this.dataStore.clearHttpData()
      else this.dataStore.clearMqttData()
    },
  },
})
</script>

<template>
  <div class="flex flex-col items-center max-w-xl mx-auto">
    <table class="mb-4 w-full text-sm sm:text-lg">
      <thead class="border-y -2 border-gray-400">
        <tr class="text-left">
          <th>ID</th>
          <th>{{ protocol == 'http' ? 'Latency' : 'Total Latency' }}</th>
          <th>{{ protocol == 'http' ? 'Status' : 'Receive Latency' }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in array" :key="index" class="border-b border-gray-400">
          <td>{{ index + 1 }}</td>
          <td>{{ protocol == 'http' ? item.latency : item.totalLatency }}</td>
          <td>{{ protocol == 'http' ? item.status : item.messageLatency }}</td>
          <td>
            <button @click="deleteItem(index)">
              <Remove class="-mb-0.5 fill-accent-purple hover:scale-110" />
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <CustomButton @click="clear" class="mb-8">Clear</CustomButton>

    <div>
      <div class="w-full flex flex-col items-center sm:flex-row justify-between sm:text-lg/10">
        <h3>{{ protocol == 'http' ? 'Average Latency' : 'Average Total Latency' }}:</h3>
        <span class="pl-2 font-bold">{{ averageLatency }} ms</span>
      </div>
      <div
        v-if="protocol == 'mqtt'"
        class="w-full flex flex-col items-center sm:flex-row flex-between sm:text-lg/10"
      >
        <h3>Average Receive Latency:</h3>
        <span class="pl-2 font-bold">{{ averageMessageLatency }} ms</span>
      </div>
    </div>
  </div>
</template>

<style>
table th,
table td {
  padding: 20px 10px;
}
</style>
