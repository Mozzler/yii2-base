<?php

namespace mozzler\base\widgets\model\filter;

use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;

//use kartik\datecontrol\DateControl;

class DateTimeField extends BaseField
{

    public $widgetConfig = []; // Allowing external edits
    public $minDateToday = true;

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'pluginOptions' => [
//                    'locale' => ['format' => 'Y-m-d']
//                    'value' => '2015-10-19 - 2015-11-03',
                ],
                'convertFormat' => true,
                'options' => [
                    'prepend' => ['content' => '<i class="fas fa-calendar-alt"></i>'],
//                    'class' => 'drp-container form-group'
                ]
//                'class' => DateRangePicker::class,
//                'type' => DateControl::FORMAT_DATETIME,
//                'displayFormat' => 'php:' . \Yii::$app->formatter->datetimeFormat,
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
        return $field->widget(DateRangePicker::class, $config['widgetConfig']);
//        return $field->widget(DateControl::className(), $config['widgetConfig']);
    }

}

