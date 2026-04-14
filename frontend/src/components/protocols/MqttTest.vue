<script lang="ts">
import { defineComponent } from 'vue'
import { useMqttStore } from '@/stores/mqtt'
import TestInfo from '@/components/protocols/TestInfo.vue'

export default defineComponent({
  name: 'MqttTest',
  components: {
    TestInfo,
  },
  data() {
    return {
      mqttStore: useMqttStore(),
    }
  },
  computed: {
    totalLatency() {
      return this.mqttStore.totalLatency
    },
    messageLatency() {
      return this.mqttStore.messageLatency
    },
  },
  methods: {
    subscribe() {
      this.mqttStore.subscribe()
    },
  },
})
</script>

<template>
  <TestInfo @click="subscribe">
    <template #title>Mqtt</template>
    <template #latency
      >Latency With Connection:
      <span class="font-bold text-nowrap text-white block sm:inline"
        >{{ totalLatency }} ms</span
      ></template
    >
    <template #meta
      >Recieve Latency:
      <span class="font-bold text-nowrap text-white block sm:inline"
        >{{ messageLatency }} ms</span
      ></template
    >
  </TestInfo>
</template>
