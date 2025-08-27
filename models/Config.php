<?php

namespace mozzler\base\models;

use mozzler\base\models\behaviors\AuditLogBehaviour;
use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Class Config
 * @package mozzler\base\models
 *
 * ============================================================
 *     Config
 * ============================================================
 * Fields: [ '_id', 'name', 'createdAt', 'createdUserId', 'updatedAt', 'updatedUserId', 'key_', 'value_', 'description', ]
 *
 * @property string $key_
 * @property string $value_
 * @property string $description
 *
 */
class Config extends BaseModel
{

    protected static $collectionName = 'app.config';

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

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['key_', 'value_', 'description'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['key_', 'value_', 'description', 'createdAt', 'updatedAt', 'updatedUserId'];
        $scenarios[self::SCENARIO_VIEW] = ['key_', 'value_', 'description', 'createdAt', 'updatedAt', 'createdUserId', 'updatedUserId'];
        $scenarios[self::SCENARIO_SEARCH] = ['key_', 'value_', 'description', 'createdAt', 'updatedAt'];

        return $scenarios;
    }

    protected function modelConfig()
    {
        return [
            'label' => 'Config',
            'labelPlural' => 'Configs'
        ];
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
                'type' => 'TextLarge', // TextLarge allows more than 256 chars
                'label' => 'Value',
                'required' => true
            ],

            // Optional info about this field and why you set it to this
            'description' => [
                'type' => 'TextLarge',
                'label' => 'Description',
                'hint' => "Basic info about why you've set it to this value or you can explain what the value does"
            ],
        ]);
    }

}
