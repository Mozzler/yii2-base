<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use kartik\datecontrol\DateControl;

class DateField extends DateTimeField
{

    public function defaultConfig() {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'type' => DateControl::FORMAT_DATE,
                'displayFormat' => 'php:'.\Yii::$app->formatter->dateFormat
            ]
        ]);
    }
	
}

?>