/**
 * Format Timestamp (00:00:00)
 *
 * @param {Integer} s Timestamp
 */
export function secondsToStr(s) {
  let t
  if (s > -1) {
    const hour = Math.floor(s / 3600)
    const min = Math.floor(s / 60) % 60
    const sec = s % 60
    if (hour < 10) {
      t = '0' + hour + ':'
    } else {
      t = hour + ':'
    }

    if (min < 10) {
      t += '0'
    }
    t += min + ':'
    if (sec < 10) {
      t += '0'
    }
    t += sec.toFixed(0)
  }
  return t
}

/**
 * Format size file
 *
 * @param {[Integer]} filesize file size (byte)
 * @return {[String]}
 */
export function formatSize(filesize) {
  filesize = parseInt(filesize)
  if (filesize == null || !filesize) {
    return '0 B'
  }
  const unitArr = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
  let index = 0
  const srcsize = parseFloat(filesize)
  index = Math.floor(Math.log(srcsize) / Math.log(1024))
  let size = srcsize / Math.pow(1024, index)
  if (filesize < 1024 * 1024) {
    size = size.toFixed(0)
  } else {
    size = size.toFixed(2)
  }
  return size + ' ' + unitArr[index]
}
