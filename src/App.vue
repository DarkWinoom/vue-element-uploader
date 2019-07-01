<template>
  <div id="app">
    <vue-element-uploader />
  </div>
</template>

<script>
import VueElementUploader from './packages'

export default {
  name: 'App',
  components: {
    VueElementUploader
  },
  data() {
    return {
      empty: ''
    }
  }
}
</script>
