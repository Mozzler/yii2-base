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
                    'ranges' => [
                        "Today" => ["moment().startOf('day')", "moment()"],
                        "Yesterday" => ["moment().startOf('day').subtract(1,'days')", "moment().endOf('day').subtract(1,'days')"],
                        "This Month" => ["moment().startOf('month')", "moment().endOf('month')"],
                        "Last Month" => ["moment().subtract(1, 'month').startOf('month')", "moment().subtract(1, 'month').endOf('month')"],
                    ]
                ],
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
    }

}

