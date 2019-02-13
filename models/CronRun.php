<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

class CronRun extends BaseModel
{

    protected static $collectionName = 'app.cronrun';
	protected function modelConfig()
	{
		return [
			'label' => 'Cron Run',
			'labelPlural' => 'Cron Runs'
		];
    }
    
    public static function modelIndexes() {
		return ArrayHelper::merge(parent::modelIndexes(), [
            // TODO: Add a unique index on timestampMinute
		]);
    }

    protected function modelFields() {
		return ArrayHelper::merge(parent::modelFields(),  [
            'timestampMinute' => [
                'type' => 'Timestamp'
            ],
            'log' => [
                'type' => 'TextLarge'
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
