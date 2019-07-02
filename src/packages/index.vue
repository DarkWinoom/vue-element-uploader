<template>
  <support-check :support="support">
    <el-row :gutter="20">
      <el-col :span="8">
        <div ref="drop">
          <drop-field>
            <div v-html="$t('tips.dropField')" />
          </drop-field>
        </div>
        <upload-tips
          :show-switch="fastTransfer !== false"
          :queue-limit="queueLimit"
          :size-limit="sizeLimit"
          :type-limit="typeLimit"
          :crop-show="cropOpen"
          :crop-width="cropWidth"
          :crop-height="cropHeight"
          @change="handleChangeForceUpload"
        />
      </el-col>
      <el-col :span="16">
        <file-list
          ref="fileList"
          :target="target"
          :files="files"
          :queue-limit="queueLimit"
          :crop-open="cropOpen"
          :crop-width="cropWidth"
          :crop-height="cropHeight"
          :crop-fixed="cropFixed"
          :crop-output-quantity="cropOutputQuantity"
          :crop-output-type="cropOutputType"
          :empty-on-complete="emptyOnComplete"
          :dialog-visible="dialogVisible"
          :spark-unique="sparkUnique"
          @add-file="handleAddFile"
          @remove="handleRemove"
          @complete="complete"
        />
      </el-col>
    </el-row>
  </support-check>
</template>
<script>
import Uploader from 'simple-uploader.js'
import SupportCheck from './components/SupportCheck'
import DropField from './components/DropField'
import UploadTips from './components/UploadTips'
import FileList from './components/FileList'

