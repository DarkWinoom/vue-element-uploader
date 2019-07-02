<template>
  <div id="app">
    <vue-element-uploader
      target="http://localhost/laboratory/uploader.php"
      :size-limit="500 * 1024 * 1024"
      :type-limit="['image', 'video', 'rar']"
      :queue-limit="20"
      :crop-width="400"
      :crop-height="300"
      :crop-fixed="[4,3]"
      lang="en"
      @complete="complete"
    />
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
  },
  methods: {
    complete(message) {
      this.$message.success('upload success!')
      console.log(message)
    }
  }
}
</script>

<style lang="scss" scoped>
#app{
  margin: 20px;
}
</style>

