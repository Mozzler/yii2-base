<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class BooleanField extends BaseField {

    public function defaultConfig() {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'trueValue' => 'True',
            'falseValue' => 'False'
        ]);
    }

}

