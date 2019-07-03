<template>
  <el-dialog
    class="cropper"
    :visible.sync="value"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :append-to-body="true"
    :before-close="handleClose"
    :title="$t('tips.imageCrop')"
  >
    <template v-if="!error">
      <div class="content" :style="{'height':height}">
        <vue-cropper
          ref="cropper"
          v-loading="loading"
          :img="image"
          :output-size="option.size"
          :auto-crop="option.autoCrop"
          :auto-crop-width="option.autoCropWidth"
          :auto-crop-height="option.autoCropHeight"
          :fixed="option.fixed"
          :fixed-number="option.fixedNumber"
          :full="option.full"
          :fixed-box="option.fixedBox"
          :center-box="option.centerBox"
        />
      </div>
      <el-button-group>
        <el-button
          type="default"
          :title="$t('control.zoomIn')"
          size="medium"
          icon="el-icon-plus"
          plain
          @click="changeScale(1)"
        />
        <el-button
          type="default"
          :title="$t('control.zoomOut')"
          size="medium"
          icon="el-icon-minus"
          plain
          @click="changeScale(-1)"
        />
        <el-button
          type="default"
          :title="$t('control.rotateLeft')"
          size="medium"
          icon="el-icon-refresh-left"
          plain
          @click="rotateLeft"
        />
        <el-button
          type="default"
          :title="$t('control.rotateRight')"
          size="medium"
          icon="el-icon-refresh-right"
          plain
          @click="rotateRight"
        />
      </el-button-group>
    </template>
    <template v-else>
      <p>{{ $t('notify.imageLoadFailed') }}</p>
    </template>
    <canvas v-show="false" ref="canvas" :width="cropWidth" :height="cropHeight" />
    <div slot="footer" class="dialog-footer">
      <el-button size="medium" @click="handleClose">{{ $t('tips.close') }}</el-button>
      <el-button v-show="!error" type="primary" size="medium" :loading="loading" @click="finish">
        {{ loading? $t('tips.cropping'):$t('tips.ok') }}
      </el-button>
    </div>
  </el-dialog>
</template>

<script>
import { VueCropper } from 'vue-cropper'

export default {
  components: { VueCropper },
  props: {
    value: {
      // Show or hide the dialog
      type: Boolean,
      default: false
    },
    id: {
      // Current file's id
      type: Number,
      default: undefined
    },
    file: {
      // Current file's resource
      type: File,
      default: undefined
    },
    cropWidth: {
      type: Number,
      default: 0
    },
    cropHeight: {
      type: Number,
      default: 0
    },
    cropFixed: {
      type: [Boolean, Array],
      default: false
    },
    cropOutputQuantity: {
      type: Number,
      default: 1
    },
    cropOutputType: {
      type: String,
      default: 'png'
    }
  },
  data() {
    let fixed = true
    let fixedNumber = [1, 1]
    let fixedBox = false
    if (this.cropFixed === true) {
      fixed = false
      fixedBox = true
    } else if (this.cropFixed === false) {
      fixed = false
    } else {
      fixedNumber = this.cropFixed
    }
    return {
      error: false,
      image: undefined,
      option: {
        // vue-cropper config, reference https://github.com/xyxiao001/vue-cropper/blob/master/english.md
        outputSize: this.cropOutputQuantity,
        outputType: this.cropOutputType,
        autoCrop: true,
        autoCropWidth: this.cropWidth,
        autoCropHeight: this.cropHeight,
        fixed: fixed,
        fixedNumber: fixedNumber,
        fixedBox: fixedBox,
        centerBox: true,
        high: false,
        original: true,
        infoTrue: true
      },
      loading: false
    }
  },
  computed: {
    height() {
      if (this.cropHeight <= 300) {
        return '300px'
      } else {
        return this.cropHeight + 'px'
      }
    }
  },
  watch: {
    value(value) {
      if (value) {
        this.init()
        const fr = new FileReader()
        fr.onload = e => {
          const img = new Image()
          img.src = fr.result
          img.onerror = e => {
            this.error = true
          }
          img.onload = () => {
            this.image = img.src
          }
        }
        fr.onerror = e => {
          this.error = true
        }
        fr.readAsDataURL(this.file)
      }
    }
  },
  methods: {
    init() {
      this.image =
        'data:image/png;base64,UmFyIRoHAQAzkrXlCgEFBgAFAQGAgABRBebzLAIDC4gABIgAIPex5LiAAAAQ5rWL6K+V55SodHh0LnR4dAoDApWmBesFmNQBdGVzdLLiytQdd1ZRAwUEAA=='
        // A blank image
      this.loading = false
      this.error = false
    },
    changeScale(num) {
      // Modify the image size. The positive number becomes larger. The negative number becomes smaller.
      if (this.loading) {
        return false
      }
      num = num || 1
      this.$refs.cropper.changeScale(num)
    },
    rotateLeft() {
      // Rotate 90 degrees to the left
      if (this.loading) {
        return false
      }
      this.$refs.cropper.rotateLeft()
    },
    rotateRight() {
      // Rotate 90 degrees to the right
      if (this.loading) {
        return false
      }
      this.$refs.cropper.rotateRight()
    },
    finish() {
      // Crop success (by click OK button)
      if (this.error) {
        this.$emit('close')
      } else {
        this.loading = true
        this.$refs.cropper.getCropBlob(data => {
          this.$emit('complete', this.id, this.file.name, data)
          this.loading = false
        })
      }
    },
    handleClose() {
      this.$emit('close')
    }
  }
}
</script>

<style lang="scss">
.cropper {
  .el-dialog__body {
    padding: 10px 20px 0;
  }
}
</style>
<style lang="scss" scoped>
.content {
  width: auto;
  height: 300px;
  margin-bottom: 20px;
}
.el-button-group {
  display: block;
}
</style>
