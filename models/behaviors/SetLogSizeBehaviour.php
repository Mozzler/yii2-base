<?php

namespace mozzler\base\models\behaviors;

use MongoDB\BSON\ObjectId;
use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\models\Model;
use yii\db\BaseActiveRecord;
use yii\behaviors\AttributeBehavior;

class SetLogSizeBehaviour extends AttributeBehavior
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->attributes = [
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'log',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'log',
        ];
    }

    protected function getValue($event)
    {
        /* @var /mozzler\base\models\Model $model */
        $model = $event->sender;
        $model->logSize = $model->returnLogSize();
        return $model->log;
    }
}