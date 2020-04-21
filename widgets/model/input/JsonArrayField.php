<?php

namespace mozzler\base\widgets\model\input;

use mozzler\base\helpers\WidgetHelper;
use yii\helpers\ArrayHelper;

class JsonArrayField extends BaseField
{

    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'rows' => 4,
                'class' => 'form-control mozzler-json-array-field-input' // Default class is form-control, but we want to specially target these filters with some CSS to make the text monospaced
            ]
        ]);
    }


    public function config($templatify = false)
    {

        $config = ArrayHelper::merge($this->defaultConfig(), $this->config);

        // We get array to string conversions when trying to process array types as hidden fields, so this JSON encodes them
        if (!empty($config['model']) && !empty($config['attribute']) && is_array($config['model']->__get($config['attribute']))) {
            $config['widgetConfig']['value'] = json_encode($config['model']->__get($config['attribute']), JSON_PRETTY_PRINT);
        }
        if ($templatify) {
            $config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
        }

        $config['id'] = $this->id;

        return $config;
    }
}
