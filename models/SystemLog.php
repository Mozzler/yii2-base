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
 * @property array $response
 * @property array $data
 * @property integer $code
 * @property string $namespace
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
                'type' => 'TextLarge',
                'label' => 'Message',
                'required' => true,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\PreField',
                    ]
                ]
            ],
            'request' => [
                'type' => 'Json',
                'label' => 'Request',
                'required' => true,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\PreField',
                    ]
                ]
            ],
            'response' => [
                'type' => 'Json',
                'label' => 'Request',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\PreField',
                    ]
                ]
            ],
            'data' => [
                'type' => 'Json',
                'label' => 'Request',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\PreField',
                    ]
                ]
            ],
            'code' => [
                'type' => 'Integer',
                'label' => 'Code',
                'required' => false
            ],
            'namespace' => [
                'type' => 'Text',
                'label' => 'Namespace',
                'required' => false,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\CodeField',
                    ]
                ]
            ],
        ]);
    }


    /**
     * At any level of authorisation you should be able to create a new SystemLog entry
     */
    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
                'insert' => [
                    'grant' => true
                ]
            ],
            'public' => [
                'insert' => [
                    'grant' => true
                ]
            ],
            'admin' => [
                'insert' => [
                    'grant' => true
                ],
                'view' => [
                    'grant' => true
                ],
                'update' => [
                    'grant' => true
                ],
                'delete' => [
                    'grant' => true
                ],
            ],
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['type', 'message', 'code', 'request', 'namespace'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['type', 'code', 'namespace', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['type', 'code', 'message', 'request', 'namespace', 'createdUserId', 'createdAt', 'updatedUserId', 'updatedAt', '_id'];
        $scenarios[self::SCENARIO_SEARCH] = ['type', 'code', 'namespace', 'message'];

        return $scenarios;
    }

}