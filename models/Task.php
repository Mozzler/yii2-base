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
    
    public static function modelIndexes() {
		return ArrayHelper::merge(parent::modelIndexes(), [
		]);
    }

    protected function modelFields() {
		return ArrayHelper::merge(parent::modelFields(),  [
            'scriptClass' => [
                'type' => 'Text'
            ],
            'status' => [
                'type' => 'SingleSelect',
                'options' => ['pending' => 'Pending', 'inProgress' => 'In Progress', 'complete' => 'Complete', 'error' => 'Error']
            ],
            'config' => [
                'type' => 'Json'
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
