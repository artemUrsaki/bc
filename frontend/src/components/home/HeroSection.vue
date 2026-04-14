<script lang="ts">
import { defineComponent } from 'vue'
import CustomButton from '../CustomButton.vue'

export default defineComponent({
  name: 'HeroSection',
  components: {
    CustomButton,
  },
})
</script>

<template>
  <section
    class="sm:pt-[100px] min-h-hero relative overflow-hidden bg-gradient-to-b from-dark-blue to-lighter-blue lg:grid grid-cols-2 gap-x-20 gap-y-8 items-center"
  >
    <div class="flex flex-col gap-y-8 items-center lg:items-start">
      <h1 class="uppercase text-wrap text-center lg:text-left">
        Http vs mqtt <br />
        <span class="text-wrap capitalize text-accent-purple">Internet protocols</span>
      </h1>
      <p class="text-xl/8 text-center text-pretty lg:text-left font-medium">
        Understanding the difference, advantages and disadvantages of each protocol. Comparison of
        performance, security, and flexibility.
      </p>
      <CustomButton href="#section"> Read more </CustomButton>
    </div>

    <img
      class="hidden lg:block rounded-xl shadow-2xl min-w-[1000px]"
      src="/img/main.png"
      alt="Main Image"
    />
  </section>
</template>
