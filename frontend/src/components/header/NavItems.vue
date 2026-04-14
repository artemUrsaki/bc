<script lang="ts">
import { defineComponent } from 'vue'
import data from '@/data/data.json'

export default defineComponent({
  name: 'NavItems',
  computed: {
    navItems() {
      return data.nav
    },
  },
})
</script>

<template>
  <li class="list-none" v-for="item in navItems" :key="item.name">
    <RouterLink :to="{ name: item.route, params: item.params }">{{ item.name }}</RouterLink>
  </li>
</template>
