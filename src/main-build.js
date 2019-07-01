import VueElementUploader from './packages'

if (typeof window !== 'undefined' && window.Vue) {
  window.Vue.component('vue-element-uploader', VueElementUploader)
}

export default VueElementUploader
