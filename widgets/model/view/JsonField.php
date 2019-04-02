<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class JsonField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "pre",
            "options" => [
                "class" => "view-model-field-json",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = false)
    {
        $config = parent::config();
        if (!empty($config['attribute']) && !empty($config['model'])) {
            $attribute = $config['attribute'];
            // -- Create a nice JSON output with spacing
            $config['prettyJson'] = json_encode($config['model']->$attribute, JSON_PRETTY_PRINT);
            $config['options']['class'] .= " view-model-field-json--$attribute";
            $config['options']['class'] = trim($config['options']['class']);
        }
        return $config;
    }

}

?>