<script lang="ts">
import { defineComponent } from 'vue'
import { useHttpStore } from '@/stores/http'
import { useDataStore } from '@/stores/data'
import TestInfo from '@/components/protocols/TestInfo.vue'

export default defineComponent({
  name: 'HttpTest',
  components: {
    TestInfo,
  },
  data() {
    return {
      httpStore: useHttpStore(),
    }
  },
  computed: {
    latency() {
      return this.httpStore.latency
    },
    status() {
      return this.httpStore.status
    },
  },
  methods: {
    request() {
      this.httpStore.request()
    },
  },
})
</script>

<template>
  <TestInfo @click="request">
    <template #title>Http</template>
    <template #latency
      >Latency:
      <span class="text-white text-nowrap font-bold block sm:inline"
        >{{ latency }} ms</span
      ></template
    >
    <template #meta
      >Response status:
      <span class="text-white text-nowrap font-bold block sm:inline">{{ status }}</span></template
    >
  </TestInfo>
</template>
