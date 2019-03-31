<?php

namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use kartik\datecontrol\DateControl;

class DateTimeField extends BaseField
{

    public $widgetConfig = []; // Allowing external edits

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'type' => DateControl::FORMAT_DATETIME,
//                'disabled' => true,
//                'startDate' => date('Y-m-d'),
                'displayFormat' => 'php:' . \Yii::$app->formatter->datetimeFormat,
//                'pluginOptions' => ['minDate' => '2019-03-31' /*date('Y-m-d')*/,'startDate' => date('Y-m-d'),],
            ]
        ], $this->widgetConfig);
    }

    public function run()
    {
        $config = $this->config();
        $form = $config['form'];
        $model = $config['model'];
        $attribute = $config['attribute'];

        $field = $form->field($model, $attribute);
        \Yii::debug("Setting the DateTimeField widgetConfig to " . json_encode($config['widgetConfig']));

        return $field->widget(DateControl::className(), $config['widgetConfig']);
    }

}

?>