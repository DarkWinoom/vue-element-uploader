# vue-element-uploader
> A Vue.js & element-ui upload and image crop component

[中文](https://github.com/DarkWinoom/vue-element-uploader/blob/master/README_zh-CN.md)

## Preview
![demo](/demo.gif)

## Features
* Allow single file or multiple files
* File validation
* Queue management
* Preview a image file
* Crop a image file
* Recover a Cropped file
* Chunk uploads
* Recover upload and Fast file transfer
* Easy to use

## How to use?
```bash
# clone the project
git clone https://github.com/DarkWinoom/vue-element-uploader.git

# enter the project directory
cd vue-element-uploader

# install dependency
npm install

# develop
npm run dev
```
This will automatically open http://localhost:8080

## Usage
coming soon

## API Documentation
#### configuration
* ```field``` The name of the multipart POST parameter to use for the file chunk. (Default: ```upload```)
* ```target``` The target URL for the multipart POST request. (Default: ```/```)
* ```headers``` Extra headers to include in the multipart POST with data. If a function, it will be passed a ```Uploader.File```, a ```Uploader.Chunk``` object and a isTest boolean. (Default: ```{}```)
* ```queue``` Extra parameters to include in the multipart POST with data. This can be an object or a function. If a function, it will be passed a Uploader.File, a Uploader.Chunk object and a isTest boolean. (Default: ```{}```)
* ```fastTransfer``` Whether to enable the fast transfer and breakpoint resume mode, if the backend does not support this feature, you can set ```false``` to close, or to let user choice by setting ```auto```. (Default: ```auto```)
* ```queueLimit``` The maximum number of files allowed in the queue, set ```0``` to not limited. (Default: ```0```)
* ```sizeLimit``` The maximum single file size allowed in the queue, set ```0``` to not limited. (Default: ```0```)
* ```typeLimit``` The file type allowed in the queue (```array```), you can set file suffix or ```image```, ```video``` and ```audio```, such as ```['image','pdf','zip']```. (Default: ```[]```)
* ```cropOpen``` If set ```true```, will allow crop a image file in th queue. (Default: ```true```)
* ```cropWidth``` Default generation of screenshot frame width. (Default: ```80% of parent container width```)
* ```cropHeight``` Default generation of screenshot frame height. (Default: ```80% of parent container height```)
* ```cropFixed``` The screenshot frame control, you can set ```true```(Fixed size, can't change), ```[width, height]```(fixed ratio (such as ```[1, 1]```)), ```false``` (Not fixed size, free to change). (Default: ```false```)
* ```cropOutputQuantity``` Crop the quality of the generated image. (Range: ```0.1 - 1```, Default: ```1```)
* ```cropOutputType``` Crop the format of the generated image, you can set ```jpg```, ```png``` or ```webp```. (Default: ```png```)
* ```emptyOnComplete``` Clear the list after upload completed. (Default: ```false```)
* ```sparkUnique``` Using spark-md5 instead of uniqueIdentifier. The calculation will run automatically when the file added, it takes a certain amount of time (depending on file size). This will make the file unique more precise. (Default: ```true```)
* ```dialogVisible``` When you use dialog to show this components. Make sure this prop sync with the dialog visible value. (Default: ```false```)
* ```lang``` Using language pack, now support ```zh-cn``` and ```en```, when you set to ```auto```, the current language will according to visitor's local language. (Default: ```auto```)
#### method
* ```complete(message)``` Call when all of the queue list files has been upload success.
  * ```message``` an array of all queue list success response. Cames from server.

## Server processing
The response type is ```json```, contains ```code``` and ```data```, you can set http code to tell the component this request is success or not:
* ```200``` ```201``` ```202``` The chunk was accepted and correct.
* ```404``` ```415``` ```500``` ```501``` The file for which the chunk was uploaded is not supported, cancel the entire upload.

There are three step to processing the upload in server:
* a ```GET``` request to check this file has been uploaded or not, you can also return the uploaded chunks number to let the component abort it. (the request will abort when you set ```fastTransfer``` to ```false```)
  
  GET param:
  * ```chunkNumber``` The index of the chunk in the current upload. First chunk is 1 (no base-0 counting here).
  * ```totalChunks``` The total number of chunks.
  * ```chunkSize``` The general chunk size. Using this value and totalSize you can calculate the total number of chunks. Please note that the size of the data received in the HTTP might be lower than chunkSize of this for the last chunk for a file.
  * ```totalSize``` The total file size.
  * ```identifier``` A unique identifier for the file contained in the request.
  * ```filename``` The original file name (since a bug in Firefox results in the file name not being transmitted in chunk multipart posts).
  * ```relativePath``` The file's relative path when selecting a directory (defaults to file name in all browsers except Chrome).

  return ```data```:
  * ```id``` ID from database saven. If the file is no exist, you should set it ```0```.
  * ```identifier``` File's identifier, it's may come from get param.
  * ```name``` File's original name, it's may come from get param.
  * ```link``` File http access address, Put it empty if the file is no exist.
  * ```skipChunks``` the chunks witch has been uploaded.
* one or more than one ```POST``` request to upload the chunks of file, you can save them directly, the number of request based on the file chunks number. When the number of chunks is only one, you should return the file information like this: 
  * ```id``` ID from database saven.
  * ```identifier``` File's identifier, it's may come from get param.
  * ```name``` File's original name, it's may come from get param.
  * ```link``` File http access address.
* a ```POST``` request let you merge chunks into a file and delete them. Once the file only have one chunk, this reuqest will not make. 

  POST param: 
  * ```totalChunks``` The total number of chunks.
  * ```identifier``` A unique identifier for the file contained in the request.
  * ```filename``` The original file name (since a bug in Firefox results in the file name not being transmitted in chunk multipart posts).
  * ```merge``` Indicates that the request is a merge request.

  return ```data```:
  * ```id``` ID from database saven.
  * ```identifier``` File's identifier, it's may come from get param.
  * ```name``` File's original name, it's may come from get param.
  * ```link``` File http access address.

[php example](https://github.com/DarkWinoom/vue-element-uploader/blob/master/example/uploader.php)

## Language
This component using ```vue-i18n``` to manage language pack, now support ***Simplified Chinese*** and ***English***.
You can put your onw language in directory ```src/lang```


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