<?php

namespace mozzler\base\models;

use mozzler\base\components\Tools;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\behaviors\AuditLogBehaviour;
use mozzler\base\models\behaviors\FileUploadBehaviour;
use mozzler\base\models\behaviors\GarbageCollectionBehaviour;
use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * Class File
 *
 * @package mozzler\base\models
 *
 * @property string $originalFilename
 * @property string $filename required
 * @property string $filepath required
 * @property string $fieldName
 * @property string $modelType
 * @property string $mimeType
 * @property string $filesystemName
 * @property string $version
 * @property string $description
 * @property integer $size
 * @property array $other
 */
class File extends BaseModel
{
    protected static $collectionName = 'app.file';

    // If you want to override these twig templates it's expected you'll setup your own File model and in your config/common.php file setup something like
    //     'container' => [
    //        'definitions' => [
    //           '\mozzler\base\models\File' => [
    //             'class' => 'app\models\File',
    //     ]]],
    public $filenameTwigTemplate = '{{ fileModel._id }}-{{ now | date("U") }}.{{ extension }}'; // Used by models/behaviors/FileUploadBehaviour.php and can use the fileModel (this file model, including _id), extension (worked out by original filename or mimetype), fsName (name of the filesystem)
    public $folderpathTwigTemplate = ''; // Used by models/behaviors/FileUploadBehaviour.php and also contains filename (the just worked out filename). Local filesystem Example (using the first 2 chars of the MD5 hash): "{{ fileModel.other.md5[:2] }}/"
    public $defaultModelNamespace = 'app\models\\'; // Used by the $this->>workoutFilesystemName as we don't get this information
    public $defaultFs = 'fs'; // If you've not defined a File system in the File document and we can't determine one from the related model's fields then we use \Yii::$app->fs
    public $sanitiseFilename = true; // Used in the File Controller Download and models/behaviors/FileUploadBehaviour.php

