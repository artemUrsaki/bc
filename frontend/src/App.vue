<script lang="ts">
import { defineComponent } from 'vue'
import Navigation from '@/components/header/Navigation.vue'
import FooterSection from './components/FooterSection.vue'

export default defineComponent({
  name: 'App',
  components: {
    Navigation,
    FooterSection,
  },
  computed: {
    isHome() {
      if (this.$route.name == 'home') return 'border-none'
    },
  },
})
</script>

<template>
  <Navigation :class="isHome" />

  <main class="flex-1">
    <RouterView :key="$route.fullPath" />
  </main>

  <FooterSection :class="isHome" />
</template>
