import { defineStore } from 'pinia'
import mqtt from 'mqtt'
import { useDataStore } from './data'

export const useMqttStore = defineStore({
  id: 'mqtt',
  state: () => ({
    messageLatency: 0,
    totalLatency: 0,
    dataStore: useDataStore(),
  }),
  actions: {
    subscribe() {
      const topic = 'topic/test/mqtt'
      const client = mqtt.connect('wss://test.mosquitto.org:8081')
      const connectionStartTime = performance.now()
      client.on('connect', () => {
        console.log('Connected')
        client.subscribe(topic, () => {
          console.log('Subscribed')
          client.publish(topic, 'test message')
          const subscribedStartTime = performance.now()

          client.on('message', (topic, message) => {
            const endTime = performance.now()
            console.log(message.toString())
            this.totalLatency = Number((endTime - connectionStartTime).toFixed(3))
            this.messageLatency = Number((endTime - subscribedStartTime).toFixed(3))
            client.end()

            this.dataStore.addMqttItem(this.totalLatency, this.messageLatency)
          })
        })
      })
    },
  },
})
