<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class AhrefField extends BaseField {
    public function defaultConfig() {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'target' => '_blank',
            'title' => ''
        ]);
    }

}

?>