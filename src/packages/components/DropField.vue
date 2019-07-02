<template>
  <div>
    <div class="el-upload el-upload--text">
      <div class="el-upload-dragger">
        <i class="el-icon-upload" />
        <div class="el-upload__text"><slot /></div>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  name: 'UploaderDropField',
  data() {
    return {
      empty: ''
    }
  }
}
</script>

<style lang="scss" scoped>
.el-upload {
  display: block;
  margin-top: 10px;
  .el-upload-dragger {
    width: 100%;
  }
}
</style>
