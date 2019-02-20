<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Class CronRun
 * @package mozzler\base\models
 *
 * 
 */
class CronRun extends BaseModel
{
    use LoggableModelTrait;

    protected static $collectionName = 'app.cronRun';

    protected function modelConfig()
    {
        return [
            'label' => 'Cron Run',
            'labelPlural' => 'Cron Runs'
        ];
    }

    public static function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'timestampUniqueId' => [
                'columns' => ['timestamp' => 1],
                'options' => [
                    'unique' => 1
                ],
                'duplicateMessage' => ['Cron has already been run for this timetamp']
            ]
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'timestamp' => [
                'type' => 'Integer',
                'label' => 'Timestamp (Minute)',
                'required' => true,
            ],
            'stats' => [
                'type' => 'Json',
                'label' => 'Stats'
            ],
            'log' => [
                'type' => 'Json', // Use addLog(),
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
                ]
                'default' => 'processing'
            ],

        ]);
    }

    public function scenarios()
    {
        return [
            'update' => 'timestamp',
            'view' => ['timestamp', 'summary'],
            'list' => 'timestamp',
            'export' => ['timestamp', 'summary']
        ];
    }

}
