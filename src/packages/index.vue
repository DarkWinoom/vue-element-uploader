<template>
  <div>
    <h1>{{ $t('title') }}</h1>
    <button @click="handleClick">{{ $t('click') }}</button>
    <ul>
      <li><a @click="changeLang('zh-cn')">中文版</a></li>
      <li><a @click="changeLang('en')">English</a></li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'VueElementUploader',
  data() {
    return {
      msg: ''
    }
  },
  methods: {
    changeLang(lang) {
      this.$i18n.locale = lang
    },
    handleClick() {
      alert(this.$t('title'))
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
ul{
  li{
    display: inline-block;
    margin: 0 10px;
    a{
      cursor: pointer;
      text-decoration: underline;
    }
  }
}
</style>
