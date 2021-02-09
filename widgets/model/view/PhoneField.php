<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class PhoneField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "a",
            "options" => [
                "class" => "mozzler-phone",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);
        $config['options']['href'] = 'tel:' . htmlentities($config['model']->__get($config['attribute'])); // Make it a link
        return $config;
    }

}
