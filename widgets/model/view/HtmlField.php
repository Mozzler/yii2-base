<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class HtmlField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "div",
            "options" => [
                "class" => "",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);
        return $config;
    }
}
