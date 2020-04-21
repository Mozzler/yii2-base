<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class DateField extends BaseField
{

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'format' => \Yii::$app->formatter->dateFormat,
            'timeZone' => \Yii::$app->formatter->timeZone,
        ]);
    }

}

