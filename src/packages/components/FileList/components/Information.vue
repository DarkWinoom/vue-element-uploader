<template>
  <div>
    <div class="thumbnails">
      <template v-if="isImage">
        <img ref="viewer" :src="row.url" :alt="row.name" class="none">
        <el-image :src="row.url" :title="$t('tips.preview')" fit="contain" @load="initViewer" @click="showViewer">
          <div slot="error" class="image-slot">
            <i :title="$t('notify.imageLoadFailed')" class="el-icon-picture-outline" />
          </div>
        </el-image>
      </template>
      <template v-else>
        <i class="el-icon-document" />
      </template>
    </div>
    <div class="information">
      <p class="name">
        <el-tag v-show="row.croped" size="mini" type="warning">{{ $t('tips.cropped') }}</el-tag>
        <span>{{ row.name }}</span>
      </p>
      <el-progress
        :text-inside="true"
        :stroke-width="15"
        :percentage="row | formatProgress"
        :color="progressColor(row)"
      />
      <p class="size">
        <el-tag size="mini">{{ row.name | formatType }}</el-tag>
        <span>{{ row.size * row.progress | formatSize }}</span>
        /
        <span>{{ row.size | formatSize }}</span>
      </p>
    </div>
  </div>
</template>

<script>
import { formatSize } from '../../../commons/utils'
import Viewer from 'viewerjs'
import 'viewerjs/dist/viewer.css'

export default {
  filters: {
    formatSize(size) {
      return formatSize(size)
    },
    formatType(name) {
      return name.substr(name.lastIndexOf('.') + 1).toUpperCase()
    },
    formatProgress(row) {
      // Progress text
      let progress = Math.floor(row.progress * 10000) / 100
      if (progress >= 100 && !row.isComplete) {
        progress = 99
      }
      return progress
    }
  },
  props: {
    data: {
      type: Object,
      required: true,
      default() {
        return {
          name: '',
          type: '',
          url: '',
          size: '',
          croped: '',
          progress: '',
          isComplete: ''
        }
      }
    }
  },
  data() {
    return {
      row: this.data,
      isImage: false,
      viewer: undefined
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.isImage = this.row.type.indexOf('image') !== -1
    })
  },
  methods: {
    initViewer() {
      const viewer = new Viewer(this.$refs.viewer, {
        inline: false,
        navbar: false,
        toolbar: {
          zoomIn: 4,
          zoomOut: 4,
          oneToOne: 4,
          reset: false,
          prev: false,
          play: false,
          next: false,
          rotateLeft: 4,
          rotateRight: 4,
          flipHorizontal: 4,
          flipVertical: 4
        }
      })
      this.viewer = viewer
    },
    showViewer() {
      if (this.viewer) {
        this.viewer.show()
      }
    },
    progressColor(row) {
      if (row.isComplete) {
        return '#67C23A' // green
      } else if (row.error) {
        return '#F56C6C' // red
      } else {
        return '#409EFF' // blue
      }
    }
  }
}
</script>
<style lang="scss">
.viewer-transition{
  transition: all 0.2s;
}
</style>

<style lang="scss" scoped>
.none {
  display: none;
}
.thumbnails {
  position: absolute;
  top: 12px;
  height: 30px;
  .el-image {
    position: relative;
    width: 30px;
    height: 30px;
    cursor: pointer;
  }
  i {
    position: relative;
    top: 3px;
    font-size: 30px;
  }
}
.information {
  padding-left: 40px;
  .el-progress {
    margin: 3px 0;
  }
  p {
    margin: 0;
    height: 23px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    &.name {
      font-size: 16px;
      color: #333;
    }
    &.size {
      font-size: 14px;
      color: #aaa;
    }
  }
}
</style>
