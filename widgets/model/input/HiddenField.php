<?php

namespace mozzler\base\widgets\model\input;

use mozzler\base\helpers\WidgetHelper;
use yii\helpers\ArrayHelper;

class HiddenField extends BaseField
{

    public function config($templatify = false)
    {
        $config = ArrayHelper::merge($this->defaultConfig(), $this->config);

        // We get array to string conversions when trying to process array types as hidden fields, so this JSON encodes them
        if (!empty($config['model']) && !empty($config['attribute']) && is_array($config['model']->__get($config['attribute']))) {
            $config['fieldOptions']['value'] = json_encode($config['model']->__get($config['attribute']));
        }
        if ($templatify) {
            $config = WidgetHelper::templatifyConfig($config, ['widget' => $config]);
        }

        $config['id'] = $this->id;

        return $config;
    }
}
