<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class PreField extends BaseField {
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "pre",
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
