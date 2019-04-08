<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Model that stores information about a background task to run
 */
class Task extends BaseModel
{
    use traits\LoggableModelTrait;

    const TRIGGER_TYPE_INSTANT = 'instant';
    const TRIGGER_TYPE_BACKGROUND = 'background';

    const STATUS_PENDING = 'pending';
    const STATUS_INPROGRESS = 'inProgress';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR = 'error';

    protected static $collectionName = 'app.task';

    protected function modelConfig()
    {
        return [
            'label' => 'Task',
            'labelPlural' => 'Tasks'
        ];
    }

    public function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'uniqueName' => [
                'columns' => ['name' => 1],
                'options' => [
                    'unique' => 1
                ],
                'duplicateMessage' => 'That task already exists'
            ],
            'createdAt' => [
                'columns' => ['createdAt' => 1]
            ]
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'scriptClass' => [
                'label' => 'Script Class',
                'type' => 'Text'
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'SingleSelect',
                'options' => [self::STATUS_PENDING => 'Pending', self::STATUS_INPROGRESS => 'In Progress', self::STATUS_COMPLETE => 'Complete', self::STATUS_ERROR => 'Error']
            ],
            'triggerType' => [
                'label' => 'Trigger Type',
                'type' => 'SingleSelect',
                'options' => [self::TRIGGER_TYPE_INSTANT => 'Instant', // Run via the Command Line straight away (esp used by the Cron manager)
                    self::TRIGGER_TYPE_BACKGROUND => 'Background' // Run by the background task manager (e.g If a user requests a large CSV file to be generated and emailed to them)
                ]
            ],
            'config' => [
                'type' => 'Json',
                'label' => 'Config'
            ],
            'log' => [
                'label' => 'Logs',
                'type' => 'Json', // Use addLog(),
                'default' => [],
            ],
            'timeoutSeconds' => [
                'label' => 'Timeout In Seconds',
                'type' => 'Integer',
                // Used by the TaskController command
                // You can set to 0 if you want it to run indefinitely, but doing so could cause stuck processes which could cause the server to crash so would be a VERY bad idea
            ]
        ]);
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['scriptClass', 'status', 'triggerType', 'config', 'timeoutSeconds'];
//        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_UPDATE] = ['scriptClass', 'status', 'triggerType', 'timeoutSeconds']; // You shouldn't really be able to edit them
        $scenarios[self::SCENARIO_LIST] = ['scriptClass', 'status', 'triggerType', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['scriptClass', 'status', 'triggerType', 'timeoutSeconds', 'createdAt', 'updatedAt', 'log'];
        $scenarios[self::SCENARIO_SEARCH] = ['scriptClass', 'status'];

        return $scenarios;
    }

    public static function rbac() {
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

}
