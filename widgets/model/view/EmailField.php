<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class EmailField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "a",
            "options" => [
                "class" => "mozzler-email",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);
        $config['options']['href'] = 'mailto:' . htmlentities($config['model']->__get($config['attribute']));
        return $config;
    }

}
