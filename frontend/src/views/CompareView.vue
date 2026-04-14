<script lang="ts">
import { defineComponent } from 'vue'
import DataTable from '@/components/protocols/DataTable.vue'

export default defineComponent({
  name: 'Compare',
  components: {
    DataTable,
  },
  data() {
    return {
      protocols: ['http', 'mqtt'],
    }
  },
})
</script>

<template>
  <section class="py-[100px] flex flex-col gap-32 lg:flex-row lg:gap-16">
    <div v-for="protocol in protocols" :key="protocol" class="w-full">
      <h2 class="uppercase text-center mb-8">{{ protocol }}</h2>
      <DataTable :protocol="protocol" />
    </div>
  </section>
</template>
