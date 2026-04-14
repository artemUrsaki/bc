<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TagComponent',
  props: {
    tagName: {
      type: String,
      required: true,
    },
  },
})
</script>

<template>
  <div class="w-fit py-2 px-2.5 font-semibold text-xs sm:text-sm bg-accent-purple rounded-md">
    {{ tagName }}
  </div>
</template>
