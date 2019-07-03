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
      // The start button can be used by the following:
      // 1.The list is not empty.
      // 2.Not all of the files is uploading.
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
      // The pause button cna be used by the following:
      // 1.The list is not empty.
      // 2.The uploading file at least one.
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
      // The clear button cna be used by the following:
      // 1.The list is not empty.
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
