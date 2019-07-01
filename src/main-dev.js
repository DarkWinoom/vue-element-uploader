import Vue from 'vue'
import App from './App.vue'

import i18n from './lang'

Vue.use({
  i18n: (key, value) => i18n.t(key, value)
})

new Vue({
  el: '#app',
  i18n,
  render: h => h(App)
})
