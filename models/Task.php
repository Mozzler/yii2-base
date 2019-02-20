<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Model that stores information about a background task to run
 */
class Task extends BaseModel
{
    use LoggableModelTrait;

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

    public static function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'scriptClass' => [
                'type' => 'Text'
            ],
            'status' => [
                'type' => 'SingleSelect',
                'options' => [self::STATUS_PENDING => 'Pending', self::STATUS_INPROGRESS => 'In Progress', self::STATUS_COMPLETE => 'Complete', self::STATUS_ERROR => 'Error']
            ],
            'triggerType' => [

                'type' => 'SingleSelect',
                'options' => [self::TRIGGER_TYPE_INSTANT => 'Instant', // Run via the Command Line straight away (esp used by the Cron manager)
                    self::TRIGGER_TYPE_BACKGROUND => 'Background' // Run by the background task manager (e.g If a user requests a large CSV file to be generated and emailed to them)
                ]
            ],
            'config' => [
                'type' => 'Json'
            ],
            'log' => [
                'type' => 'Json', // Use addLog(),
                'rules' => [
                    'default' => ['value' => []]
                ]
            ],
            'timeoutSeconds' => [
                'type' => 'Integer'
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

        return $scenarios;
    }

}
