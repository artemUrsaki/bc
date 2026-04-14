<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'ContentImage',
  props: {
    imageName: {
      type: String,
      required: true,
    },
  },
})
</script>

<template>
  <img
    :src="'/img/' + imageName + '.png '"
    alt="Http Image"
    class="max-h-96 rounded-xl order-1 lg:order-none my-4 sm:my-8 lg:my-0 mx-auto drop-shadow-xl"
  />
</template>
