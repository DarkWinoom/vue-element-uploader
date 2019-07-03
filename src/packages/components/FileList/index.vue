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
      maxHeight: 'auto',
      key: 0, // Change value will refresh the table list
      events: [
        'fileProgress',
        'fileSuccess' /* , 'fileComplete' */,
        'fileError'
      ],
      handlers: {},
      errors: [], // The array of file.id, make show the error tips only display once
      successMessage: [], // The array of message witch form server after file upload success
      successList: [], // The array of upload successed file.id
      index: 0, // The index of the list files
      list: [], // Current file list
      lines: 4, // Show the scroller when the list files more than it
      completeLock: false, // Using complete lock to make show the complete function only run once

      cropMode: undefined, // Crop mode mark when file added (sure, drop or undefined)
      cropQueue: undefined, // Current crop file's queue value
      showCrop: false, // Whether to display the crop dialog
      cropId: undefined, // Current crop file's id
      cropFile: undefined, // Current crop file's resource
      cropCache: [] // Clipped file cache, queue => file (for file restore)
    }
  },
  computed: {
    isComplete() {
      // Whether all the files in the list have been uploaded (or the list is empty)
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
      // When hide this component of dialog, pause all of the uploading files
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
      // Sort the list by queue value
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
          queue: queue, // file's queue of insert
          file: rowFile.file, // file's resource
          id: rowFile.id, // file's id (customize by simple-uploader.js)
          type: rowFile.fileType, // file's mime type
          url: '', // file's base64 url (image only)
          name: rowFile.name, // file's original name (named by uploader user)
          size: rowFile.size, // file's real size (byte)
          progress: 0, // A number of file uploading progress, 0 to 1
          speed: 0, // Average speed of a uploading file, bytes per second
          timeRemaining: 0, // Estimated remaining upload time
          uploadedSize: 0, // Uploaded size
          computed: !this.sparkUnique, // Calculation file's identifier success or not
          paused: false, // Indicated if file is paused
          error: false, // Indicated if file has encountered an error
          croped: croped, // Whether a file has been cropped
          canCrop: this.cropOpen && isImage, // Show crop button or not
          isRealImage: false, // Whether a image is real
          isInitialization: true, // Whether the file is new one
          isComplete: false, // Whether the file has completed uploading and received a server response
          isUploading: false // Whether file chunks is uploading
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
          // When setting is true, once one file is uploaded, second file will overtake existing one, first one will be canceled
          this.list = [file]
          this.key++
        } else {
          this.list.push(file)
        }
        if (isImage) {
          // If the file is image file, will show a picture snap and can be preview it
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
      // star or resume uploading
      const row = this._getRow(id)
      if (!row.computed) {
        this.$message.warning(this.$t('notify.filePreparing'))
      } else {
        const file = this._getFile(id)
        row.canCrop = false // It can not be crop, when the file has star uploading
        file.resume()
        this._actionCheck(file)
      }
    },
    handlePause(id) {
      // Pause a uploading file
      const file = this._getFile(id)
      file.pause()
      this._actionCheck(file)
      this._fileProgress(file)
    },
    handleRetry(id) {
      // Retry uploading when a file has encountered an error
      const file = this._getFile(id)
      file.retry()
      this._actionCheck(file)

      const index = this.errors.indexOf(id)
      this.errors.splice(index, 1)
    },
    handleRemove(id, completeCheck = true) {
      // Remove a file from the display list and the file list
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
      // Get simple-uloader.js file with file id (or other unique field)
      return this.files.find(item => {
        return item[field] === value
      })
    },
    _getRow(value, field = 'id') {
      // Get display list file with file id (or other unique field)
      return this.list.find(item => {
        return item[field] === value
      })
    },
    _actionCheck(file) {
      // Check current status of a file
      const row = this._getRow(file.id)
      row.paused = file.paused
      row.error = file.error
      row.isUploading = file.isUploading()
    },
    _fileProgress(file) {
      // Progress current status of a file
      const row = this._getRow(file.id)
      row.isInitialization = false
      row.progress = file.progress()
      row.speed = file.averageSpeed
      row.timeRemaining = file.timeRemaining()
      row.uploadedSize = file.sizeUploaded()
      this._actionCheck(file)
    },
    _fileSuccess(file, files, message, chunk) {
      // File upload success
      // Attention. this function can be called more than once
      const data = {
        totalChunks: file.chunks.length,
        filename: file.name,
        identifier: file.uniqueIdentifier
      }
      const check = (file, message) => {
        // Check a file's status to display
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
        // Make sure the following can be running once (in a file)
        this.successList.push(file.id)
        if (message.id || data.totalChunks === 1) {
          // A file total chunks only one, means no need to execute merge commands
          check(file, message)
        } else {
          // A file upload success, command server to merge it
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
      // Upload fail
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
      // Check all files upload complete, and emit it on true
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
      // User click crop button
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
      // Call this when a file crop success
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
      // User click crop drop button, revert file
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
      // Start all files
      if (this.isComplete) return true
      for (const row of this.list) {
        if (!row.computed) {
          this.$message.warning(this.$t('notify.oneFilePreparing', { file: row.name }))
        } else {
          this.handleResume(row.id)
        }
      }
    },
    handlePauseAll() {
      // Pause all files
      if (this.isComplete) return true
      for (const row of this.list) {
        this.handlePause(row.id)
      }
    },
    handleClear() {
      // Clear all files
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