export default {
  name: 'VueElementUploader',
  provide() {
    return {
      uploader: this
    }
  },
  components: {
    SupportCheck,
    DropField,
    UploadTips,
    FileList
  },
  props: {
    field: {
      // Upload files field
      type: String,
      default: 'upload'
    },
    target: {
      // Upload target url
      type: String,
      default: 'https://httpbin.org/post'
    },
    headers: {
      // Additional request header
      type: Function,
      default() {
        return function(file, chunk) {
          return {}
        }
      }
    },
    query: {
      // Additional request query
      type: Function,
      default() {
        return function(file, chunk) {
          return {}
        }
      }
    },
    fastTransfer: {
      // Whether to enable the fast transfer and breakpoint resume mode, if the backend does not support this feature, you can set 'false' to close
      // Allow users to choose by default
      type: [String, Boolean],
      default: 'auto'
    },
    queueLimit: {
      // The maximum number of files allowed in the queue (default is not limited)
      type: Number,
      default: 0
    },
    sizeLimit: {
      // Single file size limit (default is not limited)
      type: Number,
      default: 0
    },
    typeLimit: {
      // Upload file format restrictions, support for extensions with image, video, audio (default is not limited)
      type: Array,
      default() {
        return []
      }
    },
    cropOpen: {
      // Whether to enable the intercept function (automatically identify the picture)
      type: Boolean,
      default: true
    },
    cropWidth: {
      // Default generation of screenshot frame width (80% of the parent container width by default)
      type: Number,
      default: 0
    },
    cropHeight: {
      // Default generation of screenshot frame height (80% of the parent container height by default)
      type: Number,
      default: 0
    },
    cropFixed: {
      // The screenshot frame control
      // true - Fixed size, can't change
      // [width, height] - fixed ratio (such as [1, 1])
      // false - Not fixed size, free to change
      type: [Boolean, Array],
      default: false
    },
    cropOutputQuantity: {
      // Crop the quality of the generated image (range 0.1 - 1)
      type: Number,
      default: 1
    },
    cropOutputType: {
      // Crop the format of the generated image (jpg, png, or webp)
      type: String,
      default: 'png'
    },
    emptyOnComplete: {
      // Clear the list after upload completed (if true)
      type: Boolean,
      default: false
    },
    sparkUnique: {
      // 使用spark-md5计算值来代替uniqueIdentifier
      // 在添加文件时将会自动计算，对于大体积文件需要花费一定时间（同时在计算时可能会造成浏览器卡顿）
      // 通过计算后，将会更加准确的触发快传和断点续传
      type: Boolean,
      default: true
    },
    dialogVisible: {
      // 外部控件显示与否，用于更新文件状态
      // 使用模态框显示时需要将该参数绑定其visible属性，不使用时忽略
      type: Boolean,
      default: false
    },
    lang: {
      // 使用的语言
      type: String,
      default: 'auto'
    }
  },
  data() {
    const testChunks = () => {
      if (typeof this.fastTransfer === 'boolean') {
        return this.fastTransfer
      } else {
        return true
      }
    }
    return {
      loading: false,
      // 是否支持
      support: true,
      // simple-uploader 配置
      simpleUploaderOptions: {
        target: this.target,
        testChunks: testChunks(),
        checkChunkUploadedByResponse: (chunk, message) => {
          const objMessage = JSON.parse(message)
          if (objMessage.data.id) {
            return true
          }
          return (objMessage.data.skipChunks || []).indexOf(chunk.offset + 1) >= 0
        },
        // simultaneousUploads: 1,
        fileParameterName: this.field,
        successStatuses: [200, 201, 202],
        permanentErrors: [206, 404, 415, 500, 501],
        singleFile: this.queueLimit === 1,
        initialPaused: true,
        headers: this.headers(),
        query: this.query()
      },
      files: []
    }
  },
  created() {
    if (this.lang !== 'auto') {
      this.$i18n.locale = this.lang
    }
    this.uploader = new Uploader(this.simpleUploaderOptions)
    this.support = this.uploader.support
    if (this.support) {
      this.uploader.on('fileAdded', this.fileAdded)
      this.uploader.on('fileRemoved', this.fileRemoved)
      this.uploader.on('filesSubmitted', this.filesSubmitted)
    }
  },
  mounted() {
    this.$nextTick(() => {
      if (this.support) {
        var accept = []
        if (this.typeLimit) {
          for (let type of this.typeLimit) {
            type = type.toLowerCase()
            switch (type) {
              case 'image':
                accept.push('image/*')
                break
              case 'video':
                accept.push('video/*')
                break
              case 'audio':
                accept.push('audio/*')
                break
              default:
                if (type) {
                  accept.push('.' + type)
                }
            }
          }
        }
        this.uploader.assignBrowse(this.$refs.drop, false, false, {
          accept: accept.join(',')
        })
        if (this.$refs.drop) {
          this.uploader.assignDrop(this.$refs.drop)
        }
      }
    })
  },
  destroyed() {
    if (this.support) {
      this.uploader.off('fileAdded', this.fileAdded)
      this.uploader.off('fileRemoved', this.fileRemoved)
      this.uploader.off('filesSubmitted', this.filesSubmitted)
      if (this.$refs.drop) {
        this.uploader.unAssignDrop(this.$refs.drop)
      }
    }
    console.log('Uploader has been destroyed')
  },
  methods: {
    ignoreNotify(file, title, type = 'warning') {
      // 自定义通知拼接
      setTimeout(() => {
        this.$notify({
          title: title,
          message: this.$t('notify.fileAddIgnore', { file: file.name }),
          type: type,
          duration: 6000
        })
      }, 100)
    },
    handleChangeForceUpload(value) {
      this.uploader.opts.testChunks = !value
    },
    complete(message) {
      this.$emit('complete', message)
    },
    fileAdded(file, event) {
      // 格式与大小检测
      if (this.queueLimit > 1 && this.files.length >= this.queueLimit) {
        this.ignoreNotify(file, this.$t('notify.queueLimitError'))
        return false
      } else if (file.name.lastIndexOf('.') === -1) {
        this.ignoreNotify(file, this.$t('notify.typeLimitError'))
        return false
      } else if (this.typeLimit.length > 0) {
        const suffix = file.name
          .substring(file.name.lastIndexOf('.') + 1, file.name.length)
          .toLowerCase()
        let access = false
        for (const type of this.typeLimit) {
          switch (type) {
            case 'image':
            case 'video':
            case 'audio':
              if (file.fileType.indexOf(type) !== -1) {
                access = true
              }
              break
            default:
              if (type === suffix) {
                access = true
              }
          }
        }
        if (!access) {
          this.ignoreNotify(file, this.$t('notify.typeLimitError'))
          return false
        }
      }
      if (this.sizeLimit && file.size > this.sizeLimit) {
        this.ignoreNotify(file, this.$t('notify.sizeLimitError'))
        return false
      }
    },
    fileRemoved(file) {
      const index = this.files.findIndex(item => item.id === file.id)
      this.files.splice(index, 1)
    },
    filesSubmitted(files, fileList, event) {
      if (
        this.queueLimit > 0 &&
        this.files.length + files.length > this.queueLimit
      ) {
        const less = this.queueLimit - this.files.length
        for (const index in files) {
          this.files.push(files[index])
          if (index > less - 1) {
            this.ignoreNotify(files[index], this.$t('notify.queueLimitError'))
            this.remove(files[index])
          }
        }
      } else {
        this.files = [...this.files, ...files]
      }
    },
    handleAddFile(file) {
      this.uploader.addFile(file)
    },
    handleRemove(file) {
      this.uploader.removeFile(file)
    }
  }
}
</script>
