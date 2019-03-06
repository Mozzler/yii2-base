<?php
namespace mozzler\base\models\behaviors;

use yii\db\BaseActiveRecord;
use yii\behaviors\AttributesBehavior;

/**
 * Autoincrement behavior to set the initial value of any
 * autoincrement fields to be the most recently insterted
 * autoincrement value
 */
class AutoIncrementBehavior extends AttributesBehavior
{
    public $autoIncrementAttributes;

    public $startValue = 1;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $attributes = [];

        foreach ($this->autoIncrementAttributes as $attribute) {
            $attributes[$attribute] = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this, 'getAutoIncrementValue']
            ];
        }

        $this->attributes = $attributes;
    }

    /**
     */
    protected function getAutoIncrementValue($event, $attribute)
    {
        $model = $this->owner;

        // locate the last inserted autoincrement value for this attribute
        $models = \Yii::$app->t::getModels($model::className(), [], [
            'sort' => [
                $attribute => -1
            ],
            'limit' => 1,
            'checkPermissions' => false
        ]);

        // No autoincrement values found, so return 1 for the first value
        if (sizeof($models) == 0) {
            return $this->startValue;
        }

        // Found the last auto increment value, so increment by one
		return intval($models[0]->$attribute)+1;
    }
}
