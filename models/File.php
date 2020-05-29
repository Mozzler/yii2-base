<?php

namespace mozzler\base\models;

use mozzler\base\models\behaviors\AuditLogBehaviour;
use mozzler\base\models\behaviors\FileUploadBehaviour;
use mozzler\base\models\behaviors\GarbageCollectionBehaviour;
use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Class File
 *
 * @package mozzler\base\models
 *
 * @property string $originalFilename
 * @property string $filename required
 * @property string $filepath required
 * @property string $mimeType
 * @property string $version
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
    public static $filenameTwigTemplate = '{{ fileModel._id }}-{{ now | date("U") }}.{{ extension }}'; // Used by models/behaviors/FileUploadBehaviour.php and can use the fileModel (this file model, including _id), extension (worked out by original filename or mimetype), fsName (name of the filesystem)
    public static $folderpathTwigTemplate = ''; // Used by models/behaviors/FileUploadBehaviour.php and also contains filename (the just worked out filename). Local filesystem Example (using the first 2 chars of the MD5 hash): "{{ fileModel.other.md5[:2] }}/"

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
                'default' => "1" // NB: Has to be a string not integer otherwise this barfs
            ],
            'other' => [
                // In case you want to save anything else
                'type' => 'Json',
                'label' => 'Other Information'
            ],
        ]);
    }

    public function scenarios()
    {

        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['filename', 'filepath', 'mimeType', 'originalFilename', 'size', 'version'];
        $scenarios[self::SCENARIO_UPDATE] = ['originalFilename']; // The original filename is used as what's sent to the browser on download
        $scenarios[self::SCENARIO_LIST] = ['filename', 'originalFilename', 'size', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['_id', 'filename', 'filepath', 'mimeType', 'originalFilename', 'size', 'other', 'version', 'createdUserId', 'updatedUserId', 'createdAt', 'updatedAt'];
        $scenarios[self::SCENARIO_SEARCH] = ['filename', 'originalFilename', 'mimeType', 'size'];

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



    // -- Example convert method, used by the FileUploadBehaviour
    // -- You'll want to extend or override this file model and add your own convert to use this
    //    public function convert($fileInfo) {
    //        if ($this->getExtension() === 'png') {
    //            // -- Here you would convert the file to JPG
    //        }
    //        return $this;
    //    }


    /**
     * @param $originalFilename string
     * @return string file extension
     *
     * Based off vendor/yiisoft/yii2/web/UploadedFile.php
     */
    public function getExtension($originalFilename = null)
    {
        if (empty($originalFilename)) {
            $originalFilename = $this->originalFilename;
        }
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        // If we can't determine the extension based on the filename itself, we try to use the mimeType
        return empty($extension) ? $this->mimeTypeToExtension($this->mimeType) : $extension;
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

}