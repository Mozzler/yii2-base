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
 * @property string $type
 * @property string $filename
 * @property string $filepath
 * @property string $mimeType
 * @property string $version
 * @property integer $size
 * @property array $other
 */
class File extends BaseModel
{
    protected static $collectionName = 'app.file';

    // Type is where the file is stored. See https://github.com/thephpleague/flysystem for more info
    public const TYPE_LOCAL = 'local'; // Local to the webserver
    public const TYPE_S3 = 'amazonS3';
    public const TYPE_NULL = 'null';
    public const TYPE_AZURE_BLOB = 'azureBlob';
    public const TYPE_MEMORY = 'memory';
    public const TYPE_PHPCR = 'PHPCR';
    public const TYPE_RACKSPACE = 'rackspace';
    public const TYPE_FTP = 'FTP';
    public const TYPE_SFTP = 'SFTP';
    public const TYPE_WEBDAV = 'webDAV';
    public const TYPE_ZIP = 'zip';
    public const TYPE_BACKBLAZE = 'backblaze';
    public const TYPE_DROPBOX = 'dropbox';
    public const TYPE_DATABASE = 'database'; // A generic entry
    public const TYPE_OTHER = 'other'; // For anything else not listed


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
            'originalFilename' => [
                'type' => 'Text',
                'label' => 'Original Filename',
            ],
            'filepath' => [
                'type' => 'Text',
                'label' => 'Filepath',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\CodeField',
                    ]
                ]
            ],
            'type' => [
                'type' => 'SingleSelect',
                'label' => 'Type',
                'options' => [
                    self::TYPE_LOCAL => 'Local',
                    self::TYPE_S3 => 'Amazon S3',
                    self::TYPE_NULL => 'Null',
                    self::TYPE_AZURE_BLOB => 'Azure Blob',
                    self::TYPE_MEMORY => 'Memory',
                    self::TYPE_PHPCR => 'PHPCR',
                    self::TYPE_RACKSPACE => 'Rackspace',
                    self::TYPE_FTP => 'FTP',
                    self::TYPE_SFTP => 'SFTP',
                    self::TYPE_WEBDAV => 'WebDAV',
                    self::TYPE_ZIP => 'Zip',
                    self::TYPE_BACKBLAZE => 'Backblaze',
                    self::TYPE_DROPBOX => 'Dropbox',
                    self::TYPE_DATABASE => 'Database',
                    self::TYPE_OTHER => 'Other',
                ]
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
                'type' => 'Text',
                'label' => 'Version Number',
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
        $scenarios[self::SCENARIO_CREATE] = ['filename', 'filepath', 'type', 'mimeType', 'size', 'version', 'other'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['filename', 'originalFilename', 'size', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['_id', 'filename', 'filepath', 'type', 'mimeType', 'size', 'version', 'other', 'createdUserId', 'updatedUserId', 'createdAt', 'updatedAt'];
        $scenarios[self::SCENARIO_SEARCH] = ['filename', 'originalFilename', 'type', 'mimeType', 'size'];

        return $scenarios;
    }


    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            // Save any previous file versions, esp for S3 saves, file updates, etc..
            'auditLog' => [
                'class' => AuditLogBehaviour::class,
            ],
            'fileUpload' => [
                'class' => FileUploadBehaviour::class
            ]
        ]);
    }

}