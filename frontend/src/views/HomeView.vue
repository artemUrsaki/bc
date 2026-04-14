<script lang="ts">
import { defineComponent } from 'vue'
import data from '@/data/data.json'
import HeroSection from '@/components/home/HeroSection.vue'
import ContentSection from '@/components/home/ContentSection.vue'
import ContentImage from '@/components/ContentImage.vue'
import Paragraph from '@/components/Paragraph.vue'

export default defineComponent({
  name: 'HomeView',
  components: {
    HeroSection,
    ContentSection,
    ContentImage,
    Paragraph,
  },
  computed: {
    httpTags() {
      return data.tags.http
    },
    mqttTags() {
      return data.tags.mqtt
    },
    httpRoute() {
      return data.nav[0]
    },
    mqttRoute() {
      return data.nav[1]
    },
  },
})
</script>

<template>
  <HeroSection />

  <ContentSection
    id="section"
    :tagNames="httpTags"
    :pageRoute="httpRoute"
    class="scroll-m-[100px] bg-gradient-to-b from-lighter-blue to-dark-blue"
  >
    <template #image>
      <ContentImage imageName="http" />
    </template>

    <template #title>Http</template>

    <template #article>
      <Paragraph>
        <span class="text-accent-purple font-semibold">Hypertext Transfer Protocol</span> is an
        application-layer protocol for transmitting hypermedia documents, such as HTML. It was
        designed for communication between web browsers and web servers, but it can also be used for
        other purposes, such as machine-to-machine communication, programmatic access to APIs, and
        more.
      </Paragraph>

      <Paragraph>
        HTTP follows a classical client-server model, with a client opening a connection to make a
        request, then waiting until it receives a response from the server. HTTP is a stateless
        protocol, meaning that the server does not keep any session data between two requests,
        although the later addition of cookies adds state to some client-server interactions.
      </Paragraph>
    </template>
  </ContentSection>

  <ContentSection
    :tagNames="mqttTags"
    :imageOnRight="true"
    :pageRoute="mqttRoute"
    class="bg-gradient-to-b from-dark-blue to-lighter-blue"
  >
    <template #image>
      <ContentImage imageName="mqtt" />
    </template>

    <template #title>Mqtt</template>

    <template #article>
      <Paragraph>
        <span class="text-accent-purple font-semibold">Message Queuing Telemetry Transport</span> is
        the most commonly used messaging protocol for the Internet of Things (IoT). MQTT stands for
        MQ Telemetry Transport. The protocol is a set of rules that defines how IoT devices can
        publish and subscribe to data over the Internet. MQTT is used for messaging and data
        exchange between IoT and industrial IoT (IIoT) devices, such as embedded devices, sensors,
        industrial PLCs, etc. The protocol is event driven and connects devices using the publish
        /subscribe (Pub/Sub) pattern. The sender (Publisher) and the receiver (Subscriber)
        communicate via Topics and are decoupled from each other. The connection between them is
        handled by the MQTT broker. The MQTT broker filters all incoming messages and distributes
        them correctly to the Subscribers.
      </Paragraph>
    </template>
  </ContentSection>
</template>
