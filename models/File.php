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
    public static $filenameTwigTemplate = '{{ fileModel._id }}-{{ now | date("U") }}.{{ extension }}'; // Used by models/behaviors/FileUploadBehaviour.php and can use the fileModel (this file model, including _id), extension (worked out by original filename or mimetype), md5 (the full md5 of the file contents) and md5DirectoryChars (first two characters of the MD5 hash, useful for storing in local folders which have max files per folder restrictions)
    public static $folderpathTwigTemplate = ''; // Used by models/behaviors/FileUploadBehaviour.php and also contains filename (the just worked out filename). Local filesystem Example: "{{md5DirectoryChars}}/"

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

}