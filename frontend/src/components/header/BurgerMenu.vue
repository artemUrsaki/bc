<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import OpenMenu from '@/components/icons/OpenMenu.vue'
import CloseMenu from '@/components/icons/CloseMenu.vue'
import NavItems from './NavItems.vue'

export default defineComponent({
  name: 'BurgerMenu',
  components: {
    OpenMenu,
    CloseMenu,
    NavItems,
  },
  data() {
    return {
      isOpen: false,
    }
  },
  methods: {
    switchNav() {
      this.isOpen = !this.isOpen
    },
  },
})
</script>

<template>
  <button @click="switchNav" class="block sm:hidden px-4">
    <OpenMenu v-show="!isOpen" />
    <CloseMenu v-show="isOpen" />
  </button>

  <div
    id="burger"
    class="block sm:hidden absolute w-full top-[100px] left-0 bg-dark-blue border-t border-accent-blue drop-shadow-xl"
    v-show="isOpen"
  >
    <NavItems />
  </div>
</template>

<style>
#burger a {
  display: block;
  padding: 1.5rem 3rem;
}
</style>