    protected function modelConfig()
    {
        return [
            'label' => 'File',
            'labelPlural' => 'Files',
        ];
    }

    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
                'insert' => ['grant' => true],
                'update' => ['grant' => true],
                'find' => ['grant' => true],
                'view' => ['grant' => true],
                'delete' => ['grant' => true]
            ]
        ]);
    }

    public function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'createdAt' => [
                'columns' => ['createdAt' => 1],
            ],
            'filename' => [
                'columns' => ['namespace' => 1],
            ],
            'type' => [
                'columns' => ['type' => 1],
            ]
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'filename' => [
                'type' => 'Text',
                'label' => 'Filename',
                'required' => true,
            ],
            'filepath' => [
                'type' => 'Text',
                'label' => 'Filepath',
                'required' => true,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\CodeField',
                    ]
                ],
            ],
            'modelType' => [
                // The associated model (e.g user)
                'type' => 'Text',
                'label' => 'Model Type',
            ],
            'description' => [
                // Likely a user provided description
                'type' => 'Text',
                'label' => 'Description',
            ],
            'fieldName' => [
                // The associated model's field (e.g user.avatar)
                'type' => 'Text',
                'label' => 'Field Name',
            ],

            'originalFilename' => [
                'type' => 'Text',
                'label' => 'Original Filename',
            ],
            'mimeType' => [
                'type' => 'Text',
                'label' => 'MIME Type',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\CodeField',
                    ]
                ]
            ],
            'size' => [
                'type' => 'Integer',
                'label' => 'Size (in Bytes)',
            ],
            // Used for Amazon S3 uploads
            'version' => [
                // Normal S3 versions are long strings, not numbers
                'type' => 'Text',
                'label' => 'Version Number',
                'default' => '1' // NB: Has to be a string not integer otherwise this barfs
            ],
            'other' => [
                // In case you want to save anything else
                'type' => 'JsonArray',
                'label' => 'Other Information'
            ],
            'filesystemName' => [
                'type' => 'Text',
                'label' => 'Filesystem Name',
            ]
        ]);
    }

    public function scenarios()
    {

        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['filename', 'filepath', 'description', 'modelType', 'fieldName', 'mimeType', 'originalFilename', 'size', 'version', 'filesystemName', 'other'];
        $scenarios[self::SCENARIO_UPDATE] = ['originalFilename', 'description',]; // The original filename is used as what's sent to the browser on download
        $scenarios[self::SCENARIO_LIST] = ['filename', 'originalFilename', 'size', 'modelType', 'fieldName', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['_id', 'filename', 'description', 'filepath', 'mimeType', 'modelType', 'fieldName', 'originalFilename', 'size', 'filesystemName', 'other', 'version', 'createdUserId', 'updatedUserId', 'createdAt', 'updatedAt'];
        $scenarios[self::SCENARIO_SEARCH] = ['filename', 'description', 'originalFilename', 'filesystemName', 'mimeType', 'size', 'modelType', 'fieldName'];

        return $scenarios;
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            // Save any previous file versions, esp for S3 saves, file updates, etc..
            'auditLog' => [
                'class' => AuditLogBehaviour::class,
                'auditLogAttributes' => $this->scenarios()[self::SCENARIO_AUDITABLE],
                'skipUpdateOnClean' => true,
            ],
            'fileUpload' => [
                'class' => FileUploadBehaviour::class
            ]
        ]);
    }

    public function getFilesystem()
    {
        $fsName = $this->workoutFilesystemName();
        // -- Check the FileSystem has been defined
        if (!\Yii::$app->has($fsName)) {
            throw new BaseException("Unable to find the {$fsName} filesystem", 500, null, ['Developer note' => "In order to upload a file you need to define an {$fsName} filesystem in the config/common.php component see https://github.com/creocoder/yii2-flysystem for more information"]);
        }
        // Use the FlySystem that's been defined on the model
        // If you have defined a filesystem component using https://github.com/creocoder/yii2-flysystem
        \Yii::info("Using the $fsName Flysystem Filesystem defined");
        $this->filesystemName = $fsName;
        return \Yii::$app->$fsName;
    }


    /**
     * Example:   'other' => [
     * 'fieldName' => 'secondaryDocumentFile',
     * 'modelType' => 'Client',
     * 'md5' => 'c89d22cc8d1a150aa1cdd42a1d9bb237',
     * ],
     * @throws \yii\base\InvalidConfigException
     */
    private function workoutFilesystemName()
    {
        if (!empty($this->filesystemName)) {
            // Using what's been saved with the document
            return $this->filesystemName;
        }
        // ----------------------------------------------------------------
        //  Load the model field and check for a custom filesystemName
        // ----------------------------------------------------------------
        // If possible load up the field attributes and see if there's a custom filesystemName

        $fsName = $this->defaultFs; // If you've not defined a File system in the File document and we can't determine one from the related model's fields in a moment then we use \Yii::$app->fs
        $associatedModelField = $this->getAssociatedModelField();
        if (empty($associatedModelField)) {
            \Yii::warning("Invalid model class or field name so using $fsName for the filesystem");
            return $fsName = 'fs'; // If you've not defined a File system in the File document and we can't determine one from the related model's fields then we use \Yii::$app->fs
        }
        $filesystemName = ArrayHelper::getValue($associatedModelField, 'filesystemName');
        if (!empty($filesystemName)) {
            $this->filesystemName = $filesystemName;
            return $filesystemName;
        }
        return $fsName;
    }


    public function getAssociatedModelField()
    {
        $associatedModel = $this->createAssociatedModel();
        if (empty($associatedModel)) {
            return null;
        }
        $fieldName = empty($this->fieldName) ? ArrayHelper::getValue($this, 'other.fieldName') : $this->fieldName; // Allow the $this->other['fieldName'] backwards compatibility
        if (empty($fieldName)) {
            \Yii::warning("Unknown fieldName for {$this->ident()}");
            return null;
        }
        $modelFields = $associatedModel->getCachedModelFields();
        $modelField = ArrayHelper::getValue($modelFields, $fieldName);
        if (empty($modelField)) {
            \Yii::warning("Invalid fieldName {$fieldName} for {$this->ident()}");
        }
        return $modelField;
    }

    public function createAssociatedModel()
    {
        $modelType = empty($this->modelType) ? ArrayHelper::getValue($this, 'other.modelType') : $this->modelType; // Allow the $this->other['modelType'] backwards compatibility
        if (empty($modelType)) {
            \Yii::warning("No modelType set. So can't create an associate model for {$this->ident()}");
            return null;
        }

        $modelClass = $this->defaultModelNamespace . $modelType;
        $model = Tools::createModel($modelClass);
        if (empty($model)) {
            \Yii::warning("Invalid model class $modelClass, not sure how to create it. You might want to edit the defaultModelNamespace $this->defaultModelNamespace for {$this->ident()}");
            return null;
        }
        return $model;
    }

    // -- Example convert method, used by the FileUploadBehaviour
    // -- You'll want to extend or override this file model and add your own convert to use this
    //    public function convert($fileInfo) {
    //        if ($this->getExtension() === 'png') {
    //            // -- Here you would convert the file to JPG
    //        }
    //        // $file['tmp_name'] - This is the file that's later read and saved to the fs filesystem (e.g S3 or Google Cloud)
    //        return $fileInfo;
    //    }

    public function getSafeDescription()
    {
        if (empty($this->description)) {
            return '';
        }
        return $this->filterFilename($this->description);
    }

    /**
     * Filter Filename
     *
     * This makes the filename safe for most filesystems, in case you are using user input
     * It also defaults to trying to beautify the filename incase there's lots of repeated spaces or replacement chars
     * It still allows UTF8 characters
     * @param $filename string
     * @param bool $beautify
     * @return string
     *
     * Based on https://stackoverflow.com/a/42058764/1495634
     */
    public function filterFilename($filename, $beautify = true)
    {
        // Sanitize filename
        $filename = preg_replace(
            '~
        [<>:"/\\\|?*]|           // file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             // control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          // non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     // URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 // URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
            '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // optional beautification
        if ($beautify) {
            $filename = $this->beautifyFilename($filename);
        }
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $ext = mb_strtolower($ext, mb_detect_encoding($ext)); // Ensure lowercase extension
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
        return $filename;
    }

    /**
     * @param $filename
     * @return string
     *
     * Based on https://stackoverflow.com/a/42058764/1495634
     */
    public function beautifyFilename($filename, $lowercaseName = false)
    {
        // reduce consecutive characters
        $filename = preg_replace([
            // "file   name.zip" becomes "file-name.zip"
            '/ +/',
            // "file---name.zip" becomes "file-name.zip"
            '/-+/'
        ], '-', $filename);

        $filename = preg_replace([
            // "file___name.zip" becomes "file_name.zip"
            '/_+/',
        ], '_', $filename);
        $filename = preg_replace(array(
            // "file--.--.-.--name.zip" becomes "file.name.zip"
            '/-*\.-*/',
            // "file...name..zip" becomes "file.name.zip"
            '/\.{2,}/'
        ), '.', $filename);
        // You could also force lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        if ($lowercaseName) {
            $filename = mb_strtolower($filename, mb_detect_encoding($filename));
        }
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');
        return $filename;
    }

    /**
     * @param $originalFilename string
     * @return string file extension
     *
     * Based off vendor/yiisoft/yii2/web/UploadedFile.php
     * NB: Doesn't include the dot.
     *
     * So $file->getExtension() === 'jpg' not '.jpg'
     *
     */
    public function getExtension($originalFilename = null)
    {
        $mimeExtension = $this->mimeTypeToExtension($this->mimeType);
        if (!empty($mimeExtension)) {
            return $mimeExtension;
        }

        if (empty($originalFilename)) {
            $originalFilename = $this->originalFilename;
        }
        // If we can't determine the extension based on the mimeType we try to use the filename itself
        return strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
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
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
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
     * @param $fileInfo array
     * @param $newTmpFileLocation string e.g '/tmp/5f773fa7c5ff1f1ac1155d94.pdf'
     * @param null|string $newExtension e.g 'pdf'
     * @param null|string $newMimeType e.g 'application/pdf'
     * @return array
     *
     * @example $fileInfo = $this->convertSetNewFile($fileInfo, $this, $newFilename, $this->jpegExtension, $this->jpegMimeType); // Run inside a convert() method
     * // ------------------------------------------------------------------
     * //   Update the fileInfo with the new details
     * // ------------------------------------------------------------------
     */
    public function convertSetNewFile($fileInfo, $newTmpFileLocation, $newExtension = null, $newMimeType = null)
    {
        $originalTempFileLocation = $fileInfo['tmp_name'];
        $otherInfo = $this->other;
        $otherInfo['originalFilename'] = $this->originalFilename;; // Save the actual original name before the converting
        $this->other = $otherInfo;

        $fileInfo['tmp_name'] = $newTmpFileLocation; // The new file
        if (!empty($newMimeType)) {
            $this->mimeType = $newMimeType;
            $fileInfo['type'] = $newMimeType;
        }

        $this->originalFilename = $this->originalFilename . ($newExtension ? '.' . $newExtension : ''); // Simply append the new extension to the end, it'll be super obvious what the previous file was
        $this->size = filesize($newTmpFileLocation);
        @unlink($originalTempFileLocation); // Delete the original temporary file as cleanup
        \Yii::info("Converted {$this->ident()} to point to $newTmpFileLocation");
        return $fileInfo;
    }


    /**
     * @param $fileModel
     * @param $fileId
     * @return string
     *
     * Returns the string output, aiming for the original filename, the filename or the fileId if needed
     */
    public function getFileValue()
    {
        if ($this->originalFilename) {
            return $this->originalFilename;
        }
        if ($this->filename) {
            return $this->filename;
        }
        return $this->getId();
    }

    public function getFileDownloadSizeReadable($sizeInBytes = null)
    {
        if (is_null($sizeInBytes)) {
            $sizeInBytes = $this->size;
        }
        if (!$sizeInBytes) {
            return 'Empty';
        }

        if ($sizeInBytes >= 1073741824) {
            return number_format($sizeInBytes / 1024 / 1024 / 1024, 2) . ' GB';
        } elseif ($sizeInBytes >= 1048576) {
            return number_format($sizeInBytes / 1024 / 1024, 2) . ' MB';
        } elseif ($sizeInBytes >= 1024) {
            return number_format($sizeInBytes / 1024, 2) . ' KB';
        } else {
            return $sizeInBytes . ' bytes';
        }
    }

}
