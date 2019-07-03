# vue-element-uploader
> 基于Vue.js和element-ui构建的上传组件，支持快传、断点续传、图片裁剪等功能

## 预览
![demo](/demo.gif)

## 功能亮点
* 支持单一文件上传或多文件队列上传
* 文件大小与格式限制
* 上传进度、上传速度、剩余时间预估，队列简洁、明了
* 点击图片预览（viewerjs）
* 上传前图片裁剪与还原（vue-cropper）
* 支持文件指纹（spark-md5）
* 大文件自动分块上传
* 支持断点续传与快传（需要后端支持）
* 只需进行简单的配置即可使用

## 安装
```bash
# 克隆项目
git clone https://github.com/DarkWinoom/vue-element-uploader.git

# 进入项目目录
cd vue-element-uploader

# 安装依赖
npm install

# 建议不要直接使用 cnpm 安装依赖，会有各种诡异的 bug。可以通过如下操作解决 npm 下载速度慢的问题
npm install --registry=https://registry.npm.taobao.org

# 启动服务
npm run dev
```
浏览器访问 http://localhost:8080

## 使用
正在整理中

## API 文档
#### configuration
* ```field``` 上传文件时文件的参数名 (默认: ```upload```)
* ```target``` 目标上传 URL (默认: ```/```)
* ```headers``` 额外的一些请求头，如果是函数的话，则会传入 ```Uploader.File``` 实例、当前块 ```Uploader.Chunk``` 以及是否是测试模式 (默认: ```{}```)
* ```queue``` 其他额外的参数，这个可以是一个对象或者是一个函数，如果是函数的话，则会传入 ```Uploader.File``` 实例、当前块 ```Uploader.Chunk``` 以及是否是测试模式 (默认: ```{}```)
* ```fastTransfer``` 是否开启快传和断点续传支持，若服务器不支持这两个功能可以将其关闭。可以设为 ```auto```，这样将会提供一个按钮让用户自行抉择是否开启或关闭 (默认: ```auto```)
* ```queueLimit``` 队列中允许最大文件数量，包括已上传成功或失败的文件，设为 ```0``` 表示不限 (默认: ```0```)
* ```sizeLimit``` 队列中单个文件允许最大字节数，设为 ```0``` 表示不限 (默认: ```0```)
* ```typeLimit``` 队列中允许的文件类型（数组），可以设为文件后缀或者 ```image```、```video```、```audio```中的一个，例如：```['image','pdf','zip']``` (默认: ```[]```)
* ```cropOpen``` 是否开启图片裁剪功能，该功能会自动识别列表中的图片文件 (默认: ```true```)
* ```cropWidth``` 默认裁剪框的宽度 (默认: ```80% 父容器宽度```)
* ```cropHeight``` 默认裁剪框的高度 (默认: ```80% 父容器高度```)
* ```cropFixed``` The screenshot frame control, you can set ```true```(Fixed size, can't change), ```[width, height]```(fixed ratio (such as ```[1, 1]```)), ```false``` (Not fixed size, free to change). (Default: ```false```)
* ```cropOutputQuantity``` 裁剪生成图片质量 (取值范围: ```0.1 - 1```，默认: ```1```)
* ```cropOutputType``` 裁剪生成图片格式，可选值```jpg```、```png```、```webp``` (默认: ```png```)
* ```emptyOnComplete``` 上传成功后是否清空列表 (默认: ```false```)
* ```sparkUnique``` 使用spark-md5计算值来代替uniqueIdentifier，在添加文件时将会自动计算，对于大体积文件需要花费一定时间（同时在计算时可能会造成浏览器卡顿），通过计算后，将会更加准确的触发快传和断点续传 (默认: ```true```)
* ```dialogVisible``` 外部控件显示与否，用于更新文件状态，使用模态框显示时需要将该参数绑定其visible属性，不使用时忽略 (默认: ```false```)
* ```lang``` 使用的语言包，当前支持 ```zh-cn``` 和 ```en``` 两种。设定为 ```auto```时将会自动识别访问者当前系统语言，不支持该语言时将会显示英语 (默认: ```auto```)
#### method
* ```complete(message)``` 当列表中所有文件上传成功时调用
  * ```message``` 以数组的形式返回服务器数据

## 服务器处理
返回格式为```json```，```code```是自定义的状态码（本项目不使用），```data```为返回的数据，可以通过设置http状态码来告诉插件是否执行成功
* ```200``` ```201``` ```202``` 响应式成功的响应码
* ```404``` ```415``` ```500``` ```501``` 出错的响应码，会取消整个文件上传并给出提示。

服务器处理文件上传需要执行三步：
* 通过一个```GET```请求来判断该文件是否已经上传过，若已经上传过可以直接返回文件信息，这样将会略过整个文件上传过程（快传）；同时也可以返回哪些块已经上传成功，这样在上传时将会略过这些块（断点续传）。配置文件```fastTransfer```设置为```false```时该请求不会发送

  请求GET参数如下：
  * ```chunkNumber``` 当前块的次序，第一个块是 1，注意不是从 0 开始的
  * ```totalChunks``` 文件被分成块的总数
  * ```chunkSize``` 分块大小，根据 ```totalSize``` 和这个值你就可以计算出总共的块数。注意最后一块的大小可能会比这个要大
  * ```currentChunkSize``` 当前块的大小，实际大小
  * ```totalSize``` 文件总大小
  * ```identifier``` 这个就是每个文件的唯一标示
  * ```filename``` 文件名
  * ```relativePath``` 文件夹上传的时候文件的相对路径属性

  返回```data```如下:
  * ```id``` 存取文件的数据库对应ID，如果文件不存在（未上传过）该值应该设置为```0```
  * ```identifier``` 文件的identifier，可以直接返回GET参数
  * ```name``` 文件的原文件名，可以直接返回GET参数
  * ```link``` 文件的http访问地址，如果文件不存在请留空
  * ```skipChunks``` 以数组的形式返回哪些块已经上传过，插件将会跳过这些已上传的块继续上传
* 单个或多个```POST```请求上传文件块（请求数取决于文件的分块数），请求的参数与```GET```请求一致（类型是```POST```），上传失败时响应出错码将会取消整个文件上传并给出提示（通过返回的```data```字符串内容）。若文件只有一个块，需要直接处理并返回以下```data```：
  * ```id``` 存取文件的数据库对应ID
  * ```identifier``` 文件的identifier，可以直接返回GET参数
  * ```name``` 文件的原文件名，可以直接返回GET参数
  * ```link``` 文件的http访问地址
* 当所有块上传成功时，将会发送一个```POST```请求用于将块合成并删除单个的块文件，需要注意如果文件只有一个块，则该请求不会执行

  请求POST参数如下：
  * ```totalChunks``` 文件被分成块的总数
  * ```identifier``` 这个就是每个文件的唯一标示
  * ```filename``` 文件名
  * ```merge``` 表示该次请求是 merge 请求

  返回```data```如下：
  * ```id``` 存取文件的数据库对应ID
  * ```identifier``` 文件的identifier，可以直接返回GET参数
  * ```name``` 文件的原文件名，可以直接返回GET参数
  * ```link``` 文件的http访问地址


[php示例](https://github.com/DarkWinoom/vue-element-uploader/blob/master/example/uploader.php)

## 多语言支持
本项目使用 ```vue-i18n``` 管理多语言，目前支持**简体中文**和**英文**

你可以自行添加或修改语言包，存放目录```src/lang```

## Reference
* vue
* element-ui
* axios
* simple-uploader.js
* spark-md5
* vue-cropper
* viewerjs
* vue-i18n

## License

[MIT](https://github.com/DarkWinoom/vue-element-uploader/blob/master/LICENSE) license.