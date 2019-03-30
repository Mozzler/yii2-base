<?php

namespace mozzler\base\models;

use mozzler\base\models\behaviors\AuditLogBehaviour;
use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

class Config extends BaseModel
{

    protected static $collectionName = 'app.config';

    protected function modelConfig()
    {
        return [
            'label' => 'Config',
            'labelPlural' => 'Configs'
        ];
    }

    public function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'uniqueKey_' => [
                'columns' => ['key_' => 1],
                'options' => [
                    'unique' => 1
                ],
                'duplicateMessage' => ['Config key already exists']
            ]
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'key_' => [
                'type' => 'Text',
                'label' => 'Key',
                'required' => true
            ],
            'value_' => [
                'type' => 'Text',
                'label' => 'Value',
                'required' => true
            ]
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['key_', 'value_'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['key_', 'value_', 'createdAt'];
        $scenarios[self::SCENARIO_VIEW] = ['key_', 'value_', 'createdAt'];
        $scenarios[self::SCENARIO_SEARCH] = ['key_'];

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
                    'grant' => true
                ],
                'update' => [
                    'grant' => true
                ],
                'delete' => [
                    'grant' => true
                ]
            ]
        ]);
    }

}
