<script lang="ts">
import { defineComponent } from 'vue'
import CustomButton from '@/components/CustomButton.vue'

export default defineComponent({
  name: 'TestInfo',
  inheritAttrs: false,
  components: {
    CustomButton,
  },
})
</script>

<template>
  <div
    class="p-16 rounded-xl mt-4 text-center sm:text-left text-gray-400 w-fit mx-auto sm:w-full max-w-[500px] bg-gradient-to-r from-accent-blue/25 to-accent-purple/25 border-2 border-accent-blue"
  >
    <h1 class="text-base font-normal uppercase sm:text-lg"><slot name="title"></slot></h1>
    <h2 class="font-semibold text-2xl pt-2 mb-6 sm:flex justify-between sm:text-3xl">
      <slot name="latency"></slot>
    </h2>
    <h3 class="font-semibold justify-between text-xl mb-8 sm:flex sm:text-2xl">
      <slot name="meta"></slot>
    </h3>
    <div class="w-full flex justify-center">
      <CustomButton v-bind="$attrs">Make Test</CustomButton>
    </div>
  </div>
</template>
