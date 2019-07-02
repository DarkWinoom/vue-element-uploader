<template>
  <div>
    <file-control
      v-show="queueLimit !== 1"
      :list="list"
      @start="handleStartAll"
      @pause="handlePauseAll"
      @clear="handleClear"
    />
    <el-tabs>
      <el-tab-pane :label="$t('control.list',{length:files.length})">
        <el-table
          v-show="list.length > 0"
          :key="key"
          v-loading="loading"
          :data="list"
          :max-height="maxHeight"
        >
          <el-table-column :label="$t('control.file')" min-width="250">
            <template slot-scope="scope">
              <file-information :data="scope.row" />
            </template>
          </el-table-column>
          <el-table-column :label="$t('control.status')" width="120">
            <template slot-scope="scope">
              <file-status :data="scope.row" />
            </template>
          </el-table-column>
          <el-table-column width="160" align="right">
            <div v-show="!scope.row.isComplete" slot-scope="scope">
              <file-button
                :data="scope.row"
                @crop="handleCrop"
                @cropDrop="handleCropDrop"
                @retry="handleRetry"
                @resume="handleResume"
                @pause="handlePause"
                @remove="handleRemove"
              />
            </div>
          </el-table-column>
        </el-table>
        <file-crop
          :id="cropId"
          v-model="showCrop"
          :file="cropFile"
          :crop-width="cropWidth"
          :crop-height="cropHeight"
          :crop-fixed="cropFixed"
          :crop-output-quantity="cropOutputQuantity"
          :crop-output-type="cropOutputType"
          @complete="handleCropComplete"
          @close="showCrop = false"
        />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script>
import SparkMD5 from 'spark-md5'
import axios from 'axios'
import FileControl from './components/Control'
import FileInformation from './components/Information'
import FileStatus from './components/Status'
import FileButton from './components/Button'
import FileCrop from './components/Crop'

