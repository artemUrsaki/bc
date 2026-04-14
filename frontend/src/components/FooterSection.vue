<script lang="ts">
import { defineComponent } from 'vue'
import NavItems from './header/NavItems.vue'

export default defineComponent({
  name: 'FooterSection',
  components: {
    NavItems,
  },
  data() {
    return {
      links: [
        {
          name: 'MDN',
          href: 'https://developer.mozilla.org/',
        },
        {
          name: 'MQTT',
          href: 'https://mqtt.org/',
        },
        {
          name: 'EMQX',
          href: 'https://www.emqx.com/',
        },
        {
          name: 'HiveMQ',
          href: 'https://www.hivemq.com/',
        },
      ],
    }
  },
})
</script>

<template>
  <footer class="px-6 pb-8 pt-16 bg-dark-blue text-sm/6 border-t border-accent-blue">
    <div class="flex flex-col sm:flex-row sm:justify-between gap-8">
      <div class="lg:max-w-[40%]">
        <h3 class="uppercase font-bold tracking-widest mb-4">
          <span class="text-accent-blue">http</span>/<span class="text-accent-purple">mqtt</span>
        </h3>
        <p class="text-gray-400 text-sm/6 text-pretty">
          Understanding the difference, advantages and disadvantages of each protocol. Comparison of
          performance, security, and flexibility.
        </p>
      </div>

      <div class="flex gap-8 lg:gap-16">
        <div>
          <h3>Navigation</h3>
          <ul>
            <NavItems />
          </ul>
        </div>

        <div class="sm:pr-4">
          <h3>Sources</h3>
          <ul>
            <li v-for="link in links" :key="link.name">
              <a :href="link.href">{{ link.name }}</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="mt-8 pt-4 border-t border-gray-500 text-gray-400">Made by: Artem Ursaki</div>
  </footer>
</template>

<style>
footer a,
footer a.router-link-exact-active {
  color: rgb(107 114 128 / 1);
}

footer a:hover {
  color: #0077ff;
}
</style>
