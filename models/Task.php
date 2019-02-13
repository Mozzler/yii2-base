<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Model that stores information about a background task to run
 */
class Task extends BaseModel
{

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
                'options' => ['pending' => 'Pending', 'inProgress' => 'In Progress', 'complete' => 'Complete', 'error' => 'Error']
            ],
            'triggerType' => [

                'type' => 'SingleSelect',
                'options' => ['instant' => 'Instant',
                    'background' => 'Background' // Run by the background task manager
                ],
            ],
            'config' => [
                'type' => 'Json'
            ],
            'log' => [
                'type' => 'json' // Support AddLog
            ],
            'timeoutSeconds' => [
                'type' => 'Integer'
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

    /**
     * @param $message
     * @param $type
     *
     * Expected types: 'warning', 'info', 'error'
     */
    public function addLog($message, $type)
    {
        $this->log[] = [
            'timestamp' => time(),
            'message' => $message,
            'type' => $type
        ];
    }

}
