<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'CustomButton',
})
</script>

<template>
  <a
    class="text-sm sm:text-base mt-4 py-2.5 px-4 max-w-fit bg-accent-blue rounded-lg font-semibold hover:scale-105 hover:text-white active:bg-lightest-blue"
    ><slot></slot
  ></a>
</template>
