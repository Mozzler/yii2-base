<?php

namespace mozzler\base\models;

use mozzler\base\models\behaviors\AutoIncrementBehavior;
use mozzler\base\models\behaviors\GarbageCollectionBehaviour;
use mozzler\base\models\Model as BaseModel;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * Class CronRun
 * @package mozzler\base\models
 *
 * @property integer $timestamp
 * @property array $stats
 * @property array $log
 * @property string $status
 */
class CronRun extends BaseModel
{
    use traits\LoggableModelTrait;

    protected static $collectionName = 'app.cronRun';

    protected function modelConfig()
    {
        return [
            'label' => 'Cron Run',
            'labelPlural' => 'Cron Runs'
        ];
    }

    public function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'timestampUniqueId' => [
                'columns' => ['timestamp' => 1],
                'options' => [
                    'unique' => 1
                ],
                'duplicateMessage' => ['Cron has already been run for this timetamp']
            ],
            'createdAt' => [
                'columns' => ['createdAt' => 1],
            ],
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'timestamp' => [
                'type' => 'Timestamp',
                'label' => 'Timestamp (Minute)',
                'required' => true,
            ],
            'stats' => [
                'type' => 'JsonArray',
                'label' => 'Stats'
            ],
            'log' => [
                'type' => 'JsonArray', // Use addLog(),
                'rules' => [
                    'default' => ['value' => []]
                ]
            ],
            'status' => [
                'type' => 'SingleSelect',
                'label' => 'Status',
                'options' => [
                    'processing' => 'Processing',
                    'error' => 'Error',
                    'complete' => 'Complete'
                ],
                'rules' => [
                    'default' => ['value' => 'processing']
                ]
            ],

        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['timestamp', 'stats', 'status', 'log'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['timestamp', 'status', 'logSize'];
        $scenarios[self::SCENARIO_VIEW] = ['timestamp', 'stats', 'status', 'log'];
        $scenarios[self::SCENARIO_SEARCH] = ['status'];

        return $scenarios;
    }

    public static function rbac()
    {
        return ArrayHelper::merge(parent::rbac(), [
            'registered' => [
                'find' => [
                    'grant' => false
                ],
                'insert' => [
                    'grant' => false
                ],
                'update' => [
                    'grant' => false
                ],
                'delete' => [
                    'grant' => false
                ]
            ],
            'admin' => [
                'insert' => [
                    'grant' => false
                ],
                'update' => [
                    'grant' => false
                ],
                'delete' => [
                    'grant' => false
                ]
            ]
        ]);
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'garbagecollection' => GarbageCollectionBehaviour::class
        ]);
    }
}
