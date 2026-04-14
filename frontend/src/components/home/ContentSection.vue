<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import TagComponent from '@/components/TagComponent.vue'
import RightArrow from '@/components/icons/RightArrow.vue'

export default defineComponent({
  name: 'ContentSection',
  components: {
    TagComponent,
    RightArrow,
  },
  props: {
    imageOnRight: {
      type: Boolean,
      default: false,
    },
    tagNames: {
      type: Array as PropType<string[]>,
      default: [],
    },
    pageRoute: {
      type: Object,
      required: true,
    },
  },
})
</script>

<template>
  <section
    :class="imageOnRight ? 'lg:flex-row-reverse' : 'lg:flex-row'"
    class="flex flex-col gap-8"
  >
    <div class="lg:flex-1">
      <slot name="image"></slot>
    </div>
    <div class="contents lg:flex flex-col gap-y-4 -mt-24 flex-1">
      <h2 class="uppercase text-center lg:text-left order-first lg:pb-4">
        <slot name="title"></slot>
      </h2>

      <slot name="article"></slot>

      <div class="mt-4 flex gap-x-4 gap-y-2 flex-wrap">
        <TagComponent v-for="tag in tagNames" :key="tag" :tagName="tag" />
      </div>

      <RouterLink
        :to="{ name: pageRoute.route, params: pageRoute.params }"
        class="w-fit flex items-center group order-5 text-base font-semibold mt-4 text-accent-blue active:scale-[.99]"
        >Learn more
        <RightArrow class="fill-accent-blue w-3 inline ml-2 group-hover:ml-2.5 transition-all"
      /></RouterLink>
    </div>
  </section>
</template>
