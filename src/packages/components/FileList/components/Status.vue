<template>
  <div class="status">
    <p v-show="row.speed">{{ row.speed | formatSpeed }}</p>
    <p>{{ formatStatus(row) }}</p>
  </div>
</template>

<script>
import { formatSize, secondsToStr } from '../../../commons/utils'

export default {
  filters: {
    formatSpeed(speed) {
      // Speed text
      if (speed > 0) {
        return `${formatSize(speed)} / s`
      } else {
        return ''
      }
    }
  },
  props: {
    data: {
      type: Object,
      default() {
        return {
          progress: '',
          speed: '',
          timeRemaining: '',
          uploadedSize: '',
          paused: '',
          error: '',
          isUploading: '',
          isComplete: ''
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
    formatStatus(row) {
      // Remaining time text (status)
      if (!row.computed) {
        return this.$t('status.preparing')
      } else if (row.isComplete) {
        return this.$t('status.success')
      } else if (row.error) {
        return this.$t('status.fail')
      } else if (row.isUploading) {
        const time = row.timeRemaining
        if (time === 0) {
          return '00:00:01'
        } else if (Number.isFinite(time) && time > 0) {
          return secondsToStr(time)
        } else if (!row.progress) {
          return this.$t('status.connecting')
        } else {
          return '-'
        }
      } else if (row.paused) {
        return this.$t('status.paused')
      } else if (row.progress === 1) {
        return this.$t('status.processing')
      } else {
        return this.$t('status.waiting')
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.status {
  p {
    margin: 0;
    height: 23px;
    overflow: hidden;
  }
}
</style>
