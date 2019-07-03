<?php
/**
 * vue-element-uploader php demo
 * This example is only a sample of the process and can be used as a reference. It should not be used directly in production project.
 *
 * @auth DarkWinoom <archzars@vip.qq.com>
 */
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, Content-Type, Cookie, X-Token"); // Cross-domain header

class uploader
{

    private $temporaryFolder;
    private $maxFileSize = '';
    private $fileParameterName;
    private $uploadUrl;

    function __construct($fileParameterName, $temporaryFolder)
    {
        $this->fileParameterName = $fileParameterName;
        $this->temporaryFolder = dirname(__FILE__) . DIRECTORY_SEPARATOR . $temporaryFolder;
        $_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
        $domain = $_SERVER['HTTP_HOST'] . (($_root == '/' || $_root == '\\') ? '' : $_root);
        if (!empty($_SERVER['HTTPS'])) {
            $this->uploadUrl = 'https://' . $domain;
        } else {
            $this->uploadUrl = 'http://' . $domain;
        }
        // Compose the upload http url
        $this->uploadUrl .= '/' . $temporaryFolder . '/';
    }

    private function cleanIdentifier($identifier)
    {
        return preg_replace("/[^0-9A-Za-z_-]/U", '', $identifier);
    }

    private function getChunkFile($chunkNumber, $identifier)
    {
        // Clean up the identifier
        $identifier = $this->cleanIdentifier($identifier);
        // What would the file name be?
        return $this->temporaryFolder . DIRECTORY_SEPARATOR . 'chunk' . DIRECTORY_SEPARATOR . 'uploader-' . $identifier . '.' . $chunkNumber;
    }

    private function validateRequest($chunkNumber, $chunkSize, $totalSize, $identifier, $filename, $fileSize = 0)
    {
        // Clean up the identifier
        $identifier = $this->cleanIdentifier($identifier);

        // Check if the request is sane
        if ($chunkNumber == 0 || $chunkSize == 0 || $totalSize == 0 || !$identifier || !$filename) {
            return 'non_uploader_request';
        }
        $numberOfChunks = max(floor($totalSize / ($chunkSize * 1.0)), 1);
        if ($chunkNumber > $numberOfChunks) {
            return 'invalid_uploader_request: out of chunks';
        }

        // Is the file too big?
        if ($this->maxFileSize && $totalSize > $this->maxFileSize) {
            return 'invalid_uploader_request: size too big';
        }

        if (!empty($fileSize)) {
            if ($chunkNumber < $numberOfChunks && $fileSize != $chunkSize) {
                // The chunk in the POST request isn't the correct size
                return 'invalid_uploader_request: size not correct';
            }
            if ($numberOfChunks > 1 && $chunkNumber == $numberOfChunks && $fileSize != (($totalSize % $chunkSize) + (int)$chunkSize)) {
                // The chunks in the POST is the last one, and the fil is not the correct size
                return 'invalid_uploader_request: chunks number error';
            }
            if ($numberOfChunks == 1 && $fileSize != $totalSize) {
                // The file is only a single chunk, and the data size does not fit
                return 'invalid_uploader_request: chunk size error';
            }
        }

        return 'valid';
    }

    public function file_exists($identifier, $filename)
    {
        // Determine if the file exists
        $path_info = pathinfo($filename);
        $extension = '.' . strtolower($path_info['extension']);
        $hash_name = $identifier . $extension;
        return file_exists($this->temporaryFolder . DIRECTORY_SEPARATOR . $hash_name);
    }

    public function uploaded_chunks($identifier, $totalChunks)
    {
        // Check which chunks have been uploaded
        // This detection will be executed once in the first get request and every chunk post.
        // You can consider using some caching mechanism to speed up the processing (such as database, redis, etc.)
        $uploaded = array();
        for ($currentTestChunk = 1; $currentTestChunk <= $totalChunks; $currentTestChunk++) {
            // Recursion
            if (file_exists($this->getChunkFile($currentTestChunk, $identifier))) {
                $uploaded[] = $currentTestChunk;
            }
        }
        return $uploaded;
    }

    public function upload()
    {
        // Receive files
        $chunkNumber = post('chunkNumber');
        $totalChunks = post('totalChunks');
        $chunkSize = post('chunkSize');
        $totalSize = post('totalSize');
        $identifier = $this->cleanIdentifier(post('identifier'));
        $filename = post('filename');

        if (!$_FILES[$this->fileParameterName] || !$_FILES[$this->fileParameterName]['size']) {
            return array(
                'code' => 10000,
                'data' => 'invalid_uploader_request: file not found'
            );
        }

        $validation = $this->validateRequest($chunkNumber, $chunkSize, $totalSize, $identifier, $filename, $_FILES[$this->fileParameterName]['size']);
        if ($validation == 'valid') {
            $chunkFile = $this->getChunkFile($chunkNumber, $identifier);

            // Save the chunk (TODO: OVERWRITE)
            if (rename($_FILES[$this->fileParameterName]['tmp_name'], $chunkFile)) {
                // Do we have all the chunks?
                if ($totalChunks == 1) {
                    // if there only one chunk, directly merge
                    return $this->merge($identifier, $filename, $totalChunks);
                } else {
                    return array(
                        'code' => 20000,
                        'data' => 'chunk ' . $chunkNumber . ' upload success'
                    );
                }
            } else {
                return array(
                    'code' => 10000,
                    'data' => 'save_file_fail: the directory could not be write'
                );
            }
        } else {
            return $validation;
        }
    }

