<script lang="ts">
import { defineComponent } from 'vue'
import BurgerMenu from '@/components/header/BurgerMenu.vue'
import NavItems from './NavItems.vue'

export default defineComponent({
  name: 'Navigation',
  components: {
    BurgerMenu,
    NavItems,
  },
})
</script>

<template>
  <header
    class="fixed z-50 h-[100px] w-full top-0 left-0 flex justify-between px-8 bg-dark-blue border-b border-accent-blue"
  >
    <RouterLink class="flex h-[100px] items-center px-4 uppercase font-semibold" to="/"
      >Home</RouterLink
    >

    <ul class="hidden sm:flex font-semibold">
      <NavItems />
    </ul>

    <BurgerMenu />
  </header>
</template>

<style>
header a {
  display: flex;
  height: 100px;
  align-items: center;
  padding: 0 1rem;
  text-transform: uppercase;
}
</style>
