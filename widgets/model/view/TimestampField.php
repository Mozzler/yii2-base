<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class TimestampField extends BaseField {

    public function defaultConfig() {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'format' => \Yii::$app->formatter->datetimeFormat,
            'timeZone' => \Yii::$app->formatter->timeZone
        ]);
    }

}

