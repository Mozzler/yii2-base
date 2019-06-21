<?php

namespace app\models\behaviours;

use MongoDB\BSON\ObjectId;
use mozzler\base\components\Tools;
use mozzler\base\models\Model;
use yii\db\BaseActiveRecord;
use yii\behaviors\AttributeBehavior;

class AssignUnnamedNameBehaviour extends AttributeBehavior
{

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->attributes = [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'name',
        ];
    }

    /**
     * Sets the name to the {{collectionName}}-{{_id}}
     */
    protected function getValue($event)
    {
        /** @var Model $model */
        $model = $event->sender;
        if (!isset($model->_id)) {
            $model->_id = new ObjectId();
        }

        if (empty($model->name)) {
            $name = $model->getModelConfig('label') . '-' . $model->_id;
        } else {
            $name = $model->name; // Keep the possibly custom set default
        }
        return $name;
    }
}
