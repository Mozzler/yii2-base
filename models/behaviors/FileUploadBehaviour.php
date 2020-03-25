<?php

namespace mozzler\base\models\behaviors;

use MongoDB\BSON\ObjectId;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\File;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use League\Flysystem\AdapterInterface;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class FileUploadBehaviour extends Behavior
{
    /** @var string $filesystemComponentName */
    public $filesystemComponentName = 'fs';

    /** @var bool $visibilityPrivate */
    public $visibilityPrivate = true;

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'uploadFile',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'uploadFile',
            BaseActiveRecord::EVENT_BEFORE_DELETE => 'deleteFile',
        ];
    }

    /**
     * Deal with the file saving
     *
     * You can replace this with a different behaviour to do more fancy file saving if you want.
     * @param $event
     * @return bool
     * @throws BaseException
     */
    public function uploadFile($event)
    {
        /** @var File $fileModel */
        $fileModel = $this->owner;

        $fileModel->_id = new ObjectId(); // Create a new model ID in case you want to use that in the filename
        // -- Basic file validation checks
        // Example $file = {"name":"!!72484913_10156718971467828_53539529008611328_n.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpQa226D","error":0,"size":35420}
        $file = self::getFileInfo();
        if (!empty($file)) {
            \Yii::debug("The file information is: " . json_encode($file));
        } else {
            \Yii::error("No file uploaded" . json_encode(['Error' => 'No valid $_FILES info defined', '_FILES' => $_FILES, '$file' => $file]));
            return false;
        }
        if (!is_file($file['tmp_name'])) {
            throw new BaseException("Unable to find the uploaded file", 500, null, ['Developer note' => "The temporary file {$file['tmp_name']} could not be found", 'file' => $file]);
        }

        // -- Check the FileSystem has been defined
        if (!\Yii::$app->has($this->filesystemComponentName)) {
            throw new BaseException("Unable to find the {$this->filesystemComponentName} filesystem", 500, null, ['Developer note' => "In order to upload a file you need to define an {$this->filesystemComponentName} filesystem in the config/common.php component see https://github.com/creocoder/yii2-flysystem for more information"]);
        }
        // Use the FlySystem that's been defined
        $fsName = $this->filesystemComponentName;
        $fs = \Yii::$app->$fsName;
        // If you have defined a filesystem component using https://github.com/creocoder/yii2-flysystem
        \Yii::info("Using the $fsName Flysystem Filesystem you've defined");

        // ----------------------------------
        //   Prepare the file
        // ----------------------------------
        $extension = $this->getExtension($file['name'], $fileModel);
        $twigData = ['fileModel' => $fileModel, 'extension' => $extension, 'fsName' => $fsName];

        $filename = \Yii::$app->t::renderTwig($fileModel::$filenameTwigTemplate, $twigData);
        $twigData['filename'] = $filename;
        $folderpath = \Yii::$app->t::renderTwig($fileModel::$folderpathTwigTemplate, $twigData);
        \Yii::debug("Creating the directory: {$folderpath} (the directory could already exist) with the filename being {$filename}");
        $fs->createDir($folderpath); // Creating it
        $visibilty = $this->visibilityPrivate ? AdapterInterface::VISIBILITY_PRIVATE : AdapterInterface::VISIBILITY_PUBLIC; // Defaults to Private

        $filepath = $folderpath . $filename;
        $exists = $fs->has($filepath);
        if (!$exists) {
            // ----------------------------------
            //   Save the file
            // ----------------------------------
            $stream = fopen($file['tmp_name'], 'r+');
            $fs->writeStream($filepath, $stream, ['visibility' => $visibilty]); // Save to the filesystem (locally, Amazon S3... Whatever you've defined)
        } else {
            // This is a duplicate file
            \Yii::warning("This is a duplicate file, you've already uploaded {$filepath}");
        }

        // ----------------------------------
        //   Save the File fields
        // ----------------------------------
        $fileModel->filename = $filename;
        $fileModel->filepath = $filepath; // The filepath is the full location

        return true;
    }

    public function deleteFile($event)
    {
        /** @var File $fileModel */
        $fileModel = $this->owner;

        // -- Check the FileSystem has been defined
        if (!\Yii::$app->has($this->filesystemComponentName)) {
            throw new BaseException("Unable to find the {$this->filesystemComponentName} filesystem", 500, null, ['Developer note' => "In order to upload a file you need to define an {$this->filesystemComponentName} filesystem in the config/common.php component see https://github.com/creocoder/yii2-flysystem for more information"]);
        }

        // Use the FlySystem that's been defined
        $fsName = $this->filesystemComponentName;
        $fs = \Yii::$app->$fsName;


        $exists = $fs->has($fileModel->filepath);
        if ($exists) {
            $deleted = $fs->delete($fileModel->filepath);
            \Yii::error("Deleted file {$fileModel->filepath} based on the File ID: {$fileModel->getId()}");
            // Note: There could be other file documents pointing to the same file (multiple uploads of the same file).
            // Might need to delete the other duplicate File entries? (If so, trigger that in the FileController actionDelete method, not here)
            return $deleted;
        } else {
            \Yii::error("Can't find so thus can't delete {$fileModel->filepath}");
        }
        return false; // Doesn't stop the processing
    }


    /**
     * @param $originalFilename
     * @return string original file base name
     *
     * Based off vendor/yiisoft/yii2/web/UploadedFile.php
     */
    public function getBaseName($originalFilename)
    {
        // https://github.com/yiisoft/yii2/issues/11012
        $pathInfo = pathinfo('_' . $originalFilename, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo, '8bit'), '8bit');
    }

    /**
     * @param $originalFilename string
     * @param $fileModel File
     * @return string file extension
     *
     * Based off vendor/yiisoft/yii2/web/UploadedFile.php
     */
    public function getExtension($originalFilename, $fileModel)
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        // If we can't determine the extension based on the filename itself, we try to use the mimeType
        return empty($extension) ? $this->mimeTypeToExtension($fileModel->mimeType) : $extension;
    }

    /**
     * @param $mime
     * @return bool|mixed
     * Copied from https://stackoverflow.com/a/53662733/7299352
     */
    public function mimeTypeToExtension($mime)
    {
        $mime_map = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh',
        ];

        return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
    }

    /**
     * Get File Info
     *
     * Only expecting a single file response
     * @return array|bool
     *
     * Example response: {"modelType":"Client","name":"!Ikigai - A reason for Being.jpg","type":"image\/jpeg","tmp_name":"\/tmp\/phpuLPa2S","error":0,"size":109927,"fieldName":"driversLicenceFile"}
     */
    public static function getFileInfo()
    {
        // Example $_FILES = {"Client":{"name":{"driversLicenceFile":"!!72484913_10156718971467828_53539529008611328_n.jpg"},"type":{"driversLicenceFile":"image\/jpeg"},"tmp_name":{"driversLicenceFile":"\/tmp\/phpmypYid"},"error":{"driversLicenceFile":0},"size":{"driversLicenceFile":35420}}}
        if (empty($_FILES)) {
            return false;
        }
        $modelType = key($_FILES);
        $file = ['modelType' => $modelType];
        foreach ($_FILES[$modelType] as $fieldField => $infoEntry) {

            $fieldName = key($infoEntry);
            // e.g name = image.jpg
            $file[$fieldField] = $infoEntry[$fieldName];

        };
        $file['fieldName'] = isset($fieldName) ? $fieldName : null;
        return $file;
    }

}
