<template>
  <div class="el-upload__tip">
    <template v-if="showSwitch">
      <el-checkbox v-model="checked" @change="handleChange">{{ $t('tips.forceUpload') }}</el-checkbox>
      <el-popover
        placement="right"
        :title="$t('tips.forceUpload')"
        width="300"
        trigger="hover"
        :content="$t('tips.forceUploadTips')"
      >
        <i slot="reference" class="el-icon-warning" />
      </el-popover>
    </template>
    <template v-if="queueLimit > 0">
      <p v-if="queueLimit === 1">{{ $t('tips.singleFile') }}</p>
      <p v-else>{{ $t('tips.queueLimit',{limit:queueLimit}) }}</p>
    </template>
    <p>{{ $t('tips.typeLimit',{limit:typeLimitTips}) }}</p>
    <p v-show="cropShow">{{ $t('tips.sizeSuggestion',{width:cropWidth,height:cropHeight}) }}</p>
    <p>{{ $t('tips.sizeLimig',{limit:sizeLimitTips}) }}</p>
  </div>
</template>

<script>
import { formatSize } from '../commons/utils'

export default {
  name: 'UploaderUploadTips',
  props: {
    showSwitch: {
      type: Boolean,
      default: true
    },
    queueLimit: {
      type: Number,
      default: 0
    },
    sizeLimit: {
      type: Number,
      default: 0
    },
    typeLimit: {
      type: Array,
      default() {
        return []
      }
    },
    cropShow: {
      type: Boolean,
      default: true
    },
    cropWidth: {
      type: Number,
      default: 120
    },
    cropHeight: {
      type: Number,
      default: 120
    }
  },
  data() {
    return {
      checked: false
    }
  },
  computed: {
    typeLimitTips() {
      // Format type to Easy-to-read text
      if (this.typeLimit.length > 0) {
        const typeTips = []
        for (const type of this.typeLimit) {
          let suffix
          switch (type) {
            case 'image':
              suffix = this.$t('tips.image')
              break
            case 'video':
              suffix = this.$t('tips.video')
              break
            case 'audio':
              suffix = this.$t('tips.audio')
              break
            default:
              suffix = type
          }
          typeTips.push(suffix)
        }
        return typeTips.join(', ') + this.$t('tips.format')
      } else {
        return this.$t('tips.unlimited')
      }
    },
    sizeLimitTips() {
      // Format size to Easy-to-read text
      if (this.sizeLimit) {
        return formatSize(this.sizeLimit)
      } else {
        return this.$t('tips.unlimited')
      }
    }
  },
  methods: {
    handleChange() {
      // Change the force upload checkbox
      this.$emit('change', this.checked)
    }
  }
}
</script>

<style lang="scss" scoped>
.el-upload__tip {
  font-size: 14px;
  color: #909399;
  line-height: 28px;
  margin-top: 10px;
  p{
    margin: 0;
  }
}
.el-checkbox{
    margin: 0;
}
</style>
