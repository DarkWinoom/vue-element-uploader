<template>
  <div>
    <h1>{{ msg }}</h1>
    <button @click="handleClick">Click me</button>
  </div>
</template>

<script>
export default {
  name: 'VueElementUploader',
  data() {
    return {
      msg: 'Hello World'
    }
  },
  methods: {
    handleClick() {
      alert(this.msg)
    }
  }
}
</script>

<style lang="scss">
#app {
  font-family: 'Avenir', Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
h1 {
  font-weight: normal;
}
</style>
