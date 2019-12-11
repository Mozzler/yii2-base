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
                'auditLogAttributes' => $this->scenarios()[self::SCENARIO_AUDITABLE],
                'skipUpdateOnClean' => true,
            ],
            'fileUpload' => [
                'class' => FileUploadBehaviour::class
            ]
        ]);
    }

}