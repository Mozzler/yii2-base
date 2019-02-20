<?php

namespace mozzler\base\models;

use mozzler\base\models\Model as BaseModel;
use yii\helpers\ArrayHelper;

/**
 * Class Cache
 * @package mozzler\base\models
 *
 * This is to support the \mozzler\base\components\MozzlerCache and allow admin control panel access
 * But most importantly to use the modelIndex creation.
 *
 * @see \mozzler\base\components\MozzlerCache
 */
class Cache extends BaseModel
{

    protected static $collectionName = 'app.cache'; // This should be the same as what's used in \mozzler\base\components\cache

    protected function modelConfig()
    {
        return [
            'label' => 'Cache',
            'labelPlural' => 'Caches'
        ];
    }

    public static function modelIndexes()
    {
        return ArrayHelper::merge(parent::modelIndexes(), [
            'cacheUniqueId' => [
                'columns' => ['id' => 1],
                'options' => [
                    'unique' => 1
                ],
                'duplicateMessage' => ['id already exists']
            ]
        ]);
    }

    protected function modelFields()
    {
        return ArrayHelper::merge(parent::modelFields(), [
            'id' => [
                'type' => 'Text',
                'label' => 'Namespace/Field',
                'required' => true,
            ],
            'data' => [
                'type' => 'TextLarge',
                'label' => 'Value',
                'required' => true,
            ],
            'expire' => [
                'type' => 'Integer',
                'label' => 'Expires',
                'rules' => [
                    'default' => ['value' => 0],
                ]
            ]
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['id', 'data', 'expire'];
        $scenarios[self::SCENARIO_UPDATE] = $scenarios[self::SCENARIO_CREATE];
        $scenarios[self::SCENARIO_LIST] = ['id', 'data', 'expire'];
        $scenarios[self::SCENARIO_VIEW] = ['id', 'data', 'expire'];
        return $scenarios;
    }

}