export default {
  name: 'UploaderFileList',
  components: {
    FileControl,
    FileInformation,
    FileStatus,
    FileButton,
    FileCrop
  },
  props: {
    target: {
      type: String,
      default: ''
    },
    files: {
      type: Array,
      default() {
        return []
      }
    },
    queueLimit: {
      type: Number,
      default: 0
    },
    cropOpen: {
      type: Boolean,
      default: true
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
    },
    emptyOnComplete: {
      type: Boolean,
      default: false
    },
    sparkUnique: {
      type: Boolean,
      default: true
    },
    dialogVisible: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      loading: false,
      maxHeight: 0,
      key: 0, // 修改key可以强制刷新表格，用来解决删除文件后表格不更新的问题
      events: [
        'fileProgress',
        'fileSuccess' /* , 'fileComplete' */,
        'fileError'
      ],
      handlers: {},
      errors: [], // 出错的file.id，用以过滤错误提示
      successMessage: [], // 上传成功后文件从服务端返回的内容
      successList: [], // 正在合并或者已经合并成功的id列表
      index: 0, // 文件索引（按新增顺序）
      list: [],
      lines: 4, // 显示的文件行数
      completeLock: false,
      // 某些情况下complete()方法会被多次调用，加入锁可保证只会执行一次

      cropMode: undefined, // 非可选值时将会调用裁剪或还原覆盖（可选值sure或drop）
      cropQueue: undefined, // 裁剪或还原成功后调用，queue标记
      showCrop: false, // 是否显示裁剪框
      cropId: undefined, // 传入裁剪文件的id
      cropFile: undefined, // 传入裁剪的文件资源
      cropCache: [] // 已裁剪文件缓存，queue => file（用于还原）
    }
  },
  computed: {
    isComplete() {
      // 列表中的文件是否全部上传完毕（若列表为空返回true）
      let complete = false
      if (this.list.length > 0) {
        complete = true
        for (const row of this.list) {
          if (row.isComplete === false || row.error === true) {
            complete = false
          }
        }
      }
      return complete
    }
  },
  watch: {
    dialogVisible(value) {
      if (this.isComplete) return true
      for (const file of this.files) {
        if (value) {
          this._actionCheck(file)
        } else {
          this.handlePause(file.id)
        }
      }
    },
    files(value) {
      for (const file of value) {
        this.index++
        this.fileInit(file, this.index)
      }
      // 根据文件的添加顺序重新排列
      this.list.sort((a, b) => {
        return a.queue - b.queue
      })
    }
  },
  created() {
    this.maxHeight = this.lines * 93 + 48
  },
  destroyed() {
    this.events.forEach(event => {
      for (const file of this.files) {
        file.uploader.off(event, this.handlers[event])
      }
    })
  },
  methods: {
    computeMD5(file) {
      const fileReader = new FileReader()
      fileReader.readAsArrayBuffer(file.file)
      fileReader.onload = e => {
        file.uniqueIdentifier = SparkMD5.ArrayBuffer.hash(e.target.result)
        this.fileComputedDown(file.id)
      }
      fileReader.onerror = function() {
        this.fileComputedDown(file.id)
      }
    },
    fileInit(rowFile, index) {
      const files_id = this.list.map(item => item.id)
      if (!files_id.includes(rowFile.id)) {
        const isImage = rowFile.fileType.indexOf('image') !== -1
        let queue = index
        let croped = false
        if (this.cropMode === 'sure') {
          queue = this.cropQueue
          croped = true
        } else if (this.cropMode === 'drop') {
          queue = this.cropQueue
        }
        const file = {
          queue: queue,
          file: rowFile.file,
          id: rowFile.id, // 原文件列表和新生成的文件列表通过id进行对应
          type: rowFile.fileType,
          url: '',
          name: rowFile.name,
          size: rowFile.size,
          progress: 0,
          speed: 0,
          timeRemaining: 0,
          uploadedSize: 0, // 已上传的大小
          computed: !this.sparkUnique, // 为true表示计算md5成功（运行上传）
          paused: false, // 是否已暂停
          error: false, // 是否出错
          croped: croped, // 是否已经被裁剪
          canCrop: this.cropOpen && isImage, // 是否显示裁剪按钮
          isRealImage: false, // 是否是真实的图片
          isInitialization: true, // 是否新加入的文件（用于显示“开始”按钮）
          isComplete: false, // 是否已上传完毕
          isUploading: false // 是否正在上传
        }
        const eventHandler = event => {
          this.handlers[event] = (...args) => {
            if (args[1] === args[0]) {
              this[`_${event}`].apply(this, args)
            }
          }
          return this.handlers[event]
        }
        this.events.forEach(event => {
          rowFile.uploader.on(event, eventHandler(event))
        })
        if (rowFile.uploader.opts.singleFile === true) {
          // 设定了只上传单一文件
          this.list = [file]
          this.key++
        } else {
          this.list.push(file)
        }
        if (isImage) {
          const fr = new FileReader()
          fr.onload = e => {
            const row = this._getRow(rowFile.id)
            row.url = fr.result
            row.isRealImage = true
          }
          fr.readAsDataURL(rowFile.file)
        }
        if (this.sparkUnique) {
          this.computeMD5(rowFile)
        }
        setTimeout(() => {
          this.cropMode = undefined
        }, 10)
      }
    },
    fileComputedDown(id) {
      const row = this._getRow(id)
      row.computed = true
    },
    handleResume(id) {
      // 继续 & 开始下载
      const row = this._getRow(id)
      if (!row.computed) {
        this.$message.warning(this.$t('notify.filePreparing'))
      } else {
        const file = this._getFile(id)
        row.canCrop = false // 开始后禁止裁剪
        file.resume()
        this._actionCheck(file)
      }
    },
    handlePause(id) {
      // 暂停
      const file = this._getFile(id)
      file.pause()
      this._actionCheck(file)
      this._fileProgress(file)
    },
    handleRetry(id) {
      // 重试
      const file = this._getFile(id)
      file.retry()
      this._actionCheck(file)

      const index = this.errors.indexOf(id)
      this.errors.splice(index, 1)
    },
    handleRemove(id, completeCheck = true) {
      const file = this._getFile(id)
      const index = this.list.findIndex(item => item.id === id)
      this.list.splice(index, 1)
      this.$emit('remove', file)
      this.key++
      if (completeCheck) {
        this.completeCheck()
      }
    },
    _getFile(value, field = 'id') {
      return this.files.find(item => {
        return item[field] === value
      })
    },
    _getRow(value, field = 'id') {
      return this.list.find(item => {
        return item[field] === value
      })
    },
    _actionCheck(file) {
      const row = this._getRow(file.id)
      row.paused = file.paused
      row.error = file.error
      row.isUploading = file.isUploading()
    },
    _fileProgress(file) {
      const row = this._getRow(file.id)
      row.isInitialization = false
      row.progress = file.progress()
      row.speed = file.averageSpeed
      row.timeRemaining = file.timeRemaining()
      row.uploadedSize = file.sizeUploaded()
      this._actionCheck(file)
    },
    _fileSuccess(file, files, message, chunk) {
      const data = {
        totalChunks: file.chunks.length,
        filename: file.name,
        identifier: file.uniqueIdentifier
      }
      const check = (file, message) => {
        this._fileProgress(file)
        const row = this._getRow(file.id)
        row.error = false
        row.isComplete = true
        row.isUploading = false
        this.successMessage.push(message)
        this.completeCheck()
      }
      message = JSON.parse(message).data
      if (!this.successList.find(function(value) { return value === file.id })) {
        this.successList.push(file.id)
        if (message.id || data.totalChunks === 1) {
          check(file, message)
        } else {
          axios({
            url: this.target,
            method: 'post',
            data: {
              ...data,
              merge: 1
            }
          }).then(response => {
            check(file, response.data.data)
          })
        }
      }
    },
    _fileError(rootFile, file, message, chunk) {
      // 上传失败
      if (message) {
        message = JSON.parse(message).data
      } else {
        message = this.$t('notify.serverFailed')
      }
      this._fileProgress(file)
      const row = this._getRow(file.id)
      row.error = true
      row.isComplete = false
      row.isUploading = false
      if (!this.errors.includes(file.id)) {
        setTimeout(() => {
          this.$notify({
            title: this.$t('notify.uploadFailed'),
            message: typeof message === 'string' ? message : this.$t('notify.unknowError'),
            type: 'warning',
            duration: 6000
          })
        }, 10)
        this.errors.push(file.id)
      }
    },
    completeCheck() {
      // 列表中文件全部上传完成后回调
      if (this.completeLock || this.errors.length > 0 || !this.isComplete) {
        return false
      }
      this.completeLock = true
      this.$emit('complete', this.successMessage)
      if (this.emptyOnComplete === true) {
        this.loading = true
        setTimeout(() => {
          this.handleClear()
        }, 1000)
      }
    },
    handleCrop(id) {
      // 点击裁剪按钮
      const row = this._getRow(id)
      if (row.isUploading) {
        this.$message.error(this.$t('notify.uploadLock'))
      } else {
        this.cropId = id
        this.cropFile = row.file
        this.showCrop = true
      }
    },
    handleCropComplete(id, name, blob) {
      // 裁剪确认回调
      const row = this._getRow(id)
      this.showCrop = false
      this.cropMode = 'sure'
      this.cropQueue = row.queue
      this.cropCache[row.queue] = row.file
      this.handleRemove(id, false)
      const file = new File([blob], name, {
        type: blob.type,
        endings: 'transparent'
      })
      this.$emit('add-file', file)
    },
    handleCropDrop(queue) {
      // 点击裁剪还原按钮
      const row = this._getRow(queue, 'queue')
      if (row.isUploading) {
        this.$message.error(this.$t('notify.uploadLock'))
      } else if (this.cropCache[queue]) {
        this.cropMode = 'drop'
        this.cropQueue = queue
        this.handleRemove(row.id, false)
        this.$emit('add-file', this.cropCache[queue])
        delete this.cropCache[queue]
      } else {
        this.$message.error(this.$t('notify.imageCropDropFailed'))
      }
    },
    handleStartAll() {
      // 全部开始
      if (this.isComplete) return true
      for (const row of this.list) {
        if (!row.computed) {
          this.$message.warning(this.$t('notify.oneFilePreparing',{file:row.name}))
        } else {
          this.handleResume(row.id)
        }
      }
    },
    handlePauseAll() {
      // 全部暂停
      if (this.isComplete) return true
      for (const row of this.list) {
        this.handlePause(row.id)
      }
    },
    handleClear() {
      // 清空上传列表
      const files = this.files.concat()
      for (const file of files) {
        this.$emit('remove', file)
      }
      this.list = []
      this.errors = []
      this.cropCache = []
      this.successMessage = []
      this.successList = []
      this.loading = false
    }
  }
}
</script>
