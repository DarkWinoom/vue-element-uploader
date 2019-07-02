<template>
  <div>
    <template v-if="row.canCrop">
      <template v-if="row.croped">
        <el-button
          :title="$t('control.revert')"
          size="medium"
          type="primary"
          icon="el-icon-refresh-left"
          circle
          @click="handleCropDrop(row.queue)"
        />
      </template>
      <template v-else>
        <el-button
          :title="$t('control.crop')"
          size="medium"
          type="primary"
          icon="el-icon-scissors"
          circle
          @click="handleCrop(row.id)"
        />
      </template>
    </template>
    <template v-if="row.error">
      <el-button
        :title="$t('control.retry')"
        size="medium"
        type="warning"
        icon="el-icon-refresh-right"
        circle
        @click="handleRetry(row.id)"
      />
    </template>
    <template v-else-if="row.isInitialization">
      <el-button
        :title="$t('control.start')"
        size="medium"
        type="success"
        icon="el-icon-caret-right"
        :disabled="!row.computed"
        circle
        @click="handleResume(row.id)"
      />
    </template>
    <template v-else>
      <el-button
        v-if="row.paused"
        :title="$t('control.resume')"
        size="medium"
        type="success"
        icon="el-icon-caret-right"
        circle
        @click="handleResume(row.id)"
      />
      <el-button
        v-if="!row.paused"
        :title="$t('control.pause')"
        size="medium"
        icon="el-icon-video-pause"
        circle
        @click="handlePause(row.id)"
      />
    </template>
    <el-button
      :title="$t('control.remove')"
      size="medium"
      type="danger"
      icon="el-icon-delete"
      circle
      @click="handleRemove(row.id)"
    />
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      required: true,
      default() {
        return {
          id: '',
          croped: '',
          canCrop: '',
          error: '',
          isInitialization: '',
          paused: ''
        }
      }
    }
  },
  data() {
    return {
      row: this.data
    }
  },
  methods: {
    handleCrop(id) {
      this.$emit('crop', id)
    },
    handleCropDrop(queue) {
      this.$emit('cropDrop', queue)
    },
    handleResume(id) {
      this.$emit('resume', id)
    },
    handlePause(id) {
      this.$emit('pause', id)
    },
    handleRetry(id) {
      this.$emit('retry', id)
    },
    handleRemove(id) {
      this.$confirm(this.$t('confirm.removeFile'), this.$t('confirm.system'), {
        type: 'warning'
      }).then(() => {
        this.$emit('remove', id)
      })
    }
  }
}
</script>
