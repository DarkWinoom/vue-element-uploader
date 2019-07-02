<template>
  <div class="upload-pane">
    <div class="button">
      <el-button
        :disabled="!showStart"
        type="success"
        icon="el-icon-caret-right"
        size="mini"
        plain
        @click="handleStartAll"
      >{{ $t('control.start') }}</el-button>
      <el-button
        :disabled="!showPause"
        type="default"
        icon="el-icon-video-pause"
        size="mini"
        plain
        @click="handlePauseAll"
      >{{ $t('control.pause') }}</el-button>
      <el-button
        :disabled="!showClear"
        type="danger"
        icon="el-icon-delete"
        size="mini"
        plain
        @click="handleClear"
      >{{ $t('control.clear') }}</el-button>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    list: {
      type: Array,
      default() {
        return []
      }
    }
  },
  data() {
    return {
      empty: ''
    }
  },
  computed: {
    showStart() {
      // 开始按钮可用条件：列表非空，并且至少有一项处于非暂停状态（初始化也将视作非暂停）
      if (this.list.length > 0) {
        let paused = 0
        for (const item of this.list) {
          if (item.paused || item.isInitialization) {
            paused++
          }
        }
        return paused > 0
      }
      return false
    },
    showPause() {
      // 暂停按钮可用条件：列表非空，并且至少有一项正在上传中
      if (this.list.length > 0) {
        let uploading = 0
        for (const item of this.list) {
          if (item.isUploading) {
            uploading++
          }
        }
        return uploading > 0
      }
      return false
    },
    showClear() {
      // 清空按钮可用条件：列表非空
      return this.list.length > 0
    }
  },
  methods: {
    handleStartAll() {
      this.$emit('start')
    },
    handlePauseAll() {
      this.$emit('pause')
    },
    handleClear() {
      this.$confirm(this.$t('confirm.clearList'), this.$t('confirm.system'), {
        type: 'warning'
      }).then(() => {
        this.$emit('clear')
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.upload-pane {
  position: relative;
  z-index: 2;
  .button {
    position: absolute;
    top: 3px;
    right: 0;
  }
}
</style>
