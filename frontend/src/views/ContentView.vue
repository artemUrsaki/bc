<script lang="ts">
import { defineComponent } from 'vue'
import data from '@/data/data.json'
import Paragraph from '@/components/Paragraph.vue'
import ContentImage from '@/components/ContentImage.vue'
import RequestInfo from '@/components/protocols/TestInfo.vue'
import HttpTest from '@/components/protocols/HttpTest.vue'
import MqttTest from '@/components/protocols/MqttTest.vue'

export default defineComponent({
  name: 'ContentView',
  components: {
    Paragraph,
    ContentImage,
    RequestInfo,
    HttpTest,
    MqttTest,
  },
  props: {
    slug: {
      type: String,
      required: true,
    },
  },
  computed: {
    dataObject() {
      return data.views.find((item) => item.name == this.slug) as any
    },
  },
  methods: {
    isFirst(index: number) {
      if (index === 0) return 'text-lg/9 sm:text-xl/9'
    },
  },
})
</script>

<template>
  <section class="py-[100px] md:px-32 border-b border-accent-blue">
    <h2 class="uppercase text-center pb-8">
      {{ dataObject.name }}
    </h2>
    <div v-for="(item, index) in dataObject.content" :key="index">
      <Paragraph class="py-4" :class="isFirst(index)">
        {{ item.text }}
      </Paragraph>
      <ContentImage v-if="item.image" :imageName="item.image" class="my-8 lg:my-16" />
    </div>
    <h2 class="text-center pt-16 pb-8 uppercase">Test</h2>
    <HttpTest v-if="dataObject.name == 'http'" />
    <MqttTest v-else />
  </section>
</template>
