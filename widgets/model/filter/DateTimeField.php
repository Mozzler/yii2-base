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
//                        "Today" => ["moment().startOf('day')", "moment().endOf('day')"], // There's a bug where this doesn't work when first clicked, you have to select a different range, then select today. It seems to be an annoying bug
                        "Yesterday" => ["moment().startOf('day').subtract(1,'days')", "moment().endOf('day').subtract(1,'days')"],
                        "This Week" => ["moment().startOf('week')", "moment().endOf('day')"],
                        "This Month" => ["moment().startOf('month')", "moment().endOf('month')"],
                        "Last Month" => ["moment().subtract(1, 'month').startOf('month')", "moment().subtract(1, 'month').endOf('month')"],
                        "This Year" => ["moment().startOf('year')", "moment().endOf('day')"],
                        "Last Year" => ["moment().subtract(1, 'year').startOf('year')", "moment().subtract(1, 'year').endOf('year')"],
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