    public function merge($identifier, $filename, $totalChunks)
    {
        // Merge Files
        $path_info = pathinfo($filename);
        $extension = '.' . strtolower($path_info['extension']);
        $hash_name = $identifier . $extension;
        if (!$out = @fopen($this->temporaryFolder . DIRECTORY_SEPARATOR . $hash_name, "wb")) {
            return array(
                'code' => 10000,
                'data' => 'open_file_fail: the chunk file could not be open'
            );
        }
        if (flock($out, LOCK_EX)) {
            for ($index = 1; $index <= $totalChunks; $index++) {
                $file_part = $this->getChunkFile($index, $identifier);
                if (file_exists($file_part)) {
                    $in = fopen($file_part, 'rb');
                    $content = fread($in, filesize($file_part));
                    fwrite($out, $content);
                    // if chunk size is too big, use the following instead:
                    /*while ($buff = fread($in, 2048)) {
                        fwrite($out, $buff);
                    }*/
                    fclose($in);
                } else {
                    return array(
                        'code' => 10000,
                        'data' => 'open_file_fail: the chunk ' . $index . ' is not exists'
                    );
                }
            }
            flock($out, LOCK_UN);
        }
        @fclose($out);
        // Clear the merged chunks
        for ($index = 1; $index <= $totalChunks; $index++) {
            $file_part = $this->getChunkFile($index, $identifier);
            if (file_exists($file_part)) {
                unlink($file_part);
            }
        }
        return array(
            'code' => 20000,
            'data' => $this->get_file($identifier, $filename)
        );
    }

    public function get_file($identifier, $filename)
    {
        // Get file information (should in the database)
        // Compare and return the files stored in the database with $identifier (here for the demo)

        // The spark-md5 calculated by the file is consistent, when the same file with different suffixes
        // This case should be treated as two files (even if one of them is not working properly)
        // There are several workarounds you can consider:
        // 1.Not using spark-md5 to calculated the file, the default identifier will take the file name (including the suffix) as the seed.
        // 2.Setting the 'identifier' not unique in the database, and useing the identifier and file suffix to get the file information.
        // 3.Not save the file suffixes, generate it when someone get
        $path_info = pathinfo($filename);
        $extension = '.' . strtolower($path_info['extension']);
        $hash_name = $identifier . $extension;
        return array(
            'id' => rand(1, 99999), // ID is created by database
            'identifier' => $identifier,
            'name' => $filename, // Original file name
            'link' => $this->uploadUrl . $hash_name // File http url address
        );
    }

}

$uploader = new uploader('upload','upload');

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Upload
    if(post('merge')){
        $return = $uploader->merge(post('identifier'),post('filename'),post('totalChunks'));
    } else {
        $return = $uploader->upload();
    }
    if($return['code'] !== 20000){
        // Wrong
        http_code(500);
    }else{
        // Correct
        http_code(200);
    }
    echo json_encode($return);
}elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($uploader->file_exists($_GET['identifier'], $_GET['filename'])) {
        // The file is exists, Return file information directly
        http_code(202);
        $data = $uploader->get_file($_GET['identifier'], $_GET['filename']);
    } else {
        // The file is no exists, doing upload
        http_code(201);
        $data = array(
            'id' => 0,
            'identifier' => $_GET['identifier'],
            'name' => $_GET['filename'],
            'link' => '',
            'skipChunks' => $uploader->uploaded_chunks($_GET['identifier'], $_GET['totalChunks']) // the chunks witch has been uploaded
        );
        // You should throw some of de chunks, to make sure the chunks is complete
        // Number of files based on 'simultaneousUploads'(a config of simple-uploader.js, default is 3)
        if ($data['skipChunks']) {
            sort($data['skipChunks']);
            array_splice($data['skipChunks'], -3);
        }
    }
    echo json_encode(array(
        'code' => 20000,
        'data' => $data,
    ));
}

function post($var,$default = '')
{
    // Get post
    if (!empty($_POST[$var])) {
        return $_POST[$var];
    } else {
        $steam = json_decode(file_get_contents("php://input"), TRUE);
        return !empty($steam[$var]) ? $steam[$var] : $default;
    }
}

function http_code($num)
{
    // 200, 201, 202: The current chunk upload is successful and does not need to be retransmitted.
    // 404, 415. 500, 501: The current chunk upload fails, the entire file upload will be canceled.
    $http = array(
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        404 => "HTTP/1.1 404 Not Found",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
    );

    header($http[$num]);
}