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

    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
                // -- The System Log shouldn't be created by a user but by the SystemLog Target
                // NB: If your registered users are Admin Control Panel users then you likely want them to have find and view
                'insert' => ['grant' => false],
                'update' => ['grant' => false],
                'find' => ['grant' => false],
                'view' => ['grant' => false],
                'delete' => ['grant' => false]
            ]
        ]);
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
                'required' => true,
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\CodeField',
                    ]
                ]
            ],
            'message' => [
                'type' => 'TextLarge',
                'label' => 'Message',
                'required' => true,
            ],
            'endpoint' => [
                'type' => 'Text',
                'label' => 'Endpoint',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\LinkField',
                    ]
                ]
            ],
            'messageData' => [
                'type' => 'Json',
                'label' => 'Message Data',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'trace' => [
                'type' => 'Json',
                'label' => 'Trace',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'requestData' => [
                'type' => 'Json',
                'label' => 'Request Data',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'systemData' => [
                'type' => 'Json',
                'label' => 'System Data',
                'hint' => 'The log vars data',
                'widgets' => [
                    'view' => [
                        'class' => 'mozzler\base\widgets\model\view\JsonField',
                    ]
                ]
            ],
            'category' => [
                'type' => 'Text',
                'label' => 'Category',
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
        $scenarios[self::SCENARIO_CREATE] = ['type', 'message', 'endpoint', 'messageData', 'systemData', 'requestData', 'trace', 'category'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['type', 'category', 'createdAt', 'message', 'endpoint'];
        $scenarios[self::SCENARIO_VIEW] = ['_id', 'message', 'type', 'category', 'createdAt', 'endpoint', 'messageData', 'requestData', 'systemData', 'trace', 'createdUserId', 'updatedUserId', 'updatedAt'];
        $scenarios[self::SCENARIO_SEARCH] = ['type', 'category', 'message', 'endpoint'];

        return $scenarios;
    }

}