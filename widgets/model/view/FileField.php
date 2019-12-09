<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class FileField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "div",
            "options" => [
                "class" => "view-model-field-file",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = false)
    {
        $config = parent::config();

        // @todo: Lookup the file relation.

        if (!empty($config['attribute']) && !empty($config['model'])) {
            $attribute = $config['attribute'];
        }
        return $config;
    }

}
