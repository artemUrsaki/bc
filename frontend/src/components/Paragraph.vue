<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'Paragraph',
})
</script>

<template>
  <p class="text-pretty">
    <slot></slot>
  </p>
</template>
