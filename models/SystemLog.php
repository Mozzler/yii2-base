<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Class SystemLog
 * @package mozzler\base\models
 *
 * @property string $type
 * @property string $message
 * @property array $request
 * @property array $data
 * @property string $category
 */
class SystemLog extends BaseModel
{
    protected static $collectionName = 'app.systemLog';
    public const TYPE_DEBUG = 'debug';
    public const TYPE_TRACE = 'trace';
    public const TYPE_PROFILE = 'profile';
    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';
    public const TYPE_EXCEPTION = 'exception';

    protected function modelConfig()
    {
        return [
            'label' => 'System Log',
            'labelPlural' => 'Sys Log Entries',
        ];
    }

    public function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'createdAt' => [
                'columns' => ['createdAt' => 1],
            ],
            'category' => [
                'columns' => ['category' => 1],
            ],
            'type' => [
                'columns' => ['type' => 1],
            ]
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'type' => [
                'type' => 'SingleSelect',
                'label' => 'Type',
                'options' => [
                    self::TYPE_DEBUG => 'Debug',
                    self::TYPE_TRACE => 'Trace',
                    self::TYPE_PROFILE => 'Profile',
                    self::TYPE_INFO => 'Info',
                    self::TYPE_WARNING => 'Warning',
                    self::TYPE_ERROR => 'Error',
                    self::TYPE_EXCEPTION => 'Exception',
                ],
                'default' => self::TYPE_ERROR,
                'required' => true
            ],
            'message' => [
                'type' => 'Json',
                'label' => 'Message',
                'required' => true,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'request' => [
                'type' => 'Json',
                'label' => 'Request',
                'required' => true,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'data' => [
                'type' => 'Json',
                'label' => 'Data',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'category' => [
                'type' => 'Text',
                'label' => 'Category',
                'required' => false,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\CodeField',
                    ]
                ]
            ],
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['type', 'message', 'request', 'data', 'category'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['type', 'category', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['type', 'message', 'data', 'request', 'category', 'createdUserId', 'createdAt', 'updatedUserId', 'updatedAt', '_id'];
        $scenarios[self::SCENARIO_SEARCH] = ['type', 'category'];

        return $scenarios;
    }

}